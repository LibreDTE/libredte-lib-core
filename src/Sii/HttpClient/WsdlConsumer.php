<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Sii\HttpClient;

use SoapClient;
use SoapFault;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\SignatureException;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;

/**
 * Clase para consumir los servicios web SOAP del SII.
 */
class WsdlConsumer
{
    /**
     * Certificado digital.
     *
     * @var Certificate
     */
    private Certificate $certificate;

    /**
     * Configuración de la conexión al SII.
     *
     * @var ConnectionConfig
     */
    private ConnectionConfig $config;

    /**
     * Constructor de la clase que consume servicios web mediante WSDL del SII.
     *
     * @param Certificate $certificate
     * @param ConnectionConfig $config
     */
    public function __construct(
        Certificate $certificate,
        ConnectionConfig $config,
    )
    {
        $this->certificate = $certificate;
        $this->config = $config;
    }

    /**
     * Método para obtener el token de la sesión en el SII.
     *
     * Primero se obtiene una semilla, luego se firma la semilla con el
     * certificado digital y con esta semilla firmada se hace la solicitud del
     * token al SII.
     *
     * Referencia: http://www.sii.cl/factura_electronica/autenticacion.pdf
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/GetTokenFromSeed.jws?WSDL
     *
     * @return string Token para autenticación en SII.
     * @throws SiiClientException En caso de error.
     */
    public function getToken(): string
    {
        // Obtener semilla.
        $semilla = $this->getSeed();

        // Crear solicitud del token con la semilla, parámetro getTokenRequest
        // de la función getToken() en el servicio web GetTokenFromSeed.
        $xmlRequest = XmlConverter::arrayToXml([
            'getToken' => [
                'item' => [
                    'Semilla' => $semilla,
                ],
            ],
        ]);

        // Firmar el XML de la solicitud del token.
        try {
            $xmlRequestSigned = SignatureGenerator::signXML(
                $xmlRequest,
                $this->certificate
            );
        } catch (SignatureException $e) {
            throw new SiiClientException(sprintf(
                'No fue posible firmar getToken. %s',
                $e->getMessage()
            ));
        }

        // Realizar la solicitud del token al SII.
        $xmlResponse = $this->sendRequest(
            'GetTokenFromSeed',
            'getToken',
            ['pszXml' => $xmlRequestSigned]
        );

        // Extraer respuesta de la solicitud del token.
        $response = XmlConverter::xmlToArray($xmlResponse);
        $estado = $response['SII:RESPUESTA']['SII:RESP_HDR']['ESTADO'] ?? null;
        $token = $response['SII:RESPUESTA']['SII:RESP_BODY']['TOKEN'] ?? null;

        // Validar respuesta de la solicitud del token.
        if ($estado !== '00' || $token === null) {
            $glosa = $response['SII:RESPUESTA']['SII:RESP_HDR']['GLOSA'] ?? null;
            throw new SiiClientException(sprintf(
                'No fue posible obtener el token para autenticar en el SII al usuario %s. %s',
                $this->certificate->getId(),
                $glosa
            ));
        }

        // Entregar el token obtenido desde el SII para la sesión.
        return $token;
    }

    /**
     * Obtiene una semilla desde el SII para luego usarla en la obtención del
     * token para la autenticación.
     *
     * Este es el único servicio web que se puede llamar sin utilizar el
     * certificado digital. Es de libre consumo y se usa para obtener la
     * semilla necesaria para luego, usando el certificado, obtener un token
     * válido para la sesión en el SII.
     *
     * Nota: la semilla tiene una validez de 2 minutos.
     *
     * WSDL producción: https://palena.sii.cl/DTEWS/CrSeed.jws?WSDL
     * WSDL certificación: https://maullin.sii.cl/DTEWS/CrSeed.jws?WSDL
     *
     * @return int La semilla si se logró obtener.
     * @throws SiiClientException En caso de error.
     */
    public function getSeed(): int
    {
        $xmlResponse = $this->sendRequest('CrSeed', 'getSeed');
        $response = XmlConverter::xmlToArray($xmlResponse);
        $estado = $response['SII:RESPUESTA']['SII:RESP_HDR']['ESTADO'] ?? null;
        $semilla = $response['SII:RESPUESTA']['SII:RESP_BODY']['SEMILLA'] ?? null;

        if ($estado !== '00' || $semilla === null) {
            throw new SiiClientException('No fue posible obtener la semilla.');
        }

        return (int) $semilla;
    }

    /**
     * Método para obtener el XML del WSDL (Web Services Description Language)
     * del servicio del SII que se desea consumir.
     *
     * @param string $servicio Servicio para el cual se desea obtener su WSDL.
     * @return string Ubicación del WSDL del servicio según el ambiente que
     * esté configurado. Entrega, normalmente, un archivo local para un WSDL
     * del ambiente de certificación y siempre una URL para un WSDL del
     * ambiente de producción.
     */
    public function getWsdlUri(string $servicio): string
    {
        $ambiente = $this->config->getAmbiente();

        // Algunos WSDL del ambiente de certificación no funcionan tal cual los
        // provee SII. Lo anterior ya que apuntan a un servidor llamado
        // nogal.sii.cl el cual no es accesible desde Internet. Posiblemente es
        // un servidor local del SII para desarrollo. Así que LibreDTE tiene
        // para el ambiente de certificación WSDL modificados para funcionar
        // con el servidor de pruebas (maullin.sii.cl). Estos WSDL se usan
        // siempre al solicitar el WSDL del ambiente de certificación.
        // Cambios basados en: http://stackoverflow.com/a/28464354/3333009
        if ($ambiente === ConnectionConfig::CERTIFICACION) {
            $servidor = $this->config->getServidor();
            $wsdl = PathManager::getWsdlPath($servidor, $servicio);
            if ($wsdl !== null) {
                return $wsdl;
            }
        }

        // Los WSDL para el ambiente de producción son directamente los
        // proporcionados por el SII y que están definidos en la configuración.
        // Si por cualquier motivo un WSDL de un servicio para el ambiente de
        // certificación no existe localmente en LibreDTE, también se entregará
        // el WSDL oficial del SII.
        return $this->config->getWsdl($servicio);
    }

    /**
     * Realiza una solicitud a un servicio web del SII mediante el uso de WSDL.
     *
     * Este método prepara y normaliza los datos recibidos y llama al método
     * que realmente hace la consulta al SII: callServiceFunction().
     *
     * @param string $service Nombre del servicio del SII que se consumirá.
     * @param string $function Nombre de la función que se ejecutará en el
     * servicio web del SII.
     * @param array|int $args Argumentos que se pasarán al servicio web.
     * @param int|null $retry Intentos que se realizarán como máximo para
     * obtener respuesta.
     * @return XmlDocument Documento XML con la respuesta del servicio web.
     * @throws SiiClientException En caso de error.
     */
    public function sendRequest(
        string $service,
        string $function,
        array|int $args = [],
        ?int $retry = null
    ): XmlDocument
    {
        // Revisar si se pasó en $args el valor de $retry.
        if (is_numeric($args)) {
            $retry = (int)$args;
            $args = [];
        }

        // Si no se especificó $retry se obtiene el valor por defecto.
        $retry = max(0, min(
            $retry ?? $this->config->getReintentos(),
            ConnectionConfig::REINTENTOS
        ));

        // Definir el WSDL que se debe utilizar.
        $wsdl = $this->getWsdlUri($service);

        // Realizar la llamada a la función en el servicio web del SII.
        return $this->callServiceFunction($wsdl, $function, $args, $retry);
    }

    /**
     * Ejecuta una función en un servicio web del SII mediante el uso de WSDL.
     *
     * @param string $wsdl El WSDL del servicio web donde está la función.
     * @param string $function Nombre de la función que se ejecutará,
     * @param array $args Argumentos que se pasarán al servicio web.
     * @param int $retry Intentos que se realizarán como máximo.
     * @return XmlDocument Documento XML con la respuesta del servicio web.
     * @throws SiiClientException En caso de error.
     */
    private function callServiceFunction(
        string $wsdl,
        string $function,
        array $args,
        int $retry
    ): XmlDocument
    {
        // Definir las opciones para consumir el servicio web.
        $soapClientOptions = $this->createSoapClientOptions();

        // Preparar cliente SOAP.
        try {
            $soap = new SoapClient($wsdl, $soapClientOptions);
        } catch (SoapFault $e) {
            $message = $e->getMessage();
            if (
                isset($e->getTrace()[0]['args'][1])
                && is_string($e->getTrace()[0]['args'][1])
            ) {
                $message .= ': ' . $e->getTrace()[0]['args'][1];
            }
            throw new SiiClientException(sprintf(
                'Ocurrió un error al crear el cliente SOAP para la API del SII con el WSDL %s. %s',
                $wsdl,
                $message
            ));
        }

        // Argumentos adicionales para la llamada al servicio web SOAP mediante
        // __soapCall().
        $options = null;

        // En el WSDL indicadas como soap:header.
        $requestHeaders = [];

        // Si el SII enviase cabeceras SOAP devuelta.
        $responseHeaders = [];

        // Para almacenar la respuesta de la llamada a la API SOAP del SII.
        $responseBody = null;

        // Para ir almacenando los errores, si existen, de cada intento.
        $errors = [];

        // Ejecutar la función que se ha solicitado del servicio web a través
        // del cliente SOAP preparado previamente.
        // Se realizarán $retry intentos de consulta. O sea, si $retry > 0 se
        // hará una consulta más $retry - 1 reintentos.
        for ($i = 0; $i < $retry; $i++) {
            try {
                // Se realiza la llamada a la función en el servicio web.
                $responseBody = $soap->__soapCall(
                    $function,
                    $args,
                    $options,
                    $requestHeaders,
                    $outputHeaders
                );
                // Si la llamada no falló (no hubo excepción), se rompe el
                // ciclo de reintentos.
                break;
            } catch (SoapFault $e) {
                $message = $e->getMessage();
                if (
                    isset($e->getTrace()[0]['args'][1])
                    && is_string($e->getTrace()[0]['args'][1])
                ) {
                    $message .= ': ' . $e->getTrace()[0]['args'][1];
                }
                $errors[] = sprintf(
                    'Error al ejecutar la función %s en el servicio web SOAP del SII ($i = %d): %s',
                    $function,
                    $i,
                    $message
                );
                $responseBody = null;
                // El reitento será con "exponential backoff", por lo que se
                // hace una pausa de 0.2 * $retry segundos antes de volver a
                // intentar llamar a la función del servicio web.
                usleep(200000 * $retry);
            }
        }

        // Si la respuesta es `null` significa que ninguno de los intentos de
        // llamadas a la función del servicio web fue exitoso.
        if ($responseBody === null) {
            throw new SiiClientException(sprintf(
                'No se obtuvo respuesta de la función %s del servicio web SOAP del SII después de %d intentos. %s',
                $function,
                $retry,
                implode(' ', $errors)
            ));
        }

        // El SII indica que la respuesta que envía es:
        //   Content-Type: text/xml;charset=utf-8
        // Sin embargo, parece que indica que es UTF-8 pero envía contenido
        // codificado con ISO-8859-1 (que es lo esperable del SII). Lo que hace
        // que los caracteres especiales se obtengan como "ï¿½", por lo cual se
        // reemplazan en la respuesta por "?" para que sea "un poco" más
        // legible la respuesta del SII.
        $responseBody = str_replace(['ï¿½', '�'], '?', $responseBody);

        // Entregar el resultado como un documento XML.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXML($responseBody);
        return $xmlDocument;
    }

    /**
     * Define las opciones para consumir el servicio web del SII mediante SOAP.
     *
     * @return array Arreglo con las opciones para SoapClient.
     */
    private function createSoapClientOptions(): array
    {
        // Configuración de caché para SOAP.
        ini_set('soap.wsdl_cache_enabled', 3600);
        ini_set('soap.wsdl_cache_ttl', 3600);

        // Opciones base.
        $options = [
            'encoding' => 'ISO-8859-1',
            //'trace' => true, // Permite usar __getLastResponse().
            'exceptions' => true, // Lanza SoapFault en caso de error.
            'cache_wsdl' => WSDL_CACHE_MEMORY, // WSDL_CACHE_DISK o WSDL_CACHE_MEMORY.
            'keep_alive' => false,
            'stream_context' => [
                'http' => [
                    'header' => [
                        'User-Agent: Mozilla/4.0 (compatible; PROG 1.0; LibreDTE)',
                        'Referer: https://www.libredte.cl',
                    ],
                ],
            ],
        ];

        // Si no se debe verificar el certificado SSL del servidor del SII se
        // asigna al "stream_context" dicha configuración.
        if (!$this->config->getVerificarSsl()) {
            $options['stream_context']['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ];
        }

        // Crear el "stream context" verdadero con las opciones definidas.
        $options['stream_context'] = stream_context_create(
            $options['stream_context']
        );

        // Retornar las opciones para el SoapClient.
        return $options;
    }
}

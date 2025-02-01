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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job;

use Derafu\Lib\Core\Foundation\Abstract\AbstractJob;
use Derafu\Lib\Core\Foundation\Contract\JobInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Entity\Xml as XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiConsumeWebserviceException;
use SoapClient;
use SoapFault;

/**
 * Clase para consumir los servicios web SOAP del SII.
 */
class ConsumeWebserviceJob extends AbstractJob implements JobInterface
{
    /**
     * Realiza una solicitud a un servicio web del SII mediante el uso de WSDL.
     *
     * Este método prepara y normaliza los datos recibidos y llama al método
     * que realmente hace la consulta al SII: callServiceFunction().
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $service Nombre del servicio del SII que se consumirá.
     * @param string $function Nombre de la función que se ejecutará en el
     * servicio web del SII.
     * @param array|int $args Argumentos que se pasarán al servicio web.
     * @param int|null $retry Intentos que se realizarán como máximo para
     * obtener respuesta.
     * @return XmlInterface Documento XML con la respuesta del servicio web.
     * @throws SiiConsumeWebserviceException En caso de error.
     */
    public function sendRequest(
        SiiRequestInterface $request,
        string $service,
        string $function,
        array|int $args = [],
        ?int $retry = null
    ): XmlInterface {
        // Revisar si se pasó en $args el valor de $retry.
        // @scrutinizer ignore-type-check
        if (is_int($args)) {
            $retry = $args;
            $args = [];
        }

        // Definir el WSDL que se debe utilizar.
        $wsdl = $this->getWsdlUri($request, $service);

        // Resolver el valor de $retry.
        $retry = $request->getReintentos($retry);

        // Definir las opciones para consumir el servicio web.
        $soapClientOptions = $this->createSoapClientOptions($request);

        // Realizar la llamada a la función en el servicio web del SII.
        return $this->callServiceFunction(
            $wsdl,
            $function,
            $args,
            $soapClientOptions,
            $retry
        );
    }

    /**
     * Método para obtener el XML del WSDL (Web Services Description Language)
     * del servicio del SII que se desea consumir.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $servicio Servicio para el cual se desea obtener su WSDL.
     * @return string Ubicación del WSDL del servicio según el ambiente que
     * esté configurado. Entrega, normalmente, un archivo local para un WSDL
     * del ambiente de certificación y siempre una URL para un WSDL del
     * ambiente de producción.
     */
    private function getWsdlUri(SiiRequestInterface $request, string $servicio): string
    {
        $ambiente = $request->getAmbiente();

        // Algunos WSDL del ambiente de certificación no funcionan tal cual los
        // provee SII. Lo anterior ya que apuntan a un servidor llamado
        // nogal.sii.cl el cual no es accesible desde Internet. Posiblemente es
        // un servidor local del SII para desarrollo. Así que LibreDTE tiene
        // para el ambiente de certificación WSDL modificados para funcionar
        // con el servidor de pruebas (maullin.sii.cl). Estos WSDL se usan
        // siempre al solicitar el WSDL del ambiente de certificación.
        // Cambios basados en: http://stackoverflow.com/a/28464354/3333009
        if ($ambiente->isCertificacion()) {
            $wsdl = $ambiente->getWsdlPath($servicio);
            if ($wsdl !== null) {
                return $wsdl;
            }
        }

        // Los WSDL para el ambiente de producción son directamente los
        // proporcionados por el SII y que están definidos en la configuración.
        // Si por cualquier motivo un WSDL de un servicio para el ambiente de
        // certificación no existe localmente en LibreDTE, también se entregará
        // el WSDL oficial del SII.
        return $ambiente->getWsdl($servicio);
    }

    /**
     * Ejecuta una función en un servicio web del SII mediante el uso de WSDL.
     *
     * @param string $wsdl WSDL del servicio del SII que se consumirá.
     * @param string $function Nombre de la función que se ejecutará,
     * @param array $args Argumentos que se pasarán al servicio web.
     * @param array $soapClientOptions Opciones del cliente SOAP.
     * @param int $retry Intentos que se realizarán como máximo.
     * @return XmlInterface Documento XML con la respuesta del servicio web.
     * @throws SiiConsumeWebserviceException En caso de error.
     */
    private function callServiceFunction(
        string $wsdl,
        string $function,
        array $args,
        array $soapClientOptions,
        int $retry
    ): XmlInterface {
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
            throw new SiiConsumeWebserviceException(sprintf(
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
                    $responseHeaders
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
            throw new SiiConsumeWebserviceException(sprintf(
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
        $xmlDocument->loadXml($responseBody);
        return $xmlDocument;
    }

    /**
     * Define las opciones para consumir el servicio web del SII mediante SOAP.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return array Arreglo con las opciones para SoapClient.
     */
    private function createSoapClientOptions(SiiRequestInterface $request): array
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
                        'User-Agent: Mozilla/5.0 (compatible; PROG 1.0; +https://www.libredte.cl)',
                    ],
                ],
            ],
        ];

        // Si no se debe verificar el certificado SSL del servidor del SII se
        // asigna al "stream_context" dicha configuración.
        if (!$request->getVerificarSsl()) {
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

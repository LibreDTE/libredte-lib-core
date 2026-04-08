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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRtc\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtc\SendAecException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRtcException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use UnexpectedValueException;

/**
 * Clase para el envío de un AEC al RTC del SII.
 */
#[Job(name: 'send_aec', worker: 'sii_rtc', component: 'integration', package: 'billing')]
class SendAecJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
    ) {
    }

    /**
     * Envía un AEC al Registro de Transferencias de Créditos (RTC) del SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param XmlDocumentInterface $doc Documento XML del AEC.
     * @param string $company RUT del cedente.
     * @param string $emailNotif Correo electrónico de contacto del cedente.
     * @param int|null $retry Intentos máximos al enviar.
     * @return SendAecResponse Respuesta con el Track ID del envío.
     * @throws UnexpectedValueException Si el RUT de la empresa es inválido.
     * @throws SendAecException Si hay algún error al enviar el AEC.
     */
    public function send(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        string $emailNotif,
        ?int $retry = null
    ): SendAecResponse {
        // Crear string del documento XML en ISO-8859-1 (requerido por el SII).
        $xml = $doc->setEncoding('ISO-8859-1')->saveXml();
        if (empty($xml) || $xml === '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n") {
            throw new SendAecException(
                'El XML del AEC que se desea enviar al SII no puede ser vacío.'
            );
        }

        // Validar y descomponer el RUT de la empresa cedente.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Crear el archivo temporal con el XML del AEC.
        $filepath = $this->createXmlFile($xml);
        $filename = $company . '_' . basename($filepath);

        // Preparar los datos que se enviarán mediante POST al SII.
        // A diferencia del envío de DTE, el RTC no requiere el RUT del usuario
        // que envía (rutSender/dvSender), sino un email de contacto del cedente.
        $data = [
            'emailNotif' => $emailNotif,
            'rutCompany' => $rutCompany,
            'dvCompany' => $dvCompany,
            'archivo' => curl_file_create($filepath, 'application/xml', $filename),
        ];

        // Resolver el valor de $retry.
        $retry = $request->getReintentos($retry);

        // Realizar la solicitud al SII.
        $xmlResponse = $this->uploadAec($request, $data, $retry);

        // Eliminar el archivo temporal.
        unlink($filepath);

        // Procesar la respuesta del SII.
        $this->validateResponse($xmlResponse);

        return new SendAecResponse($xmlResponse);
    }

    /**
     * Valida la respuesta del SII al enviar el AEC.
     *
     * Códigos de estado:
     *   0  → Envío recibido OK.
     *   1  → RUT autenticado no tiene permiso para enviar en empresa cedente.
     *   2  → Error en tamaño del archivo enviado.
     *   4  → Faltan parámetros de entrada.
     *   5  → Error de autenticación (TOKEN inválido, no existe o expirado).
     *   6  → Empresa no es DTE.
     *   9  → Error interno del SII.
     *   10 → Error interno del SII.
     *
     * @param array $response Arreglo decodificado de la respuesta XML del SII.
     * @throws SendAecException Si el STATUS indica un error.
     */
    private function validateResponse(array $response): void
    {
        $inner = reset($response);
        $data = is_array($inner) ? $inner : $response;

        $status = $data['STATUS'] ?? null;

        if ($status === null) {
            throw new SendAecException(
                'La respuesta del envío del AEC al SII no trae un código de estado válido.'
            );
        }

        if ((int) $status === 0) {
            return;
        }

        $messages = [
            1 => 'El RUT autenticado no tiene permiso para enviar en la empresa cedente.',
            2 => 'Error en el tamaño del archivo enviado.',
            4 => 'Faltan parámetros de entrada en la solicitud al RTC.',
            5 => 'Error de autenticación: TOKEN inválido, no existe o está expirado.',
            6 => 'La empresa no está autorizada como emisor de DTE.',
            9 => 'Error interno en los servidores del SII.',
            10 => 'Error interno en los servidores del SII.',
        ];

        $message = $messages[(int) $status] ?? sprintf(
            'El SII rechazó el AEC con el código de estado desconocido "%s".',
            $status
        );

        throw new SendAecException($message);
    }

    /**
     * Sube el AEC al endpoint del RTC del SII.
     *
     * Emula el formulario web en:
     *   - Producción:    https://palena.sii.cl/cgi_rtc/RTC/RTCDocum.cgi?2
     *   - Certificación: https://maullin.sii.cl/cgi_rtc/RTC/RTCDocum.cgi?2
     *
     * @param SiiRequestInterface $request
     * @param array $data Campos del formulario multipart, incluido el archivo.
     * @param int $retry Número de reintentos.
     * @return array Respuesta del SII decodificada como arreglo.
     * @throws SiiRtcException Si no se puede autenticar o si el HTTP falla.
     */
    private function uploadAec(
        SiiRequestInterface $request,
        array $data,
        int $retry
    ): array {
        $url = $request->getAmbiente()->getUrl('/cgi_rtc/RTC/RTCAnotEnvio.cgi');
        $token = $this->siiLazyWorker->authenticate($request);

        $headers = [
            'User-Agent: Mozilla/5.0 (compatible; PROG 1.0; +https://www.libredte.cl)',
            'Cookie: TOKEN=' . $token,
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (!$request->getVerificarSsl()) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $responseBody = null;
        for ($i = 0; $i < $retry; $i++) {
            $responseBody = curl_exec($curl);

            if ($responseBody && $responseBody !== 'Error 500') {
                break;
            }

            usleep(200000 * $retry);
        }

        if (!$responseBody || $responseBody === 'Error 500') {
            $message = 'Falló el envío del AEC al SII. ';
            $message .= !$responseBody
                ? curl_error($curl)
                : 'El SII tiene problemas en sus servidores (Error 500).';
            throw new SendAecException($message);
        }

        // Parsear la respuesta XML del SII como arreglo.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml((string) $responseBody);

        $decoded = [];
        foreach ($xmlDocument->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $decoded[$node->nodeName] = [];
                foreach ($node->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE) {
                        $decoded[$node->nodeName][$child->nodeName] = $child->textContent;
                    }
                }
            }
        }

        return $decoded;
    }

    /**
     * Crea un archivo temporal con el XML del AEC.
     *
     * @param string $xml Contenido XML del AEC (debe incluir declaración).
     * @return string Ruta al archivo temporal creado.
     */
    private function createXmlFile(string $xml): string
    {
        if (!str_contains($xml, '<?xml')) {
            $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . $xml;
        }

        $tempDir = sys_get_temp_dir();
        $prefix = 'libredte_aec_for_upload_to_sii_rtc_';
        $filepath = tempnam($tempDir, $prefix);
        $realFilepath = $filepath . '.xml';
        rename($filepath, $realFilepath);
        file_put_contents($realFilepath, $xml);

        return $realFilepath;
    }
}

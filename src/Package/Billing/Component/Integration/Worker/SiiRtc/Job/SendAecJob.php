<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
     * @param int|null $retries Intentos máximos al enviar.
     * @return SendAecResponse Respuesta con el Track ID del envío.
     * @throws UnexpectedValueException Si el RUT de la empresa es inválido.
     * @throws SendAecException Si hay algún error al enviar el AEC.
     */
    public function send(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        string $emailNotif,
        ?int $retries = null
    ): SendAecResponse {
        // Crear string del documento XML en ISO-8859-1 (requerido por el SII).
        $xml = $doc->setEncoding('ISO-8859-1')->saveXml();
        if (empty($xml) || $xml === '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n") {
            throw new SendAecException(
                'The AEC XML to send to the SII cannot be empty.'
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

        // Resolver el valor de $retries.
        $retries = $request->getRetries($retries);

        // Realizar la solicitud al SII.
        $xmlResponse = $this->uploadAec($request, $data, $retries);

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
                'The SII AEC upload response does not include a valid status code.'
            );
        }

        if ((int) $status === 0) {
            return;
        }

        switch ((int) $status) {
            case 1:
                throw new SendAecException(
                    'The authenticated RUT has no permission to send on behalf of the assigning company.'
                );
            case 2:
                throw new SendAecException('Uploaded file size error.');
            case 4:
                throw new SendAecException('Missing input parameters in the RTC request.');
            case 5:
                throw new SendAecException('Authentication error: invalid, missing or expired TOKEN.');
            case 6:
                throw new SendAecException('The company is not authorized as a DTE issuer.');
            case 9:
            case 10:
                throw new SendAecException('Internal error on SII servers.');
            default:
                throw new SendAecException([
                    'The SII rejected the AEC with unknown status code "{status}".',
                    'status' => (string) $status,
                ]);
        }
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
     * @param int $retries Número de reintentos.
     * @return array Respuesta del SII decodificada como arreglo.
     * @throws SiiRtcException Si no se puede autenticar o si el HTTP falla.
     */
    private function uploadAec(
        SiiRequestInterface $request,
        array $data,
        int $retries
    ): array {
        $url = $request->getEnvironment()->getUrl('/cgi_rtc/RTC/RTCAnotEnvio.cgi');
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

        if (!$request->getVerifySsl()) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $responseBody = null;
        for ($i = 0; $i < $retries; $i++) {
            $responseBody = curl_exec($curl);

            if ($responseBody && $responseBody !== 'Error 500') {
                break;
            }

            // El reitento será con "exponential backoff", por lo que se
            // hace una pausa de 0.5 * $retries segundos antes de volver a
            // intentar llamar a la función del servicio web.
            usleep(500000 * $retries);
        }

        if (!$responseBody || $responseBody === 'Error 500') {
            throw new SendAecException([
                'Failed to send the AEC to the SII. {detail}',
                'detail' => !$responseBody
                    ? curl_error($curl)
                    : 'The SII servers are having problems (Error 500).',
            ]);
        }

        // Parsear la respuesta XML del SII como arreglo.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml((string) $responseBody);

        $decoded = [];
        foreach ($xmlDocument->getDomDocument()->childNodes as $node) {
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

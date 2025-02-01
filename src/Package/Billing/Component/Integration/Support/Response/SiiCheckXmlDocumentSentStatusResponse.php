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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response;

use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;

/**
 * Respuesta de la consulta de estado de un documento subido al SII.
 *
 * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf
 */
class SiiCheckXmlDocumentSentStatusResponse extends AbstractSiiWsdlResponse
{
    /**
     * Estados de salida.
     *
     * El resultado de la consulta al SII puede arrojar uno de estos estados.
     */
    private const STATUSES = [
        'RSC' => 'Rechazado por Error en Schema',
        'SOK' => 'Schema Validado',
        'CRT' => 'Carátula OK',
        'RFR' => 'Rechazado por Error en Firma',
        'FOK' => 'Firma de Envió Validada',
        'PDR' => 'Envió en Proceso',
        'RCT' => 'Rechazado por Error en Carátula',
        'EPR' => 'Envío Procesado',
    ];

    /**
     * Estados de salida por ERROR.
     *
     * El resultado de la consulta al SII puede arrojar uno de estos estados de
     * error.
     */
    private const ERRORS = [
        // Otros Errores.
        'ESTADO' => [
            '-1' => 'ERROR: RETORNO CAMPO ESTADO, NO EXISTE',
            '-2' => 'ERROR RETORNO',
            '-3' => 'ERROR: RUT USUARIO NO EXISTE',
            '-4' => 'ERROR OBTENCION DE DATOS',
            '-5' => 'ERROR RETORNO DATOS',
            '-6' => 'ERROR: USUARIO NO AUTORIZADO',
            '-7' => 'ERROR RETORNO DATOS',
            '-8' => 'ERROR: RETORNO DATOS',
            '-9' => 'ERROR: RETORNO DATOS',
            '-10' => 'ERROR: VALIDA RUT USUARIO',
            '-11' => 'ERR_CODE, SQL_CODE, SRV_CODE',
            '-12' => 'ERROR: RETORNO CONSULTA',
            '-13' => 'ERROR RUT USUARIO NULO',
            '-14' => 'ERROR XML RETORNO DATOS',
            //'OTRO' => 'No documentado.',
        ],
        // Errores por autenticación.
        'TOKEN' => [
            '001' => 'Cookie Inactivo (o Token Inactivo)',
            '002' => 'Token Inactivo',
            '003' => 'Token No Existe',
        ],
        // Errores de consulta.
        'SRV_CODE' => [
            0 => 'Todo Ok',
            1 => 'Error en Entrada',
            2 => 'Error SQL',
        ],
        'SQL_CODE' => [
            0 => 'Schema Validado',
            //'OTRO' => 'Código de Oracle',
        ],
        'ERR_CODE' => [
            0 => 'Se retorna el estado',
            1 => 'El envío no es de la Empresa, faltan parámetros de entrada.',
            2 => 'Error de Proceso',
        ],
    ];

    /**
     * Obtiene los datos normalizados de la respuesta.
     *
     * @return array Datos normalizados de la respuesta del SII.
     */
    public function getData(): array
    {
        // Si no existen los datos normalizados de la respuesta se normalizan.
        if (!isset($this->data)) {
            // Si hay número de atención se normaliza.
            [$number, $datetime] = !empty($this->headers['NUM_ATENCION'])
                ? $this->parseNumeroAtencion($this->headers['NUM_ATENCION'])
                : null
            ;

            // Determinar Track ID.
            $track_id = $this->headers['TRACKID']
                ?? $this->requestData['TrackId']
                ?? null
            ;

            // RUT de la empresa que realizó la consulta del envío.
            $company = isset($this->requestData['Rut']) && isset($this->requestData['Dv'])
                ? $this->requestData['Rut'] . '-' . $this->requestData['Dv']
                : null
            ;

            // Normalizar el estado de la consulta del envío.
            [$status, $error, $description] = $this->parseEstado($this->headers);

            // Normalizar los documentos.
            $documents = $this->parseDocumentos($this->body);
            $resume = $this->calculateResume($documents);

            // Armar los datos normalizados.
            $this->data = [
                'query_number' => $number ?? null,
                'query_datetime' => $datetime ?? null,
                'track_id' => $track_id,
                'company' => $company,
                'status' => $status,
                'error' => $error,
                'description' => $description,
                'resume' => $resume,
                'documents' => $documents,
                'token' => $this->requestData['Token'] ?? null,
            ];
        }

        // Entregar los datos de la respuesta del envío del documento al SII.
        return $this->data;
    }

    /**
     * Devuelve el estado de revisión del documento.
     *
     * @return string El estado de la revisión del documento.
     */
    public function getReviewStatus(): string
    {
        $data = $this->getData();

        if ($data['status'] === 'EPR') {
            if ($this->hasRejectedDocuments()) {
                return 'RCH - DTE Rechazado';
            }

            if ($this->hasRepairsDocuments()) {
                return 'RLV - DTE Aceptado con Reparos Leves';
            }
        }

        return $data['description']
            ? ($data['status'] . ' - ' . $data['description'])
            : $data['status']
        ;
    }

    /**
     * Devuelve el detalle de la revisión del documento.
     *
     * @return string|null El detalle de la revisión o `null` si no existe.
     */
    public function getReviewDetail(): ?string
    {
        $data = $this->getData();

        if ($data['status'] === 'EPR') {
            if ($this->allDocumentsAreAccepted()) {
                return 'DTE aceptado';
            }
        }

        return null;
    }

    /**
     * Verifica si todos los documentos fueron aceptados.
     *
     * @return bool True si todos los documentos fueron aceptados.
     */
    private function allDocumentsAreAccepted(): bool
    {
        $data = $this->getData();

        return $data['resume']['reported'] === $data['resume']['accepted'];
    }

    /**
     * Verifica si hay documentos rechazados.
     *
     * @return bool True si existen documentos rechazados.
     */
    private function hasRejectedDocuments(): bool
    {
        $data = $this->getData();

        return $data['resume']['rejected'] > 0;
    }

    /**
     * Verifica si hay documentos con reparos.
     *
     * @return bool True si existen documentos con reparos.
     */
    private function hasRepairsDocuments(): bool
    {
        $data = $this->getData();

        return $data['resume']['repairs'] > 0;
    }

    /**
     * Parsea el estado de la respuesta del SII.
     *
     * @param array $headers Encabezados de la respuesta.
     * @return array Arreglo con el estado, error y descripción.
     */
    private function parseEstado(array $headers): array
    {
        // Asignar el código del estado y asumir que hubo error en el proceso
        // de envío del documento al SII.
        $status = $headers['ESTADO'];
        $error = true;
        $description = null;

        // Verificar si el estado es uno de los estados "normales", o sea, no
        // es un estado de error en el envío. Se valida esto primero pues es lo
        // que normalmente debería entregar el SII en la respuesta.
        if (isset(self::STATUSES[$status])) {
            $error = $status[0] === 'R';
            $description = $headers['GLOSA'] ?? null;
        }

        // Si el estado es un error de token se asigna.
        elseif (isset(self::ERRORS['TOKEN'][$status])) {
            //$description = self::ERRORS['TOKEN'][$code];
            $description = $headers['GLOSA'] ?? null;
        }

        // El error es uno de los números negativos. Donde hay 3 opciones:
        // El error es -11 y puede ser uno de los 3 que detalla -11.
        elseif ($status == '-11') {
            $errors = [];
            foreach (['SRV_CODE', 'SQL_CODE', 'ERR_CODE'] as $errorType) {
                if (isset($headers[$errorType])) {
                    $errors[] = self::ERRORS[$errorType][$headers[$errorType]]
                        ?? sprintf(
                            'Error %s #%s',
                            $errorType,
                            $headers[$errorType]
                        )
                    ;
                }
            }
            $description = implode(' ', $errors);
        }

        // El error es uno de los definidos como error de estado (excepto -11).
        elseif (isset(self::ERRORS['ESTADO'][$status])) {
            $description = self::ERRORS['ESTADO'][$status];
        }

        // El error es de estado pero no se tiene el código de error registrado.
        else {
            $description = sprintf(
                'Error desconocido código #%s al subir el XML.',
                $status
            );
        }

        // Entregar valores determinados para el error.
        return [$status, $error, $description];
    }

    /**
     * Parsea los documentos de la respuesta del SII.
     *
     * @param array $body Cuerpo de la respuesta del SII.
     * @return array Arreglo con los documentos normalizados.
     */
    private function parseDocumentos(array $body): array
    {
        // No vienen documentos en el cuerpo.
        if (empty($body['TIPO_DOCTO'])) {
            return [];
        }

        // Índices donde vienen los datos de los documentos.
        $keysMap = [
            'TIPO_DOCTO'  => 'type',
            'INFORMADOS'  => 'reported',
            'ACEPTADOS'   => 'accepted',
            'RECHAZADOS'  => 'rejected',
            'REPAROS'     => 'repairs',
        ];
        $keys = array_keys($keysMap);

        // Normalizar a arreglo de documentos (y otros datos) si no lo son.
        if (!is_array($body[$keys[0]])) {
            foreach ($keys as $key) {
                $body[$key] = [$body[$key] ?? 0];
            }
        }

        // Iterar y armar estado de los documentos.
        $n_documents = count($body[$keys[0]]);
        $documents = [];
        for ($i = 0; $i < $n_documents; $i++) {
            $document = [];
            foreach ($keysMap as $source => $destination) {
                $document[$destination] = $body[$source][$i];
            }
            $documents[$body[$keys[0]][$i]] = $document;
        }

        // Entregar documentos encontrados.
        return $documents;
    }

    /**
     * Calcula el resumen de los documentos procesados.
     *
     * @param array $documents Arreglo con los documentos.
     * @return array Resumen con el total de reportados, aceptados, rechazados
     * y reparos.
     */
    private function calculateResume(array $documents): array
    {
        $resume = [
            'reported' => 0,
            'accepted' => 0,
            'rejected' => 0,
            'repairs' => 0,
        ];
        $keys = array_keys($resume);

        foreach ($documents as $document) {
            foreach ($keys as $key) {
                $resume[$key] += ($document[$key] ?? 0);
            }
        }

        return $resume;
    }
}

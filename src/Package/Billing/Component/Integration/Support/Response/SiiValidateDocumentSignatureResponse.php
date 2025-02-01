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
 * Respuesta con el estado avanzado de un DTE aceptado por el SII.
 *
 * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/OIFE2006_QueryEstDteAv_MDE.pdf
 */
class SiiValidateDocumentSignatureResponse extends AbstractSiiWsdlResponse
{
    /**
     * Estados de salida.
     *
     * El resultado de la consulta al SII puede arrojar uno de estos estados.
     */
    private const STATUSES = [
        'DOK' => 'Documento Recibido por el SII. Datos Coinciden con los Registrados.',
        'DNK' => 'Documento Recibido por el SII pero Datos NO Coinciden con los registrados.',
        'FAU' => 'Documento No Recibido por el SII.',
        'FNA' => 'Documento No Autorizado.',
        'FAN' => 'Documento Anulado.',
        'EMP' => 'Empresa no autorizada a Emitir Documentos Tributarios Electrónicos',
        'TMD' => 'Existe Nota de Débito que Modifica Texto Documento.',
        'TMC' => 'Existe Nota de Crédito que Modifica Textos Documento.',
        'MMD' => 'Existe Nota de Débito que Modifica Montos Documento.',
        'MMC' => 'Existe Nota de Crédito que Modifica Montos Documento.',
        'AND' => 'Existe Nota de Débito que Anula Documento',
        'ANC' => 'Existe Nota de Crédito que Anula Documento',
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
            '-1' => 'ERROR: RETORNO CAMPO ESTADO',
            '-2' => 'ERROR RETORNO',
            '-3' => 'ERROR RETORNO',
            '-4' => 'ERROR RETORNO',
            //'OTRO' => 'No documentado.',
        ],
        // Errores por autenticación.
        'TOKEN' => [
            '001' => 'Cookie Inactivo (o token no existe)',
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
            '0' => 'Consulta procesada OK.',
            '1' => 'Token inactivo (expirado)',
            '2' => 'Token no existe',
            '3' => 'Error Interno (ver glosa)',
            '4' => 'Error Interno',
            '5' => 'Error parámetros de entrada (ver glosa)',
            '6' => 'Error Interno',
            '7' => 'Error Interno',
            '8' => 'Error Interno',
            '9' => 'Usuario no autorizado en empresa',
            '10' => 'Error Interno',
            '11' => 'Error Interno',
            '12' => 'Error Interno',
            '13' => 'Error Interno',
            '14' => 'Error Interno',
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

            // RUT de la empresa emisora del documento.
            $rutExists = isset($this->requestData['RutCompania'])
                && isset($this->requestData['DvCompania'])
            ;
            $company = $rutExists
                ? $this->requestData['RutCompania']
                    . '-' . $this->requestData['DvCompania']
                : null
            ;

            // RUT del receptor del documento.
            $rutExists = isset($this->requestData['RutReceptor'])
                && isset($this->requestData['DvReceptor'])
            ;
            $recipient = $rutExists
                ? $this->requestData['RutReceptor']
                    . '-' . $this->requestData['DvReceptor']
                : null
            ;

            // Normalizar el estado de la consulta del envío.
            [$status, $received, $description] = $this->parseEstado(
                $this->headers,
                $this->body
            );

            // Armar los datos normalizados.
            $this->data = [
                'query_number' => $number ?? null,
                'query_datetime' => $datetime ?? null,
                'company' => $company,
                'document' => $this->requestData['TipoDte'] ?? null,
                'number' => $this->requestData['FolioDte'] ?? null,
                'date' => $this->requestData['FechaEmisionDte'] ?? null,
                'total' => $this->requestData['MontoDte'] ?? null,
                'signature' => $this->requestData['FirmaDte'] ?? null,
                'recipient' => $recipient,
                'status' => $status,
                'received' => $received,
                'description' => $description,
                'token' => $this->requestData['Token'] ?? null,
            ];
        }

        // Entregar los datos de la respuesta del estado del DTE.
        return $this->data;
    }

    /**
     * Parsea el estado de la respuesta del SII.
     *
     * @param array $headers Encabezados de la respuesta.
     * @param array $body Cuerpo de la respuesta.
     * @return array Arreglo con el estado, error y descripción.
     */
    private function parseEstado(array $headers, array $body): array
    {
        // Asignar el código del estado y asumir que hubo error en la
        // validación del documento por parte del SII.
        $status = $headers['ESTADO'] ?? $body['ESTADO'] ?? $headers['SII:ESTADO'];
        $received = ($body['RECIBIDO'] ?? 'NO') === 'SI';
        $description = null;

        // Verificar si el estado es uno de los estados "normales", o sea, no
        // es un estado de error en el envío. Se valida esto primero pues es lo
        // que normalmente debería entregar el SII en la respuesta.
        if (isset(self::STATUSES[$status])) {
            $description = $headers['GLOSA_ERR'] ?? null;
        }

        // Si el estado es un error de token se asigna.
        elseif (isset(self::ERRORS['TOKEN'][$status])) {
            //$description = self::ERRORS['TOKEN'][$code];
            $description = $headers['SII:GLOSA'] ?? null;
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
        return [$status, $received, $description];
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte;

use Derafu\Enum\Contract\StatusInterface;
use Derafu\Enum\Status;
use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;

/**
 * Respuesta con el estado de un DTE aceptado por el SII.
 *
 * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_dte.pdf
 */
class ValidateDocumentResponse extends AbstractSiiWsdlResponse implements JsonSerializable
{
    /**
     * Estados de salida.
     *
     * El resultado de la consulta al SII puede arrojar uno de estos estados.
     */
    private const STATUSES = [
        // Todo ok.
        'DOK' => 'Documento recibido por el SII. Datos coinciden con los registrados.',
        // Errores.
        'DNK' => 'Documento recibido por el SII pero datos no coinciden con los registrados.',
        'FAU' => 'Folio del documento no recibido por el SII.',
        'FNA' => 'Folio no autorizado por el SII.',
        'FAN' => 'Folio anulado antes de ser enviado al SII.',
        'EMP' => 'Empresa no autorizada a emitir documentos tributarios electrónicos',
        // Notas.
        'TMD' => 'Existe nota de débito que modifica texto del documento.',
        'TMC' => 'Existe nota de crédito que modifica texto del documento.',
        'MMD' => 'Existe nota de débito que modifica montos del documento.',
        'MMC' => 'Existe nota de crédito que modifica montos del documento.',
        'AND' => 'Existe nota de débito que anula documento',
        'ANC' => 'Existe nota de crédito que anula documento',
    ];

    /**
     * Colores de salida.
     *
     * El resultado de la consulta al SII puede arrojar uno de estos colores.
     */
    private const STATUSES_TYPES = [
        // Todo ok.
        'DOK' => 'success',
        // Errores.
        'DNK' => 'danger',
        'FAU' => 'danger',
        'FNA' => 'danger',
        'FAN' => 'danger',
        'EMP' => 'danger',
        // Notas.
        'TMD' => 'info',
        'TMC' => 'info',
        'MMD' => 'info',
        'MMC' => 'info',
        'AND' => 'info',
        'ANC' => 'info',
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
            '0' => self::STATUSES_TYPES['DOK'],
            '1' => self::STATUSES_TYPES['DNK'],
            '3' => self::STATUSES_TYPES['FAU'],
            '4' => self::STATUSES_TYPES['FNA'],
            '5' => self::STATUSES_TYPES['FAN'],
            '6' => self::STATUSES_TYPES['EMP'],
            '10' => self::STATUSES_TYPES['TMD'],
            '11' => self::STATUSES_TYPES['TMC'],
            '12' => self::STATUSES_TYPES['MMD'],
            '13' => self::STATUSES_TYPES['MMC'],
            '14' => self::STATUSES_TYPES['AND'],
            '15' => self::STATUSES_TYPES['ANC'],
        ],
    ];

    /**
     * Obtiene el tipo de salida para un estado.
     *
     * @return StatusInterface Tipo de salida.
     */
    public function getStatusType(): StatusInterface
    {
        return Status::tryFrom(self::STATUSES_TYPES[$this->getStatus()] ?? 'info');
    }

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
                $this->headers
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
     * @return array Arreglo con el estado, error y descripción.
     */
    private function parseEstado(array $headers): array
    {
        // Asignar el código del estado y asumir que hubo error en la
        // validación del documento por parte del SII.
        $status = $headers['ESTADO'];
        $received = null;
        $description = null;

        // Verificar si el estado es uno de los estados "normales", o sea, no
        // es un estado de error en el envío. Se valida esto primero pues es lo
        // que normalmente debería entregar el SII en la respuesta.
        if (isset(self::STATUSES[$status])) {
            $received = !in_array($status[0], ['F', 'E']);
            $description = self::STATUSES[$status];
            // Se descarta la glosa del SII en $headers['GLOSA_ERR']
        }

        // Si el estado es un error de token se asigna.
        elseif (isset(self::ERRORS['TOKEN'][$status])) {
            $description = self::ERRORS['TOKEN'][$status];
            // Se descarta la glosa del SII en $headers['GLOSA']
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

    /**
     * Entrega los datos de la respuesta como un arreglo.
     *
     * @return array Datos de la respuesta.
     */
    public function toArray(): array
    {
        return $this->getData();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();
        unset($data['token']);

        return $data;
    }
}

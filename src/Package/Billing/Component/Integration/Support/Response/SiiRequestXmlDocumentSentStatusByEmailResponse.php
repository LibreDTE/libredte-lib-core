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
 * Respuesta de la solicitud de correo de estado de un documento subido al SII.
 *
 * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/OIFE2005_wsDTECorreo_MDE.pdf
 */
class SiiRequestXmlDocumentSentStatusByEmailResponse extends AbstractSiiWsdlResponse
{
    /**
     * Estados de salida y error.
     *
     * El resultado de la consulta al SII puede arrojar uno de estos estados.
     *
     * En otros servicios los estados se separan en estados "normales" y
     * "errores", acá solo hay una lista donde solo "0" es el estado "normal" y
     * todo los demás son códigos de estado de errores.
     */
    private const STATUSES = [
        '0' => 'Requerimiento recibido OK.',
        // Errores de Datos.
        '101' => 'Error en dígito verificador del Rut de la empresa.',
        '102' => 'Faltan datos de entrada.',
        '105' => 'Error Track ID no existe.',
        '106' => 'Usuario autenticado no tiene permisos sobre Empresa.',
        '114' => 'Envío solicitado no ha concluido su validación, por lo tanto no existe correo.',
        // Errores por autenticación.
        '104' => 'Token Inactivo o No Existe',
        // Otros Errores.
        '103' => 'Error Interno.',
        '107' => 'Error Interno.',
        '108' => 'Error Interno.',
        '110' => 'Error Interno.',
        '111' => 'Error Interno.',
        '112' => 'Error Interno.',
        '113' => 'Error Interno.',
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
            // RUT de la empresa emisora del documento.
            $rutExists = isset($this->requestData['RutEmpresa'])
                && isset($this->requestData['DvEmpresa'])
            ;
            $company = $rutExists
                ? $this->requestData['RutEmpresa']
                    . '-' . $this->requestData['DvEmpresa']
                : null
            ;

            // Normalizar el estado de la consulta del envío.
            [$status, $description] = $this->parseEstado($this->headers);

            // Armar los datos normalizados.
            $this->data = [
                'company' => $company,
                'track_id' => $this->requestData['TrackId'] ?? null,
                'status' => $status,
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
        // Asignar el código del estado.
        $status = $headers['SII:ESTADO'];
        $description = $headers['SII:GLOSA'] ?? null;

        // Si existe una glosa para el estado se reemplaza la recibida del SII.
        if (isset(self::STATUSES[$status])) {
            $description = self::STATUSES[$status];
        }

        // Entregar valores determinados para el error.
        return [$status, $description];
    }
}

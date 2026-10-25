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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;

/**
 * Reglas de normalización para documentos de exportación.
 */
trait NormalizeExportacionTrait
{
    /**
     * Normaliza los datos de exportación de un documento.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    protected function normalizeExportacion(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Agregar modalidad de venta por defecto si no existe.
        if (
            empty($data['Encabezado']['Transporte']['Aduana']['CodModVenta'])
            && (
                !isset($data['Encabezado']['IdDoc']['IndServicio'])
                || !in_array($data['Encabezado']['IdDoc']['IndServicio'], [3, 4, 5])
            )
        ) {
            $data['Encabezado']['Transporte']['Aduana']['CodModVenta'] = 1;
        }

        // Quitar campos que no son parte del documento de exportacion.
        $data['Encabezado']['Receptor']['CmnaRecep'] = false;

        // Colocar forma de pago de exportación.
        if (!empty($data['Encabezado']['IdDoc']['FmaPago'])) {
            $formas = [3 => 21];
            if (isset($formas[$data['Encabezado']['IdDoc']['FmaPago']])) {
                $data['Encabezado']['IdDoc']['FmaPagExp'] =
                    $formas[$data['Encabezado']['IdDoc']['FmaPago']]
                ;
            }
            $data['Encabezado']['IdDoc']['FmaPago'] = false;
        }

        // Si es entrega gratuita se coloca el tipo de cambio en CLP en 0 para
        // que total sea 0.
        if (
            !empty($data['Encabezado']['IdDoc']['FmaPagExp'])
            && $data['Encabezado']['IdDoc']['FmaPagExp'] == 21
            && !empty($data['Encabezado']['OtraMoneda'])
        ) {
            if (!isset($data['Encabezado']['OtraMoneda'][0])) {
                $data['Encabezado']['OtraMoneda'] = [
                    $data['Encabezado']['OtraMoneda'],
                ];
            }
            foreach ($data['Encabezado']['OtraMoneda'] as &$OtraMoneda) {
                if ($OtraMoneda['TpoMoneda'] === 'PESO CL') {
                    $OtraMoneda['TpoCambio'] = 0;
                }
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}

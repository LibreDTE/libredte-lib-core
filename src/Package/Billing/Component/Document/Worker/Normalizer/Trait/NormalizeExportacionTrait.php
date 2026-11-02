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

use Derafu\Array\ArrayPath;
use Derafu\Array\ArrayWriter;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
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
        $data = $bag->requireNormalizedData();
        $writer = ArrayWriter::fromReference($data);

        // Agregar modalidad de venta por defecto si no existe.
        $indServicio = $writer->getReader()->get('Encabezado.IdDoc.IndServicio', ValueType::mixed());
        if (
            empty($writer->getReader()->get('Encabezado.Transporte.Aduana.CodModVenta', ValueType::mixed()))
            && (
                $indServicio === null
                || !in_array($indServicio, [3, 4, 5])
            )
        ) {
            $writer->set('Encabezado.Transporte.Aduana.CodModVenta', 1);
        }

        // Quitar campos que no son parte del documento de exportacion.
        $writer->set('Encabezado.Receptor.CmnaRecep', null);

        // Colocar forma de pago de exportación.
        $fmaPago = $writer->getReader()->get('Encabezado.IdDoc.FmaPago', ValueType::mixed());
        if (!empty($fmaPago)) {
            $formas = [3 => 21];
            $formaPago = ValueCaster::toString($fmaPago);
            if ($formaPago !== null && isset($formas[$formaPago])) {
                $writer->set('Encabezado.IdDoc.FmaPagExp', $formas[$formaPago]);
            }
            $writer->set('Encabezado.IdDoc.FmaPago', null);
        }

        // Si es entrega gratuita se coloca el tipo de cambio en CLP en 0 para
        // que total sea 0.
        $fmaPagExp = $writer->getReader()->get('Encabezado.IdDoc.FmaPagExp', ValueType::mixed());
        $otraMoneda = $writer->getReader()->get('Encabezado.OtraMoneda', ValueType::mixed());
        if (
            !empty($fmaPagExp)
            && $fmaPagExp == 21
            && !empty($otraMoneda)
        ) {
            // Una sola moneda se decodifica como un arreglo y no como una
            // lista, se normaliza siempre a una lista.
            $data = ArrayPath::wrap($data, 'Encabezado.OtraMoneda');
            $monedas = $writer->getReader()->get(
                'Encabezado.OtraMoneda',
                ValueType::arrayOf(ValueType::map()),
                []
            );
            foreach ($monedas as $key => $moneda) {
                if (($moneda['TpoMoneda'] ?? null) === 'PESO CL') {
                    $writer->set('Encabezado.OtraMoneda.' . $key . '.TpoCambio', 0);
                }
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}

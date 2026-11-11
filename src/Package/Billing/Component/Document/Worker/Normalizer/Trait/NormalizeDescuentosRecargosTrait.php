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
use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils;

/**
 * Reglas de normalización para los descuentos y recargos de un documento.
 */
trait NormalizeDescuentosRecargosTrait
{
    /**
     * Aplica los descuentos y recargos generales respectivos a los montos que
     * correspondan según el indicador del descuento o recargo.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     * @todo Revisar si existen casos de boletas afectas con algún item exento
     * donde el descuento se podría estar aplicando mal.
     */
    protected function normalizeDescuentosRecargos(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();
        $writer = ArrayWriter::fromReference($data);

        if (!empty($writer->getReader()->get('DscRcgGlobal', ValueType::mixed()))) {
            // Un solo descuento o recargo se decodifica como un arreglo y no
            // como una lista, se normaliza siempre a una lista.
            $data = ArrayPath::wrap($data, 'DscRcgGlobal');

            $items = $writer->getReader()->get(
                'DscRcgGlobal',
                ValueType::arrayOf(ValueType::map()),
                []
            );
            $tipoDte = $writer->getReader()->get('Encabezado.IdDoc.TipoDTE', ValueType::mixed());
            $isExportacion = $bag->requireTipoDocumento()->isExportacion();

            foreach ($items as $key => $item) {
                $dr = array_merge([
                    'NroLinDR' => null,
                    'TpoMov' => null,
                    'GlosaDR' => null,
                    'TpoValor' => null,
                    'ValorDR' => null,
                    'ValorDROtrMnda' => null,
                    'IndExeDR' => null,
                ], $item);
                if ($isExportacion) {
                    $dr['IndExeDR'] = 1;
                }

                // El descuento o recargo queda con sus valores por defecto
                // aunque más abajo se omita.
                $path = 'DscRcgGlobal.' . $key;
                $writer->set($path, $dr);

                // Determinar a que aplicar el descuento/recargo.
                if (!isset($dr['IndExeDR'])) {
                    $monto = $tipoDte === 39
                        ? 'MntTotal'
                        : 'MntNeto'
                    ;
                } else {
                    $monto = $dr['IndExeDR'] == 1
                        ? 'MntExe'  # IndExeDR == 1
                        : 'MontoNF' # IndExeDR == 2
                    ;
                }
                $montoPath = 'Encabezado.Totales.' . $monto;

                // Si no hay monto al que aplicar el descuento se omite.
                if (empty($writer->getReader()->get($montoPath, ValueType::mixed()))) {
                    continue;
                }

                // Calcular valor del descuento o recargo.
                $tpoMoneda = $writer->getReader()->getAsString('Encabezado.Totales.TpoMoneda');
                if ($dr['TpoValor'] === '$') {
                    $dr['ValorDR'] = Utils::round(
                        ArrayReader::fromArray($dr, $path)->requireAsNumber('ValorDR'),
                        $tpoMoneda,
                        2
                    );
                    $writer->set($path . '.ValorDR', $dr['ValorDR']);
                }
                $valorDR = ArrayReader::fromArray($dr, $path);
                $total = $writer->getReader()->getAsNumber($montoPath) ?? 0;
                $valor = $dr['TpoValor'] === '%'
                    ? Utils::round(
                        ($valorDR->requireAsNumber('ValorDR') / 100) * $total,
                        $tpoMoneda
                    )
                    : ($valorDR->getAsNumber('ValorDR') ?? 0)
                ;

                // Aplicar descuento.
                if ($dr['TpoMov'] === 'D') {
                    $total -= $valor;
                }

                // Aplicar recargo.
                elseif ($dr['TpoMov'] === 'R') {
                    $total += $valor;
                }
                $writer->set($montoPath, Utils::round($total, $tpoMoneda));

                // Si el descuento global se aplica a una boleta exenta se
                // copia el valor exento al total.
                if (
                    $tipoDte === 41
                    && isset($dr['IndExeDR'])
                    && $dr['IndExeDR'] == 1
                ) {
                    $writer->set(
                        'Encabezado.Totales.MntTotal',
                        $writer->getReader()->get('Encabezado.Totales.MntExe', ValueType::mixed())
                    );
                }
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($writer->getReader()->toMap());
    }
}

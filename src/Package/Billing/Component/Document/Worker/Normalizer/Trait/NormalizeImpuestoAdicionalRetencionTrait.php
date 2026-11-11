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

use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ImpuestoAdicionalRetencionRepository;

/**
 * Reglas de normalización para documentos con impuesto adicional o retención.
 */
trait NormalizeImpuestoAdicionalRetencionTrait
{
    /**
     * Calcula los montos de impuestos adicionales o retenciones.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    protected function normalizeImpuestoAdicionalRetencion(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();
        $writer = ArrayWriter::fromReference($data);
        $imptoRetenPath = 'Encabezado.Totales.ImptoReten';

        // Copiar montos.
        $montos = [];
        $detalle = $writer->getReader()->get('Detalle', ValueType::arrayOf(ValueType::map()), []);
        foreach ($detalle as $d) {
            if (!empty($d['CodImpAdic'])) {
                $codigo = ValueCaster::requireInt(ValueCaster::toInt($d['CodImpAdic']));
                if (!isset($montos[$codigo])) {
                    $montos[$codigo] = 0;
                }
                $montos[$codigo] += ArrayReader::fromArray($d)->getAsNumber('MontoItem') ?? 0;
            }
        }

        // Si hay montos y no hay total para impuesto retenido se arma.
        if (!empty($montos)) {
            $imptoReten = $writer->getReader()->get($imptoRetenPath, ValueType::mixed());
            if (!is_array($imptoReten)) {
                $writer->set($imptoRetenPath, []);
            } elseif (!isset($imptoReten[0])) {
                $writer->set($imptoRetenPath, [$imptoReten]);
            }
        }

        // Armar impuesto adicional o retención en los totales.
        foreach ($montos as $codigo => $neto) {
            // Buscar si existe el impuesto en los totales.
            $impuestos = $writer->getReader()->get(
                $imptoRetenPath,
                ValueType::arrayOf(ValueType::map()),
                []
            );
            $i = 0;
            foreach ($impuestos as $ImptoReten) {
                if (($ImptoReten['TipoImp'] ?? null) == $codigo) {
                    break;
                }
                $i++;
            }

            // Si no existe se crea.
            if (!isset($impuestos[$i])) {
                $writer->append($imptoRetenPath, [
                    'TipoImp' => $codigo,
                ]);
            }

            // Se normaliza.
            // El repositorio entrega una entidad "falsa" si el código no
            // existe, por lo que su find() nunca retorna null.
            $tasa = ValueCaster::requireObject(
                $this->repositoryManager->getRepository(
                    ImpuestoAdicionalRetencion::class
                ),
                ImpuestoAdicionalRetencionRepository::class
            )->find($codigo)->getTasa();
            $path = $imptoRetenPath . '.' . $i;
            $impuesto = array_merge([
                'TipoImp' => $codigo,
                'TasaImp' => $tasa,
                'MontoImp' => null,
            ], $writer->getReader()->requireMap($path));

            // Si el monto no existe se asigna.
            if ($impuesto['MontoImp'] === null) {
                $impuesto['MontoImp'] = round(
                    $neto * (ArrayReader::fromArray($impuesto)->getAsNumber('TasaImp') ?? 0) / 100
                );
            }
            $writer->set($path, $impuesto);
        }

        // Quitar los códigos que no existen en el detalle.
        $imptoReten = $writer->getReader()->get($imptoRetenPath, ValueType::mixed());
        if (is_array($imptoReten)) {
            $impuestos = ValueType::arrayOf(ValueType::map())->parse($imptoReten, $imptoRetenPath);
            $codigos = array_keys($montos);
            $n_impuestos = count($impuestos);
            for ($i = 0; $i < $n_impuestos; $i++) {
                if (!in_array($impuestos[$i]['TipoImp'], $codigos)) {
                    unset($impuestos[$i]);
                }
            }
            sort($impuestos);
            $writer->set($imptoRetenPath, $impuestos);
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($writer->getReader()->toMap());
    }
}

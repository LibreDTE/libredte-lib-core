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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroGuias;

use Derafu\Array\ArrayDuplicates;
use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Value\ValueParser;
use Derafu\Xml\Contract\XmlEncoderInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroGuias;

/**
 * Estrategia `libro_guias` del `BuilderWorker`.
 *
 * Construye el XML del Libro de Guías de Despacho a partir de los detalles
 * normalizados por el `LoaderWorker`.
 */
#[Strategy(name: 'libro_guias', worker: 'builder', component: 'book', package: 'billing')]
class BuilderStrategy extends AbstractStrategy implements BuilderStrategyInterface
{
    public function __construct(
        private XmlEncoderInterface $xmlEncoder
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function build(BookBagInterface $bag): BookInterface
    {
        $caratula = $bag->getCaratula();
        $detalles = $bag->getDetalle();

        // Ninguna guía puede estar repetida en el detalle: el esquema no lo
        // valida.
        ArrayDuplicates::assertUnique(
            $detalles,
            $bag->getTipo()->getDocumentKey(),
            ValueParser::parse(...)
        );

        // Calcular resumen del período.
        $resumenPeriodo = $this->calculateResumenPeriodo($detalles);

        // Construir ID del documento.
        $caratulaReader = self::reader($caratula);
        $rut = str_replace('-', '', $caratulaReader->getAsString('RutEmisorLibro') ?? '');
        $periodo = str_replace('-', '', $caratulaReader->getAsString('PeriodoTributario') ?? date('Y-m'));
        $id = sprintf('LibreDTE_LIBRO_GUIA_%s_%s_%s', $rut, $periodo, time());

        // Carátula normalizada.
        $caratulaNorm = array_merge([
            'RutEmisorLibro' => false,
            'RutEnvia' => false,
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => false,
            'NroResol' => false,
            'TipoLibro' => 'ESPECIAL',
            'TipoEnvio' => 'TOTAL',
            'FolioNotificacion' => null,
        ], $caratula);

        // Generar XML.
        $xmlDocument = $this->xmlEncoder->encode([
            'LibroGuia' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte LibroGuia_v10.xsd',
                    'version' => '1.0',
                ],
                'EnvioLibro' => [
                    '@attributes' => ['ID' => $id],
                    'Caratula' => $caratulaNorm,
                    'ResumenPeriodo' => $resumenPeriodo,
                    'Detalle' => $detalles ?: false,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ],
        ]);

        return new LibroGuias($xmlDocument);
    }

    /**
     * Calcula el resumen del período del libro de guías.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return array<string, mixed>
     */
    private function calculateResumenPeriodo(array $detalles): array
    {
        $annulledFolios = 0;
        $annulledGuides = 0;
        $salesCount = 0;
        $salesAmount = 0;

        // Traslados por tipo de operación, en el orden en que aparecen.
        $transfers = [];

        foreach ($detalles as $d) {
            $row = self::reader($d);
            $anulado = $row->getAsInt('Anulado') ?? 0;
            $tpoOper = $row->getAsInt('TpoOper') ?? 0;
            $amount = $row->getAsInt('MntTotal') ?? 0;

            if ($anulado === 1) {
                $annulledFolios++;
            } elseif ($anulado === 2) {
                $annulledGuides++;
            } elseif ($tpoOper === 1) {
                $salesCount++;
                $salesAmount += $amount;
            } elseif ($tpoOper !== 0) {
                $transfers[$tpoOper] ??= ['TpoTraslado' => $tpoOper, 'CantGuia' => 0, 'MntGuia' => 0];
                $transfers[$tpoOper]['CantGuia']++;
                $transfers[$tpoOper]['MntGuia'] += $amount;
            }
        }

        return [
            'TotFolAnulado' => $annulledFolios ?: false,
            'TotGuiaAnulada' => $annulledGuides ?: false,
            'TotGuiaVenta' => $salesCount,
            'TotMntGuiaVta' => $salesAmount,
            // Re-indexar traslados para XML.
            'TotTraslado' => $transfers === [] ? false : array_values($transfers),
        ];
    }

    /**
     * Entrega un lector de los datos. En el detalle y en la carátula `false` y el
     * texto vacío son lo que se pone cuando no hay un valor.
     *
     * @param array<array-key, mixed> $data
     */
    private static function reader(array $data): ArrayReader
    {
        return ArrayReader::fromArray(
            array_map(static fn (mixed $value): mixed => $value === false || $value === '' ? null : $value, $data)
        );
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\ResumenVentasDiarias;

use Derafu\Array\ArrayDuplicates;
use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Value\ValueParser;
use Derafu\Xml\Contract\XmlEncoderInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\ResumenVentasDiarias;

/**
 * Estrategia `resumen_ventas_diarias` del `BuilderWorker`.
 *
 * Construye el XML del Resumen de Ventas Diarias (ConsumoFolios) según el
 * esquema `ConsumoFolio_v10.xsd` del SII.
 */
#[Strategy(name: 'resumen_ventas_diarias', worker: 'builder', component: 'book', package: 'billing')]
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

        // Ningún documento puede estar repetido en el detalle: sus folios se
        // contarían dos veces.
        ArrayDuplicates::assertUnique(
            $detalles,
            $bag->getTipo()->getDocumentKey(),
            ValueParser::parse(...)
        );

        // Calcular fechas inicio y fin desde los detalles.
        $fchInicio = $this->calculateFechaInicial($detalles);
        $fchFinal = $this->calculateFechaFinal($detalles);

        // Calcular resumen agrupado por TpoDoc con rangos de folios.
        $resumen = $this->calculateResumen($detalles);

        // Construir carátula normalizada.
        $caratulaNorm = array_merge([
            '@attributes' => ['version' => '1.0'],
            'RutEmisor' => false,
            'RutEnvia' => false,
            'FchResol' => false,
            'NroResol' => false,
            'FchInicio' => $fchInicio,
            'FchFinal' => $fchFinal,
            'Correlativo' => false,
            'SecEnvio' => 1,
            'TmstFirmaEnv' => date('Y-m-d\TH:i:s'),
        ], $caratula, [
            'FchInicio' => $fchInicio,
            'FchFinal' => $fchFinal,
        ]);

        // Construir ID del documento.
        $rut = str_replace('-', '', self::reader($caratulaNorm)->getString('RutEmisor') ?? '');
        $fecha = str_replace('-', '', $fchInicio);
        $id = sprintf('LibreDTE_CONSUMO_FOLIO_%s_%s_%s', $rut, $fecha, time());

        // Generar XML (el tag raíz sigue siendo <ConsumoFolios> por esquema SII).
        $xmlDocument = $this->xmlEncoder->encode([
            'ConsumoFolios' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte ConsumoFolio_v10.xsd',
                    'version' => '1.0',
                ],
                'DocumentoConsumoFolios' => [
                    '@attributes' => ['ID' => $id],
                    'Caratula' => $caratulaNorm,
                    'Resumen' => $resumen,
                ],
            ],
        ]);

        return new ResumenVentasDiarias($xmlDocument);
    }

    /**
     * Calcula la fecha del primer documento de los detalles.
     *
     * Los detalles sin fecha no cuentan. Sin ninguna fecha es la de hoy.
     *
     * @param array<int, array<string, mixed>> $detalles
     */
    private function calculateFechaInicial(array $detalles): string
    {
        $fecha = null;
        foreach ($detalles as $d) {
            $fchDoc = self::reader($d)->getString('FchDoc');
            if ($fchDoc !== null && ($fecha === null || $fchDoc < $fecha)) {
                $fecha = $fchDoc;
            }
        }

        return $fecha ?? date('Y-m-d');
    }

    /**
     * Calcula la fecha del último documento de los detalles.
     *
     * Los detalles sin fecha no cuentan. Sin ninguna fecha es la de hoy.
     *
     * @param array<int, array<string, mixed>> $detalles
     */
    private function calculateFechaFinal(array $detalles): string
    {
        $fecha = null;
        foreach ($detalles as $d) {
            $fchDoc = self::reader($d)->getString('FchDoc');
            if ($fchDoc !== null && ($fecha === null || $fchDoc > $fecha)) {
                $fecha = $fchDoc;
            }
        }

        return $fecha ?? date('Y-m-d');
    }

    /**
     * Calcula el resumen agrupado por TpoDoc con rangos de folios utilizados.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return list<array<string, mixed>>
     */
    private function calculateResumen(array $detalles): array
    {
        $groups = [];
        foreach ($detalles as $d) {
            $row = self::reader($d);

            $tpoDoc = $row->getAsInt('TpoDoc');
            if ($tpoDoc === null || $tpoDoc === 0) {
                continue;
            }

            // La tasa es la del primer documento del tipo.
            $groups[$tpoDoc] ??= [
                'rate' => $row->getAsNumber('TasaImp'),
                'net' => null,
                'vat' => 0,
                'exempt' => null,
                'total' => 0,
                'folios' => [],
            ];

            $net = $row->getAsInt('MntNeto');
            if ($net !== null && $net !== 0) {
                $groups[$tpoDoc]['net'] = ($groups[$tpoDoc]['net'] ?? 0) + $net;
                $groups[$tpoDoc]['vat'] += $row->getAsInt('MntIVA') ?? 0;
            }

            $exempt = $row->getAsInt('MntExe');
            if ($exempt !== null && $exempt !== 0) {
                $groups[$tpoDoc]['exempt'] = ($groups[$tpoDoc]['exempt'] ?? 0) + $exempt;
            }

            $groups[$tpoDoc]['total'] += $row->getAsInt('MntTotal') ?? 0;

            $folio = $row->getAsInt('NroDoc');
            if ($folio !== null) {
                $groups[$tpoDoc]['folios'][] = $folio;
            }
        }

        $resumen = [];
        foreach ($groups as $tpoDoc => $group) {
            $issued = $this->countIssued($detalles, $tpoDoc);
            $resumen[] = [
                'TipoDocumento' => $tpoDoc,
                'MntNeto' => $group['net'] ?? false,
                'MntIva' => $group['net'] !== null ? $group['vat'] : false,
                'TasaIVA' => $group['rate'] ?: false,
                'MntExento' => $group['exempt'] ?? false,
                'MntTotal' => $group['total'],
                'FoliosEmitidos' => $issued,
                'FoliosAnulados' => 0,
                'FoliosUtilizados' => $issued,
                'RangoUtilizados' => $this->calculateRangos($group['folios']),
            ];
        }

        return $resumen;
    }

    /**
     * Cuenta los documentos de un tipo en los detalles.
     *
     * @param array<int, array<string, mixed>> $detalles
     */
    private function countIssued(array $detalles, int $tpoDoc): int
    {
        $count = 0;
        foreach ($detalles as $d) {
            if (self::reader($d)->getAsInt('TpoDoc') === $tpoDoc) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Determina los rangos continuos de folios.
     *
     * @param list<int> $folios Lista de números de folio.
     * @return list<array{Inicial: int, Final: int}>
     */
    private function calculateRangos(array $folios): array
    {
        if ($folios === []) {
            return [];
        }

        sort($folios);

        $rangos = [];
        $inicioRango = $folios[0];
        $anterior = $folios[0];

        for ($i = 1; $i < count($folios); $i++) {
            if ($folios[$i] !== $anterior + 1) {
                $rangos[] = ['Inicial' => $inicioRango, 'Final' => $anterior];
                $inicioRango = $folios[$i];
            }
            $anterior = $folios[$i];
        }

        $rangos[] = ['Inicial' => $inicioRango, 'Final' => $anterior];

        return $rangos;
    }

    /**
     * Entrega un lector de los datos. En los detalles y en la carátula `false`
     * es lo que se pone cuando no hay un valor.
     *
     * @param array<string, mixed> $data
     */
    private static function reader(array $data): ArrayReader
    {
        return ArrayReader::fromArray(
            array_map(static fn (mixed $value): mixed => $value === false ? null : $value, $data)
        );
    }
}

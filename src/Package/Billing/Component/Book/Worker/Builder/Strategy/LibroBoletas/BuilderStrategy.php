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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroBoletas;

use Derafu\Array\ArrayCaster;
use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Xml\Contract\XmlEncoderInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroBoletas;

/**
 * Estrategia `libro_boletas` del `BuilderWorker`.
 *
 * Construye el XML del Libro de Boletas Electrónicas a partir de los detalles
 * normalizados por el `LoaderWorker`.
 */
#[Strategy(name: 'libro_boletas', worker: 'builder', component: 'book', package: 'billing')]
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

        // Calcular resumen del período.
        $resumenPeriodo = $this->calculateResumenPeriodo($detalles);

        // Construir ID del documento.
        $caratulaReader = ArrayReader::fromArray(ArrayCaster::nullify($caratula, [false, '']));
        $rut = str_replace('-', '', $caratulaReader->getAsString('RutEmisorLibro') ?? '');
        $periodo = str_replace('-', '', $caratulaReader->getAsString('PeriodoTributario') ?? date('Y-m'));
        $id = sprintf('LibreDTE_LIBRO_BOLETA_%s_%s_%s', $rut, $periodo, time());

        // Carátula normalizada.
        $caratulaNorm = array_merge([
            'RutEmisorLibro' => null,
            'RutEnvia' => null,
            'PeriodoTributario' => date('Y-m'),
            'FchResol' => null,
            'NroResol' => null,
            'TipoLibro' => 'ESPECIAL',
            'TipoEnvio' => 'TOTAL',
            'FolioNotificacion' => null,
        ], $caratula);

        // Generar XML.
        $xmlDocument = $this->xmlEncoder->encode([
            'LibroBoleta' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte LibroBOLETA_v10.xsd',
                    'version' => '1.0',
                ],
                'EnvioLibro' => [
                    '@attributes' => ['ID' => $id],
                    'Caratula' => $caratulaNorm,
                    'ResumenPeriodo' => $resumenPeriodo ?: null,
                    'Detalle' => $detalles ?: null,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ],
        ]);

        return new LibroBoletas($xmlDocument);
    }

    /**
     * Calcula el resumen del período agrupado por TpoDoc y TpoServ.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return array<string, mixed>
     */
    private function calculateResumenPeriodo(array $detalles): array
    {
        $resumen = [];

        foreach ($detalles as $d) {
            $row = ArrayReader::fromArray(ArrayCaster::nullify($d, [false, '']));
            $tpoDoc = $row->requireAsInt('TpoDoc');
            $tpoServ = $row->getAsInt('TpoServ', 3);
            $anulado = $row->getAsString('Anulado');

            if (!isset($resumen[$tpoDoc])) {
                $resumen[$tpoDoc] = [
                    'TpoDoc' => $tpoDoc,
                    'TotAnulado' => null,
                    'TotalesServicio' => [],
                ];
            }

            if (!$anulado) {
                if (!isset($resumen[$tpoDoc]['TotalesServicio'][$tpoServ])) {
                    $resumen[$tpoDoc]['TotalesServicio'][$tpoServ] = [
                        'TpoServ' => $tpoServ,
                        'PeriodoDevengado' => null,
                        'TotDoc' => null,
                        'TotMntExe' => null,
                        'TotMntNeto' => 0,
                        'TasaIVA' => 0,
                        'TotMntIVA' => 0,
                        'TotMntTotal' => null,
                        'TotMntNoFact' => null,
                        'TotMntPeriodo' => null,
                        'TotSaldoAnt' => null,
                        'TotVlrPagar' => null,
                        'TotTicket' => null,
                    ];
                }

                $svc = &$resumen[$tpoDoc]['TotalesServicio'][$tpoServ];
                $svc['TotDoc'] += 1;

                $vals = [
                    'MntExe' => 'TotMntExe',
                    'MntTotal' => 'TotMntTotal',
                    'MntNoFact' => 'TotMntNoFact',
                    'MntPeriodo' => 'TotMntPeriodo',
                    'SaldoAnt' => 'TotSaldoAnt',
                    'VlrPagar' => 'TotVlrPagar',
                    'TotTicketBoleta' => 'TotTicket',
                ];
                foreach ($vals as $ori => $des) {
                    $amount = $row->getAsInt($ori);
                    if ($amount) {
                        $svc[$des] += $amount;
                    }
                }

                $tasa = 19;
                $neto = (int) round((($row->getAsInt('MntTotal') ?? 0) - ($row->getAsInt('MntExe') ?? 0)) / (1 + $tasa / 100));
                if ($neto) {
                    $svc['TotMntNeto'] += $neto;
                    $svc['TasaIVA'] = $tasa;
                    $svc['TotMntIVA'] = (int) $svc['TotMntTotal'] - (int) $svc['TotMntExe'] - $svc['TotMntNeto'];
                }
                unset($svc);
            } elseif ($anulado === 'A') {
                $resumen[$tpoDoc]['TotAnulado'] = (int) $resumen[$tpoDoc]['TotAnulado'] + 1;
            }
        }

        // Convertir TotalesServicio a lista.
        $totalesPeriodo = [];
        foreach ($resumen as $r) {
            $r['TotalesServicio'] = array_values($r['TotalesServicio']);
            $totalesPeriodo[] = $r;
        }

        return ['TotalesPeriodo' => $totalesPeriodo];
    }
}

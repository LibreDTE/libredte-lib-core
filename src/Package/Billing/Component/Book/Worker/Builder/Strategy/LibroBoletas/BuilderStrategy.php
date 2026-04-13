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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroBoletas;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Xml\Service\XmlEncoder;
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
        $rut = str_replace('-', '', $caratula['RutEmisorLibro'] ?? '');
        $periodo = str_replace('-', '', $caratula['PeriodoTributario'] ?? date('Y-m'));
        $id = sprintf('LibreDTE_LIBRO_BOLETA_%s_%s_%s', $rut, $periodo, time());

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
        $encoder = new XmlEncoder();
        $xmlDocument = $encoder->encode([
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
                    'ResumenPeriodo' => $resumenPeriodo ?: false,
                    'Detalle' => $detalles ?: false,
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
            $tpoDoc = $d['TpoDoc'];
            $tpoServ = $d['TpoServ'] ?? 3;

            if (!isset($resumen[$tpoDoc])) {
                $resumen[$tpoDoc] = [
                    'TpoDoc' => $tpoDoc,
                    'TotAnulado' => false,
                    'TotalesServicio' => [],
                ];
            }

            if (empty($d['Anulado'])) {
                if (!isset($resumen[$tpoDoc]['TotalesServicio'][$tpoServ])) {
                    $resumen[$tpoDoc]['TotalesServicio'][$tpoServ] = [
                        'TpoServ' => $tpoServ,
                        'PeriodoDevengado' => false,
                        'TotDoc' => false,
                        'TotMntExe' => false,
                        'TotMntNeto' => 0,
                        'TasaIVA' => 0,
                        'TotMntIVA' => 0,
                        'TotMntTotal' => false,
                        'TotMntNoFact' => false,
                        'TotMntPeriodo' => false,
                        'TotSaldoAnt' => false,
                        'TotVlrPagar' => false,
                        'TotTicket' => false,
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
                    if ($d[$ori]) {
                        $svc[$des] += $d[$ori];
                    }
                }

                $tasa = 19;
                $neto = (int) round(((int) $d['MntTotal'] - (int) $d['MntExe']) / (1 + $tasa / 100));
                if ($neto) {
                    $svc['TotMntNeto'] += $neto;
                    $svc['TasaIVA'] = $tasa;
                    $svc['TotMntIVA'] = (int) $svc['TotMntTotal'] - (int) $svc['TotMntExe'] - $svc['TotMntNeto'];
                }
                unset($svc);
            } elseif ($d['Anulado'] === 'A') {
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

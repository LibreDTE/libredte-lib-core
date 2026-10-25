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

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
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

        // Calcular resumen del período.
        $resumenPeriodo = $this->calculateResumenPeriodo($detalles);

        // Construir ID del documento.
        $rut = str_replace('-', '', $caratula['RutEmisorLibro'] ?? '');
        $periodo = str_replace('-', '', $caratula['PeriodoTributario'] ?? date('Y-m'));
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
        $resumen = [
            'TotFolAnulado' => false,
            'TotGuiaAnulada' => false,
            'TotGuiaVenta' => 0,
            'TotMntGuiaVta' => 0,
            'TotTraslado' => false,
        ];

        foreach ($detalles as $d) {
            $anulado = $d['Anulado'] ?? false;
            $tpoOper = $d['TpoOper'] ?? false;

            if ($anulado == 1) {
                $resumen['TotFolAnulado'] = (int) $resumen['TotFolAnulado'] + 1;
            } elseif ($anulado == 2) {
                $resumen['TotGuiaAnulada'] = (int) $resumen['TotGuiaAnulada'] + 1;
            } else {
                if ($tpoOper == 1) {
                    $resumen['TotGuiaVenta']++;
                    $resumen['TotMntGuiaVta'] += (int) ($d['MntTotal'] ?? 0);
                } elseif ($tpoOper) {
                    if ($resumen['TotTraslado'] === false) {
                        $resumen['TotTraslado'] = [];
                    }
                    if (!isset($resumen['TotTraslado'][$tpoOper])) {
                        $resumen['TotTraslado'][$tpoOper] = [
                            'TpoTraslado' => $tpoOper,
                            'CantGuia' => 0,
                            'MntGuia' => 0,
                        ];
                    }
                    $resumen['TotTraslado'][$tpoOper]['CantGuia']++;
                    $resumen['TotTraslado'][$tpoOper]['MntGuia'] += (int) ($d['MntTotal'] ?? 0);
                }
            }
        }

        // Re-indexar traslados para XML.
        if (is_array($resumen['TotTraslado'])) {
            $resumen['TotTraslado'] = array_values($resumen['TotTraslado']);
        }

        return $resumen;
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroGuias;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Xml\Service\XmlEncoder;
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
    /**
     * {@inheritDoc}
     */
    public function build(BookBagInterface $bag): BookInterface
    {
        $caratula = $bag->getCaratula();
        $detalles = $bag->getDetalle();

        // Calcular resumen del período.
        $resumenPeriodo = $this->calcularResumenPeriodo($detalles);

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
        $encoder = new XmlEncoder();
        $xmlDocument = $encoder->encode([
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
    private function calcularResumenPeriodo(array $detalles): array
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

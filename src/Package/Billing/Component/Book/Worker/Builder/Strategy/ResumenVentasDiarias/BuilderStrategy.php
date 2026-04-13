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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\ResumenVentasDiarias;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
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
        $rut = str_replace('-', '', $caratulaNorm['RutEmisor'] ?? '');
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
                    'Resumen' => array_values($resumen),
                ],
            ],
        ]);

        return new ResumenVentasDiarias($xmlDocument);
    }

    /**
     * Calcula la fecha del primer documento de los detalles.
     *
     * @param array<int, array<string, mixed>> $detalles
     */
    private function calculateFechaInicial(array $detalles): string
    {
        $fecha = '9999-12-31';
        foreach ($detalles as $d) {
            if (isset($d['FchDoc']) && $d['FchDoc'] < $fecha) {
                $fecha = $d['FchDoc'];
            }
        }
        return $fecha === '9999-12-31' ? date('Y-m-d') : $fecha;
    }

    /**
     * Calcula la fecha del último documento de los detalles.
     *
     * @param array<int, array<string, mixed>> $detalles
     */
    private function calculateFechaFinal(array $detalles): string
    {
        $fecha = '0000-01-01';
        foreach ($detalles as $d) {
            if (isset($d['FchDoc']) && $d['FchDoc'] > $fecha) {
                $fecha = $d['FchDoc'];
            }
        }
        return $fecha === '0000-01-01' ? date('Y-m-d') : $fecha;
    }

    /**
     * Calcula el resumen agrupado por TpoDoc con rangos de folios utilizados.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return array<int|string, array<string, mixed>>
     */
    private function calculateResumen(array $detalles): array
    {
        $resumen = [];
        $foliosPorTipo = [];

        foreach ($detalles as $d) {
            $tpoDoc = $d['TpoDoc'] ?? false;
            if (!$tpoDoc) {
                continue;
            }

            if (!isset($resumen[$tpoDoc])) {
                $resumen[$tpoDoc] = [
                    'TipoDocumento' => $tpoDoc,
                    'MntNeto' => false,
                    'MntIva' => false,
                    'TasaIVA' => isset($d['TasaImp']) && $d['TasaImp'] ? $d['TasaImp'] : false,
                    'MntExento' => false,
                    'MntTotal' => 0,
                    'FoliosEmitidos' => 0,
                    'FoliosAnulados' => 0,
                    'FoliosUtilizados' => false,
                    'RangoUtilizados' => false,
                ];
                $foliosPorTipo[$tpoDoc] = [];
            }

            if (isset($d['MntNeto']) && $d['MntNeto']) {
                $resumen[$tpoDoc]['MntNeto'] = (int) $resumen[$tpoDoc]['MntNeto'] + (int) $d['MntNeto'];
                $mntIva = isset($d['MntIVA']) ? (int) $d['MntIVA'] : 0;
                $resumen[$tpoDoc]['MntIva'] = (int) $resumen[$tpoDoc]['MntIva'] + $mntIva;
            }

            if (isset($d['MntExe']) && $d['MntExe']) {
                $resumen[$tpoDoc]['MntExento'] = (int) $resumen[$tpoDoc]['MntExento'] + (int) $d['MntExe'];
            }

            $resumen[$tpoDoc]['MntTotal'] += (int) ($d['MntTotal'] ?? 0);
            $resumen[$tpoDoc]['FoliosEmitidos']++;

            if (isset($d['NroDoc'])) {
                $foliosPorTipo[$tpoDoc][] = (int) $d['NroDoc'];
            }
        }

        // Post-procesamiento: calcular folios utilizados y rangos.
        foreach ($resumen as $tpoDoc => &$r) {
            $r['FoliosUtilizados'] = $r['FoliosEmitidos'] + $r['FoliosAnulados'];
            $r['RangoUtilizados'] = $this->calculateRangos($foliosPorTipo[$tpoDoc] ?? []);
        }
        unset($r);

        return $resumen;
    }

    /**
     * Determina los rangos continuos de folios.
     *
     * @param int[] $folios Lista de números de folio.
     * @return array<int, array{Inicial: int, Final: int}>
     */
    private function calculateRangos(array $folios): array
    {
        if (empty($folios)) {
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
}

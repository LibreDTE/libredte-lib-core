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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job;

use Derafu\Array\ArrayPath;
use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;

/**
 * Trabajo con reglas de normalización generales para el final de todos los
 * documentos tributarios.
 */
#[Job(name: 'normalize_data_post_document_normalization', worker: 'normalizer', component: 'document', package: 'billing')]
class NormalizeDataPostDocumentNormalizationJob extends AbstractJob implements JobInterface
{
    /**
     * Aplica la normalización final de los datos de un documento tributario
     * electrónico.
     *
     * Esta normalización se ejecuta después de ejecutar la normalización
     * específica del tipo de documento tributario.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    public function execute(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();

        $this->normalizeIdDocMntPagos($data);
        $this->normalizeOtraMoneda($data);

        // Si vienen datos específicos de LibreDTE se quitan de los datos.
        // Si no estaban previamente en la bolsa, se agregan antes de quitarlos.
        if (array_key_exists('LibreDTE', $data)) {
            if (!$bag->getLibredteData()) {
                $bag->setLibredteData(ValueType::map()->parse($data['LibreDTE'] ?? [], 'LibreDTE'));
            }
            unset($data['LibreDTE']);
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData(ArrayReader::fromArray($data, 'document')->toMap());
    }

    /**
     * Normalizar montos de pagos programados.
     *
     * @param array<array-key, mixed> $data
     * @return void
     */
    private function normalizeIdDocMntPagos(array &$data): void
    {
        $writer = ArrayWriter::fromReference($data);

        $mntPagos = $writer->getReader()->get('Encabezado.IdDoc.MntPagos', ValueType::mixed());
        if (!is_array($mntPagos)) {
            return;
        }

        if (!isset($mntPagos[0])) {
            $writer->set('Encabezado.IdDoc.MntPagos', [$mntPagos]);
        }

        $pagos = $writer->getReader()->get(
            'Encabezado.IdDoc.MntPagos',
            ValueType::arrayOf(ValueType::map()),
            []
        );
        foreach ($pagos as $key => $pago) {
            $pago = array_merge([
                'FchPago' => null,
                'MntPago' => null,
                'GlosaPagos' => null,
            ], $pago);
            if ($pago['MntPago'] === null) {
                $pago['MntPago'] = $writer->getReader()->get(
                    'Encabezado.Totales.MntTotal',
                    ValueType::mixed()
                );
            }
            $writer->set('Encabezado.IdDoc.MntPagos.' . $key, $pago);
        }
    }

    /**
     * Si existe OtraMoneda se verifican los tipos de cambio y totales.
     *
     * @param array<array-key, mixed> $data
     * @return void
     */
    private function normalizeOtraMoneda(array &$data): void
    {
        $writer = ArrayWriter::fromReference($data);

        if (empty($writer->getReader()->get('Encabezado.OtraMoneda', ValueType::mixed()))) {
            return;
        }

        // Una sola moneda se decodifica como un arreglo y no como una lista,
        // se normaliza siempre a una lista.
        $data = ArrayPath::wrap($data, 'Encabezado.OtraMoneda');

        $monedas = $writer->getReader()->get(
            'Encabezado.OtraMoneda',
            ValueType::arrayOf(ValueType::map()),
            []
        );
        foreach ($monedas as $key => $moneda) {
            $path = 'Encabezado.OtraMoneda.' . $key;

            // Colocar campos por defecto.
            $OtraMoneda = array_merge([
                'TpoMoneda' => null,
                'TpoCambio' => null,
                'MntNetoOtrMnda' => null,
                'MntExeOtrMnda' => null,
                'MntFaeCarneOtrMnda' => null,
                'MntMargComOtrMnda' => null,
                'IVAOtrMnda' => null,
                'ImpRetOtrMnda' => null,
                'IVANoRetOtrMnda' => null,
                'MntTotOtrMnda' => null,
            ], $moneda);

            // Si no hay tipo de cambio no seguir.
            if (!isset($OtraMoneda['TpoCambio'])) {
                $writer->set($path, $OtraMoneda);
                continue;
            }

            // Buscar si los valores están asignados, si no lo están se
            // asignan usando el tipo de cambio que exista para la moneda.
            $tpoCambio = ArrayReader::fromArray($OtraMoneda, $path)->requireAsNumber('TpoCambio');
            foreach (['MntNeto', 'MntExe', 'IVA', 'IVANoRet'] as $monto) {
                $total = $writer->getReader()->getAsNumber('Encabezado.Totales.' . $monto);
                if (
                    empty($OtraMoneda[$monto.'OtrMnda'])
                    && !empty($total)
                ) {
                    $OtraMoneda[$monto.'OtrMnda'] = round($total * $tpoCambio, 4);
                }
            }

            // Calcular MntFaeCarneOtrMnda, MntMargComOtrMnda y
            // ImpRetOtrMnda.
            if (empty($OtraMoneda['MntFaeCarneOtrMnda'])) {
                // TODO: Implementar cálculo de MntFaeCarneOtrMnda.
                $OtraMoneda['MntFaeCarneOtrMnda'] = null;
            }
            if (empty($OtraMoneda['MntMargComOtrMnda'])) {
                // TODO: Implementar cálculo de MntMargComOtrMnda.
                $OtraMoneda['MntMargComOtrMnda'] = null;
            }
            if (empty($OtraMoneda['ImpRetOtrMnda'])) {
                // TODO: Implementar cálculo de ImpRetOtrMnda.
                $OtraMoneda['ImpRetOtrMnda'] = null;
            }

            // Calcular el monto total.
            if (empty($OtraMoneda['MntTotOtrMnda'])) {
                $montos = ArrayReader::fromArray($OtraMoneda, $path);
                $totalOtraMoneda = 0;
                $cols = [
                    'MntNetoOtrMnda',
                    'MntExeOtrMnda',
                    'MntFaeCarneOtrMnda',
                    'MntMargComOtrMnda',
                    'IVAOtrMnda',
                    'IVANoRetOtrMnda',
                ];
                foreach ($cols as $monto) {
                    if (!empty($OtraMoneda[$monto])) {
                        $totalOtraMoneda += $montos->requireAsNumber($monto);
                    }
                }

                // Agregar el total de impuesto retenido de otra moneda.
                if (!empty($OtraMoneda['ImpRetOtrMnda'])) {
                    // TODO: Agregar el total de impuesto retenido de ImpRetOtrMnda.
                }

                // Aproximar el total si es en pesos chilenos.
                if ($OtraMoneda['TpoMoneda'] === 'PESO CL') {
                    $totalOtraMoneda = round($totalOtraMoneda, 0);
                }

                $OtraMoneda['MntTotOtrMnda'] = $totalOtraMoneda;
            }

            // Si el tipo de cambio es 0, se quita.
            if ($OtraMoneda['TpoCambio'] == 0) {
                $OtraMoneda['TpoCambio'] = null;
            }

            $writer->set($path, $OtraMoneda);
        }
    }
}

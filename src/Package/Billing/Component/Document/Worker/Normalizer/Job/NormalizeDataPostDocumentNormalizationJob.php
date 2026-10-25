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

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
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
        $data = $bag->getNormalizedData();

        $this->normalizeIdDocMntPagos($data);
        $this->normalizeOtraMoneda($data);

        // Si vienen datos específicos de LibreDTE se quitan de los datos.
        // Si no estaban previamente en la bolsa, se agregan antes de quitarlos.
        if (array_key_exists('LibreDTE', $data)) {
            if (!$bag->getLibredteData()) {
                $bag->setLibredteData((array) $data['LibreDTE']);
            }
            unset($data['LibreDTE']);
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }

    /**
     * Normalizar montos de pagos programados.
     *
     * @param array $data
     * @return void
     */
    private function normalizeIdDocMntPagos(array &$data): void
    {
        if (!is_array($data['Encabezado']['IdDoc']['MntPagos'])) {
            return;
        }

        if (!isset($data['Encabezado']['IdDoc']['MntPagos'][0])) {
            $data['Encabezado']['IdDoc']['MntPagos'] = [
                $data['Encabezado']['IdDoc']['MntPagos'],
            ];
        }

        foreach ($data['Encabezado']['IdDoc']['MntPagos'] as &$MntPagos) {
            $MntPagos = array_merge([
                'FchPago' => null,
                'MntPago' => null,
                'GlosaPagos' => false,
            ], $MntPagos);
            if ($MntPagos['MntPago'] === null) {
                $MntPagos['MntPago'] = $data['Encabezado']['Totales']['MntTotal'];
            }
        }
    }

    /**
     * Si existe OtraMoneda se verifican los tipos de cambio y totales.
     *
     * @param array $data
     * @return void
     */
    private function normalizeOtraMoneda(array &$data): void
    {
        if (empty($data['Encabezado']['OtraMoneda'])) {
            return;
        }

        if (!isset($data['Encabezado']['OtraMoneda'][0])) {
            $data['Encabezado']['OtraMoneda'] = [
                $data['Encabezado']['OtraMoneda'],
            ];
        }

        foreach ($data['Encabezado']['OtraMoneda'] as &$OtraMoneda) {
            // Colocar campos por defecto.
            $OtraMoneda = array_merge([
                'TpoMoneda' => false,
                'TpoCambio' => false,
                'MntNetoOtrMnda' => false,
                'MntExeOtrMnda' => false,
                'MntFaeCarneOtrMnda' => false,
                'MntMargComOtrMnda' => false,
                'IVAOtrMnda' => false,
                'ImpRetOtrMnda' => false,
                'IVANoRetOtrMnda' => false,
                'MntTotOtrMnda' => false,
            ], $OtraMoneda);

            // Si no hay tipo de cambio no seguir.
            if (!isset($OtraMoneda['TpoCambio'])) {
                continue;
            }

            // Buscar si los valores están asignados, si no lo están se
            // asignan usando el tipo de cambio que exista para la moneda.
            foreach (['MntNeto', 'MntExe', 'IVA', 'IVANoRet'] as $monto) {
                if (
                    empty($OtraMoneda[$monto.'OtrMnda'])
                    && !empty($data['Encabezado']['Totales'][$monto])
                ) {
                    $OtraMoneda[$monto.'OtrMnda'] = round(
                        $data['Encabezado']['Totales'][$monto]
                            * $OtraMoneda['TpoCambio'],
                        4
                    );
                }
            }

            // Calcular MntFaeCarneOtrMnda, MntMargComOtrMnda y
            // ImpRetOtrMnda.
            if (empty($OtraMoneda['MntFaeCarneOtrMnda'])) {
                // TODO: Implementar cálculo de MntFaeCarneOtrMnda.
                $OtraMoneda['MntFaeCarneOtrMnda'] = false;
            }
            if (empty($OtraMoneda['MntMargComOtrMnda'])) {
                // TODO: Implementar cálculo de MntMargComOtrMnda.
                $OtraMoneda['MntMargComOtrMnda'] = false;
            }
            if (empty($OtraMoneda['ImpRetOtrMnda'])) {
                // TODO: Implementar cálculo de ImpRetOtrMnda.
                $OtraMoneda['ImpRetOtrMnda'] = false;
            }

            // Calcular el monto total.
            if (empty($OtraMoneda['MntTotOtrMnda'])) {
                $OtraMoneda['MntTotOtrMnda'] = 0;
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
                        $OtraMoneda['MntTotOtrMnda'] += $OtraMoneda[$monto];
                    }
                }

                // Agregar el total de impuesto retenido de otra moneda.
                if (!empty($OtraMoneda['ImpRetOtrMnda'])) {
                    // TODO: Agregar el total de impuesto retenido de ImpRetOtrMnda.
                }

                // Aproximar el total si es en pesos chilenos.
                if ($OtraMoneda['TpoMoneda'] === 'PESO CL') {
                    $OtraMoneda['MntTotOtrMnda'] = round(
                        $OtraMoneda['MntTotOtrMnda'],
                        0
                    );
                }
            }

            // Si el tipo de cambio es 0, se quita.
            if ($OtraMoneda['TpoCambio'] == 0) {
                $OtraMoneda['TpoCambio'] = false;
            }
        }
    }
}

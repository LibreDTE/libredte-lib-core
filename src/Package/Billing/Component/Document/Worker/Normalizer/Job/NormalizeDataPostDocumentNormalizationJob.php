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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job;

use Derafu\Lib\Core\Foundation\Abstract\AbstractJob;
use Derafu\Lib\Core\Foundation\Contract\JobInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;

/**
 * Trabajo con reglas de normalización generales para el final de todos los
 * documentos tributarios.
 */
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

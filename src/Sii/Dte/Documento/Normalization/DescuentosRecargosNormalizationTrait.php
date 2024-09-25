<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\Documento\Normalization;

use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;

/**
 * Reglas de normalización para los descuentos y recargos de un documento.
 */
trait DescuentosRecargosNormalizationTrait
{
    use UtilsTrait;

    /**
     * Entrega el tipo de documento que este "builder" puede construir.
     *
     * @return DocumentoTipo
     */
    abstract protected function getTipoDocumento(): DocumentoTipo;

    /**
     * Aplica los descuentos y recargos generales respectivos a los montos que
     * correspondan según el indicador del descuento o recargo.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     * @todo Revisar si existen casos de boletas afectas con algún item exento
     * donde el descuento se podría estar aplicando mal.
     */
    protected function applyDescuentosRecargosNormalization(array $data): array
    {
        if (!empty($data['DscRcgGlobal'])) {
            if (!isset($data['DscRcgGlobal'][0])) {
                $data['DscRcgGlobal'] = [
                    $data['DscRcgGlobal'],
                ];
            }

            foreach ($data['DscRcgGlobal'] as &$dr) {
                $dr = array_merge([
                    'NroLinDR' => false,
                    'TpoMov' => false,
                    'GlosaDR' => false,
                    'TpoValor' => false,
                    'ValorDR' => false,
                    'ValorDROtrMnda' => false,
                    'IndExeDR' => false,
                ], $dr);
                if ($this->getTipoDocumento()->esExportacion()) {
                    $dr['IndExeDR'] = 1;
                }

                // Determinar a que aplicar el descuento/recargo.
                if (!isset($dr['IndExeDR']) || $dr['IndExeDR'] === false) {
                    $monto = $data['Encabezado']['IdDoc']['TipoDTE'] === 39
                        ? 'MntTotal'
                        : 'MntNeto'
                    ;
                } else {
                    $monto = $dr['IndExeDR'] == 1
                        ? 'MntExe'  # IndExeDR == 1
                        : 'MontoNF' # IndExeDR == 2
                    ;
                }

                // Si no hay monto al que aplicar el descuento se omite.
                if (empty($data['Encabezado']['Totales'][$monto])) {
                    continue;
                }

                // Calcular valor del descuento o recargo.
                if ($dr['TpoValor'] === '$') {
                    $dr['ValorDR'] = $this->round(
                        $dr['ValorDR'],
                        $data['Encabezado']['Totales']['TpoMoneda'],
                        2
                    );
                }
                $valor = $dr['TpoValor'] === '%'
                    ? $this->round(
                        ($dr['ValorDR'] / 100) * $data['Encabezado']['Totales'][$monto],
                        $data['Encabezado']['Totales']['TpoMoneda']
                    )
                    : $dr['ValorDR']
                ;

                // Aplicar descuento.
                if ($dr['TpoMov'] === 'D') {
                    $data['Encabezado']['Totales'][$monto] -= $valor;
                }

                // Aplicar recargo.
                elseif ($dr['TpoMov'] === 'R') {
                    $data['Encabezado']['Totales'][$monto] += $valor;
                }
                $data['Encabezado']['Totales'][$monto] = $this->round(
                    $data['Encabezado']['Totales'][$monto],
                    $data['Encabezado']['Totales']['TpoMoneda']
                );

                // Si el descuento global se aplica a una boleta exenta se
                // copia el valor exento al total.
                if (
                    $data['Encabezado']['IdDoc']['TipoDTE'] === 41
                    && isset($dr['IndExeDR'])
                    && $dr['IndExeDR'] == 1
                ) {
                    $data['Encabezado']['Totales']['MntTotal'] =
                        $data['Encabezado']['Totales']['MntExe']
                    ;
                }
            }
        }

        // Entregar los datos normalizados.
        return $data;
    }
}

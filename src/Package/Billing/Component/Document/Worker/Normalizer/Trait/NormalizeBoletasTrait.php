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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;

/**
 * Reglas de normalización para boletas.
 */
trait NormalizeBoletasTrait
{
    /**
     * Normaliza las boletas electrónicas.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    protected function normalizeBoletas(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Cambiar tags de DTE a boleta si se pasaron.
        if ($data['Encabezado']['Emisor']['RznSoc']) {
            $data['Encabezado']['Emisor']['RznSocEmisor'] =
                $data['Encabezado']['Emisor']['RznSoc']
            ;
            $data['Encabezado']['Emisor']['RznSoc'] = false;
        }
        if ($data['Encabezado']['Emisor']['GiroEmis']) {
            $data['Encabezado']['Emisor']['GiroEmisor'] =
                $data['Encabezado']['Emisor']['GiroEmis']
            ;
            $data['Encabezado']['Emisor']['GiroEmis'] = false;
        }
        $data['Encabezado']['Emisor']['Acteco'] = false;
        $data['Encabezado']['Emisor']['Telefono'] = false;
        $data['Encabezado']['Emisor']['CorreoEmisor'] = false;
        $data['Encabezado']['Emisor']['CdgVendedor'] = false;
        $data['Encabezado']['Receptor']['GiroRecep'] = false;
        if (!empty($data['Encabezado']['Receptor']['CorreoRecep'])) {
            if ($data['Referencia'] === false) {
                $data['Referencia'] = [];
            }
            $data['Referencia'][] = [
                'NroLinRef' => !empty($data['Referencia'])
                    ? (count($data['Referencia']) + 1)
                : 1,
                'RazonRef' => mb_substr(
                    sprintf(
                        'Email receptor: %s',
                        $data['Encabezado']['Receptor']['CorreoRecep']
                    ),
                    0,
                    90
                ),
            ];
        }
        $data['Encabezado']['Receptor']['CorreoRecep'] = false;

        // Quitar otros tags que no son parte de las boletas.
        $data['Encabezado']['IdDoc']['FmaPago'] = false;
        $data['Encabezado']['IdDoc']['FchCancel'] = false;
        $data['Encabezado']['IdDoc']['MedioPago'] = false;
        $data['Encabezado']['IdDoc']['TpoCtaPago'] = false;
        $data['Encabezado']['IdDoc']['NumCtaPago'] = false;
        $data['Encabezado']['IdDoc']['BcoPago'] = false;
        $data['Encabezado']['IdDoc']['TermPagoGlosa'] = false;
        $data['Encabezado']['RUTSolicita'] = false;
        $data['Encabezado']['IdDoc']['TpoTranCompra'] = false;
        $data['Encabezado']['IdDoc']['TpoTranVenta'] = false;
        $data['Encabezado']['Transporte'] = false;

        // Ajustar las referencias si existen.
        if (!empty($data['Referencia'])) {
            if (!isset($data['Referencia'][0])) {
                $data['Referencia'] = [
                    $data['Referencia'],
                ];
            }
            foreach ($data['Referencia'] as &$r) {
                foreach (['FchRef'] as $c) {
                    if (isset($r[$c])) {
                        unset($r[$c]);
                    }
                }
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}

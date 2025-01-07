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

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
use Derafu\Lib\Core\Helper\Arr;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;

/**
 * Trabajo con reglas de normalización generales para el inicio de todos los
 * documentos tributarios.
 */
class NormalizeDataPreDocumentNormalizationJob extends AbstractJob implements JobInterface
{
    /**
     * Aplica la normalización inicial de los datos de un documento tributario
     * electrónico.
     *
     * Esta normalización se debe ejecutar antes de ejecutar la normalización
     * específica del tipo de documento tributario.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    public function execute(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Completar con campos por defecto.
        $data = Arr::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => false,
                    'Folio' => false,
                    'FchEmis' => date('Y-m-d'),
                    'IndNoRebaja' => false,
                    'TipoDespacho' => false,
                    'IndTraslado' => false,
                    'TpoImpresion' => false,
                    'IndServicio' => $bag->getTipoDocumento()->getDefaultIndServicio(),
                    'MntBruto' => false,
                    'TpoTranCompra' => false,
                    'TpoTranVenta' => false,
                    'FmaPago' => false,
                    'FmaPagExp' => false,
                    'MntCancel' => false,
                    'SaldoInsol' => false,
                    'FchCancel' => false,
                    'MntPagos' => false,
                    'PeriodoDesde' => false,
                    'PeriodoHasta' => false,
                    'MedioPago' => false,
                    'TpoCtaPago' => false,
                    'NumCtaPago' => false,
                    'BcoPago' => false,
                    'TermPagoCdg' => false,
                    'TermPagoGlosa' => false,
                    'TermPagoDias' => false,
                    'FchVenc' => false,
                ],
                'Emisor' => [
                    'RUTEmisor' => false,
                    'RznSoc' => false,
                    'GiroEmis' => false,
                    'Telefono' => false,
                    'CorreoEmisor' => false,
                    'Acteco' => false,
                    'GuiaExport' => false,
                    'Sucursal' => false,
                    'CdgSIISucur' => false,
                    'DirOrigen' => false,
                    'CmnaOrigen' => false,
                    'CiudadOrigen' => false,
                    'CdgVendedor' => false,
                    'IdAdicEmisor' => false,
                ],
                'Receptor' => [
                    'RUTRecep' => false,
                    'CdgIntRecep' => false,
                    'RznSocRecep' => false,
                    'Extranjero' => false,
                    'GiroRecep' => false,
                    'Contacto' => false,
                    'CorreoRecep' => false,
                    'DirRecep' => false,
                    'CmnaRecep' => false,
                    'CiudadRecep' => false,
                    'DirPostal' => false,
                    'CmnaPostal' => false,
                    'CiudadPostal' => false,
                ],
                'Totales' => [
                    'TpoMoneda' => false,
                ],
            ],
            'Detalle' => false,
            'SubTotInfo' => false,
            'DscRcgGlobal' => false,
            'Referencia' => false,
            'Comisiones' => false,
        ], $data);

        // Si existe descuento o recargo global se normalizan.
        if (!empty($data['DscRcgGlobal'])) {
            if (!isset($data['DscRcgGlobal'][0])) {
                $data['DscRcgGlobal'] = [
                    $data['DscRcgGlobal'],
                ];
            }
            $NroLinDR = 1;
            foreach ($data['DscRcgGlobal'] as &$dr) {
                $dr = array_merge([
                    'NroLinDR' => $NroLinDR++,
                ], $dr);
            }
        }

        // Si existe una o más referencias se normalizan.
        if (!empty($data['Referencia'])) {
            if (!isset($data['Referencia'][0])) {
                $data['Referencia'] = [
                    $data['Referencia'],
                ];
            }
            $NroLinRef = 1;
            foreach ($data['Referencia'] as &$r) {
                $r = array_merge([
                    'NroLinRef' => $NroLinRef++,
                    'TpoDocRef' => false,
                    'IndGlobal' => false,
                    'FolioRef' => false,
                    'RUTOtr' => false,
                    'FchRef' => date('Y-m-d'),
                    'CodRef' => false,
                    'RazonRef' => false,
                ], $r);
            }
        }

        // Verificar que exista TpoTranVenta.
        if (
            $bag->getTipoDocumento()->requiereTpoTranVenta()
            && empty($data['Encabezado']['IdDoc']['TpoTranVenta'])
        ) {
            // Se asigna "Ventas del giro" como valor por defecto.
            $data['Encabezado']['IdDoc']['TpoTranVenta'] = 1;
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}

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

use Derafu\Lib\Core\Enum\Cl\Comuna;
use Derafu\Lib\Core\Foundation\Abstract\AbstractJob;
use Derafu\Lib\Core\Foundation\Contract\JobInterface;
use Derafu\Lib\Core\Helper\Arr;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorProviderInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorProviderInterface;

/**
 * Trabajo con reglas de normalización generales para el inicio de todos los
 * documentos tributarios.
 */
class NormalizeDataPreDocumentNormalizationJob extends AbstractJob implements JobInterface
{
    /**
     * Constructor del trabajo con sus dependencias.
     *
     * @param EmisorProviderInterface $emisorProvider
     * @param ReceptorProviderInterface $receptorProvider
     */
    public function __construct(
        private EmisorProviderInterface $emisorProvider,
        private ReceptorProviderInterface $receptorProvider,
        private EmisorFactoryInterface $emisorFactory,
        private ReceptorFactoryInterface $receptorFactory
    ) {
    }

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

        $this->normalizeDefaultTags(
            $data,
            $bag->getTipoDocumento()->getDefaultIndServicio()
        );
        $this->normalizeIdDocFechas($data);
        $this->normalizeDescuentosRecargosGlobales($data);
        $this->normalizeReferencias($data);
        $this->normalizeTpoTranVenta(
            $data,
            $bag->getTipoDocumento()->requiereTpoTranVenta()
        );
        $this->normalizeEmisor($data);
        $this->normalizeReceptor($data);

        $bag->setNormalizedData($data);
    }

    /**
     * Completar con campos por defecto.
     *
     * @param array $data
     * @param int|false $IndServicio
     * @return void
     */
    private function normalizeDefaultTags(
        array &$data,
        int|false $IndServicio
    ): void {
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
                    'IndServicio' => $IndServicio,
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
    }

    /**
     * Si alguna de las fechas de la identificación del documento se pasó como
     * entero es un timestamp y se convierte a string.
     *
     * @param array $data
     * @return void
     */
    private function normalizeIdDocFechas(array &$data): void
    {
        foreach (['FchEmis', 'FchCancel', 'FchVenc'] as $tag) {
            if (is_numeric($data['Encabezado']['IdDoc'][$tag])) {
                $data['Encabezado']['IdDoc'][$tag] = date(
                    'Y-m-d',
                    $data['Encabezado']['IdDoc'][$tag]
                );
            }
        }
    }

    /**
     * Si existe descuento o recargo global se normalizan.
     *
     * @param array $data
     * @return void
     */
    private function normalizeDescuentosRecargosGlobales(array &$data): void
    {
        if (empty($data['DscRcgGlobal'])) {
            return;
        }

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

    /**
     * Si existe una o más referencias se normalizan.
     *
     * @param array $data
     * @return void
     */
    private function normalizeReferencias(array &$data): void
    {
        if (empty($data['Referencia'])) {
            return;
        }

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

            // Si la fecha de la referencia se pasó como entero es un timestamp
            // y se convierte a string.
            if (is_numeric($r['FchRef'])) {
                $r['FchRef'] = date('Y-m-d', $r['FchRef']);
            }
        }
    }

    /**
     * Asegura que exista TpoTranVenta.
     *
     * Si no existe y el documento lo requiere se asigna "Ventas del giro" como
     * valor por defecto.
     *
     * @param array $data
     * @param bool $requiereTpoTranVenta
     * @return void
     */
    private function normalizeTpoTranVenta(
        array &$data,
        bool $requiereTpoTranVenta
    ): void {
        if (
            $requiereTpoTranVenta
            && empty($data['Encabezado']['IdDoc']['TpoTranVenta'])
        ) {
            $data['Encabezado']['IdDoc']['TpoTranVenta'] = 1;
        }
    }

    /**
     * Normaliza los datos del emisor completando los datos requeridos que
     * puedan faltar usando un proveedor de datos de emisor.
     *
     * @param array $data
     * @return void
     */
    public function normalizeEmisor(array &$data): void
    {
        // Crear emisor con los datos del documento.
        $emisor = $this->emisorFactory->create($data['Encabezado']['Emisor']);

        // Obtener datos del emisor.
        $emisor = $this->emisorProvider->retrieve($emisor);

        // Actualizar $data si es necesario.
        $data['Encabezado']['Emisor']['RUTEmisor'] =
            ($data['Encabezado']['Emisor']['RUTEmisor'] ?? false)
            ?: $emisor->getRut()
        ;
        $data['Encabezado']['Emisor']['RznSoc'] =
            ($data['Encabezado']['Emisor']['RznSoc'] ?? false)
            ?: $emisor->getRazonSocial()
        ;
        $data['Encabezado']['Emisor']['GiroEmis'] =
            ($data['Encabezado']['Emisor']['GiroEmis'] ?? false)
            ?: ($emisor->getGiro() ?? false)
        ;
        $data['Encabezado']['Emisor']['Telefono'] =
            ($data['Encabezado']['Emisor']['Telefono'] ?? false)
            ?: ($emisor->getTelefono() ?? false)
        ;
        $data['Encabezado']['Emisor']['CorreoEmisor'] =
            ($data['Encabezado']['Emisor']['CorreoEmisor'] ?? false)
            ?: ($emisor->getEmail() ?? false)
        ;
        $data['Encabezado']['Emisor']['Acteco'] =
            ($data['Encabezado']['Emisor']['Acteco'] ?? false)
            ?: ($emisor->getActividadEconomica() ?? false)
        ;
        $data['Encabezado']['Emisor']['DirOrigen'] =
            ($data['Encabezado']['Emisor']['DirOrigen'] ?? false)
            ?: ($emisor->getDireccion() ?? false)
        ;
        $data['Encabezado']['Emisor']['CmnaOrigen'] =
            ($data['Encabezado']['Emisor']['CmnaOrigen'] ?? false)
            ?: ($emisor->getComuna() ?? false)
        ;
        $data['Encabezado']['Emisor']['CdgSIISucur'] =
            ($data['Encabezado']['Emisor']['CdgSIISucur'] ?? false)
            ?: ($emisor->getCodigoSucursal() ?? false)
        ;
        $data['Encabezado']['Emisor']['CdgVendedor'] =
            ($data['Encabezado']['Emisor']['CdgVendedor'] ?? false)
            ?: ($emisor->getVendedor() ?? false)
        ;

        // Si la comuna es un número se asume que es el código oficial.
        if (is_numeric($data['Encabezado']['Emisor']['CmnaOrigen'])) {
            $comuna = Comuna::tryFrom(
                (int) $data['Encabezado']['Emisor']['CmnaOrigen']
            );

            if ($comuna !== null) {
                $data['Encabezado']['Emisor']['CmnaOrigen'] =
                    $comuna->getNombre()
                ;
            } else {
                $data['Encabezado']['Emisor']['CmnaOrigen'] =
                    (string) $data['Encabezado']['Emisor']['CmnaOrigen']
                ;
            }
        }
    }

    /**
     * Normaliza los datos del receptor completando los datos requeridos que
     * puedan faltar usando un proveedor de datos de receptor.
     *
     * @param array $data
     * @return void
     */
    public function normalizeReceptor(array &$data): void
    {
        // Crear receptor con los datos del documento.
        $receptor = $this->receptorFactory->create(
            $data['Encabezado']['Receptor']
        );

        // Obtener datos del receptor.
        $receptor = $this->receptorProvider->retrieve($receptor);

        // Actualizar $data si es necesario.
        $data['Encabezado']['Receptor']['RUTRecep'] =
            ($data['Encabezado']['Receptor']['RUTRecep'] ?? false)
            ?: $receptor->getRut()
        ;
        $data['Encabezado']['Receptor']['RznSocRecep'] =
            ($data['Encabezado']['Receptor']['RznSocRecep'] ?? false)
            ?: $receptor->getRazonSocial()
        ;
        $data['Encabezado']['Receptor']['GiroRecep'] =
            ($data['Encabezado']['Receptor']['GiroRecep'] ?? false)
            ?: ($receptor->getGiro() ?? false)
        ;
        $data['Encabezado']['Receptor']['Contacto'] =
            ($data['Encabezado']['Receptor']['Contacto'] ?? false)
            ?: ($receptor->getTelefono() ?? false)
        ;
        $data['Encabezado']['Receptor']['CorreoRecep'] =
            ($data['Encabezado']['Receptor']['CorreoRecep'] ?? false)
            ?: ($receptor->getEmail() ?? false)
        ;
        $data['Encabezado']['Receptor']['DirRecep'] =
            ($data['Encabezado']['Receptor']['DirRecep'] ?? false)
            ?: ($receptor->getDireccion() ?? false)
        ;
        $data['Encabezado']['Receptor']['CmnaRecep'] =
            ($data['Encabezado']['Receptor']['CmnaRecep'] ?? false)
            ?: ($receptor->getComuna() ?? false)
        ;

        // Si la comuna es un número se asume que es el código oficial.
        if (is_numeric($data['Encabezado']['Receptor']['CmnaRecep'])) {
            $comuna = Comuna::tryFrom(
                (int) $data['Encabezado']['Receptor']['CmnaRecep']
            );

            if ($comuna !== null) {
                $data['Encabezado']['Receptor']['CmnaRecep'] =
                    $comuna->getNombre()
                ;
            } else {
                $data['Encabezado']['Receptor']['CmnaRecep'] =
                    (string) $data['Encabezado']['Receptor']['CmnaRecep']
                ;
            }
        }
    }
}

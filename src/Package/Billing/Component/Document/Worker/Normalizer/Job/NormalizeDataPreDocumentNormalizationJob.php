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

use Derafu\Array\ArrayCaster;
use Derafu\Array\ArrayPath;
use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\L10n\Cl\Enum\Comuna;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorProviderInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorProviderInterface;

/**
 * Trabajo con reglas de normalización generales para el inicio de todos los
 * documentos tributarios.
 */
#[Job(name: 'normalize_data_pre_document_normalization', worker: 'normalizer', component: 'document', package: 'billing')]
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
        // Los datos pueden traer `false` como valor ausente. Desde acá la
        // ausencia de un valor es `null`.
        $data = ArrayCaster::nullify($bag->requireNormalizedData(), [false]);

        $this->normalizeDefaultTags(
            $data,
            $bag->requireTipoDocumento()->getDefaultIndServicio()
        );
        $this->normalizeIdDocFechas($data);
        $this->normalizeDescuentosRecargosGlobales($data);
        $this->normalizeReferencias($data);
        $this->normalizeTpoTranVenta(
            $data,
            $bag->requireTipoDocumento()->requiresTpoTranVenta()
        );
        $this->normalizeEmisor($data);
        $this->normalizeReceptor($data);

        $bag->setNormalizedData(ArrayReader::fromArray($data, 'document')->toMap());
    }

    /**
     * Completar con campos por defecto.
     *
     * @param array<array-key, mixed> $data
     * @param int|null $IndServicio
     * @return void
     */
    private function normalizeDefaultTags(
        array &$data,
        ?int $IndServicio
    ): void {
        $data = array_replace_recursive([
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => null,
                    'Folio' => null,
                    'FchEmis' => date('Y-m-d'),
                    'IndNoRebaja' => null,
                    'TipoDespacho' => null,
                    'IndTraslado' => null,
                    'TpoImpresion' => null,
                    'IndServicio' => $IndServicio,
                    'MntBruto' => null,
                    'TpoTranCompra' => null,
                    'TpoTranVenta' => null,
                    'FmaPago' => null,
                    'FmaPagExp' => null,
                    'MntCancel' => null,
                    'SaldoInsol' => null,
                    'FchCancel' => null,
                    'MntPagos' => null,
                    'PeriodoDesde' => null,
                    'PeriodoHasta' => null,
                    'MedioPago' => null,
                    'TpoCtaPago' => null,
                    'NumCtaPago' => null,
                    'BcoPago' => null,
                    'TermPagoCdg' => null,
                    'TermPagoGlosa' => null,
                    'TermPagoDias' => null,
                    'FchVenc' => null,
                ],
                'Emisor' => [
                    'RUTEmisor' => null,
                    'RznSoc' => null,
                    'GiroEmis' => null,
                    'Telefono' => null,
                    'CorreoEmisor' => null,
                    'Acteco' => null,
                    'GuiaExport' => null,
                    'Sucursal' => null,
                    'CdgSIISucur' => null,
                    'DirOrigen' => null,
                    'CmnaOrigen' => null,
                    'CiudadOrigen' => null,
                    'CdgVendedor' => null,
                    'IdAdicEmisor' => null,
                ],
                'Receptor' => [
                    'RUTRecep' => null,
                    'CdgIntRecep' => null,
                    'RznSocRecep' => null,
                    'Extranjero' => null,
                    'GiroRecep' => null,
                    'Contacto' => null,
                    'CorreoRecep' => null,
                    'DirRecep' => null,
                    'CmnaRecep' => null,
                    'CiudadRecep' => null,
                    'DirPostal' => null,
                    'CmnaPostal' => null,
                    'CiudadPostal' => null,
                ],
                'Totales' => [
                    'TpoMoneda' => null,
                ],
            ],
            'Detalle' => null,
            'SubTotInfo' => null,
            'DscRcgGlobal' => null,
            'Referencia' => null,
            'Comisiones' => null,
        ], $data);
    }

    /**
     * Si alguna de las fechas de la identificación del documento se pasó como
     * entero es un timestamp y se convierte a string.
     *
     * @param array<array-key, mixed> $data
     * @return void
     */
    private function normalizeIdDocFechas(array &$data): void
    {
        $writer = ArrayWriter::fromReference($data);

        foreach (['FchEmis', 'FchCancel', 'FchVenc'] as $tag) {
            $path = 'Encabezado.IdDoc.' . $tag;
            $fecha = $writer->getReader()->get($path, ValueType::mixed());
            if (is_numeric($fecha)) {
                $writer->set($path, date('Y-m-d', ValueCaster::requireInt($fecha)));
            }
        }
    }

    /**
     * Si existe descuento o recargo global se normalizan.
     *
     * @param array<array-key, mixed> $data
     * @return void
     */
    private function normalizeDescuentosRecargosGlobales(array &$data): void
    {
        $writer = ArrayWriter::fromReference($data);

        if (empty($writer->getReader()->get('DscRcgGlobal', ValueType::mixed()))) {
            return;
        }

        // Un solo descuento o recargo se decodifica como un arreglo y no como
        // una lista, se normaliza siempre a una lista.
        $data = ArrayPath::wrap($data, 'DscRcgGlobal');

        $NroLinDR = 1;
        $items = $writer->getReader()->get('DscRcgGlobal', ValueType::arrayOf(ValueType::map()), []);
        foreach ($items as $key => $dr) {
            $writer->set('DscRcgGlobal.' . $key, array_merge([
                'NroLinDR' => $NroLinDR++,
            ], $dr));
        }
    }

    /**
     * Si existe una o más referencias se normalizan.
     *
     * @param array<array-key, mixed> $data
     * @return void
     */
    private function normalizeReferencias(array &$data): void
    {
        $writer = ArrayWriter::fromReference($data);

        if (empty($writer->getReader()->get('Referencia', ValueType::mixed()))) {
            return;
        }

        // Una sola referencia se decodifica como un arreglo y no como una
        // lista, se normaliza siempre a una lista.
        $data = ArrayPath::wrap($data, 'Referencia');

        $NroLinRef = 1;
        $items = $writer->getReader()->get('Referencia', ValueType::arrayOf(ValueType::map()), []);
        foreach ($items as $key => $item) {
            $r = array_merge([
                'NroLinRef' => $NroLinRef++,
                'TpoDocRef' => null,
                'IndGlobal' => null,
                'FolioRef' => null,
                'RUTOtr' => null,
                'FchRef' => date('Y-m-d'),
                'CodRef' => null,
                'RazonRef' => null,
            ], $item);

            // Si la fecha de la referencia se pasó como entero es un timestamp
            // y se convierte a string.
            if (is_numeric($r['FchRef'])) {
                $r['FchRef'] = date('Y-m-d', ValueCaster::requireInt($r['FchRef']));
            }

            $writer->set('Referencia.' . $key, $r);
        }
    }

    /**
     * Asegura que exista TpoTranVenta.
     *
     * Si no existe y el documento lo requiere se asigna "Ventas del giro" como
     * valor por defecto.
     *
     * @param array<array-key, mixed> $data
     * @param bool $requiereTpoTranVenta
     * @return void
     */
    private function normalizeTpoTranVenta(
        array &$data,
        bool $requiereTpoTranVenta
    ): void {
        $writer = ArrayWriter::fromReference($data);

        if (
            $requiereTpoTranVenta
            && empty($writer->getReader()->get('Encabezado.IdDoc.TpoTranVenta', ValueType::mixed()))
        ) {
            $writer->set('Encabezado.IdDoc.TpoTranVenta', 1);
        }
    }

    /**
     * Normaliza los datos del emisor completando los datos requeridos que
     * puedan faltar usando un proveedor de datos de emisor.
     *
     * @param array<array-key, mixed> $data
     * @return void
     */
    public function normalizeEmisor(array &$data): void
    {
        $writer = ArrayWriter::fromReference($data);

        // Crear emisor con los datos del documento.
        $emisor = $this->emisorFactory->create(
            $writer->getReader()->requireMap('Encabezado.Emisor')
        );

        // Obtener datos del emisor.
        $emisor = $this->emisorProvider->retrieve($emisor);

        // Actualizar $data si es necesario.
        $defaults = [
            'RUTEmisor' => $emisor->getRut(),
            'RznSoc' => $emisor->getRazonSocial(),
            'GiroEmis' => $emisor->getGiro(),
            'Telefono' => $emisor->getTelefono(),
            'CorreoEmisor' => $emisor->getEmail(),
            'Acteco' => $emisor->getActividadEconomica(),
            'DirOrigen' => $emisor->getDireccion(),
            'CmnaOrigen' => $emisor->getComuna(),
            'CdgSIISucur' => $emisor->getCodigoSucursal(),
            'CdgVendedor' => $emisor->getVendedor(),
        ];
        foreach ($defaults as $field => $default) {
            $path = 'Encabezado.Emisor.' . $field;
            $writer->set($path, $writer->getReader()->get($path, ValueType::mixed()) ?: $default);
        }

        // Si la comuna es un número se asume que es el código oficial.
        $this->normalizeComuna($writer, 'Encabezado.Emisor.CmnaOrigen');
    }

    /**
     * Normaliza los datos del receptor completando los datos requeridos que
     * puedan faltar usando un proveedor de datos de receptor.
     *
     * @param array<array-key, mixed> $data
     * @return void
     */
    public function normalizeReceptor(array &$data): void
    {
        $writer = ArrayWriter::fromReference($data);

        // Crear receptor con los datos del documento.
        $receptor = $this->receptorFactory->create(
            $writer->getReader()->requireMap('Encabezado.Receptor')
        );

        // Obtener datos del receptor.
        $receptor = $this->receptorProvider->retrieve($receptor);

        // Actualizar $data si es necesario.
        $defaults = [
            'RUTRecep' => $receptor->getRut(),
            'RznSocRecep' => $receptor->getRazonSocial(),
            'GiroRecep' => $receptor->getGiro(),
            'Contacto' => $receptor->getTelefono(),
            'CorreoRecep' => $receptor->getEmail(),
            'DirRecep' => $receptor->getDireccion(),
            'CmnaRecep' => $receptor->getComuna(),
        ];
        foreach ($defaults as $field => $default) {
            $path = 'Encabezado.Receptor.' . $field;
            $writer->set($path, $writer->getReader()->get($path, ValueType::mixed()) ?: $default);
        }

        // Si la comuna es un número se asume que es el código oficial.
        $this->normalizeComuna($writer, 'Encabezado.Receptor.CmnaRecep');
    }

    /**
     * Si la comuna es un número se asume que es el código oficial y se
     * reemplaza por su nombre.
     *
     * @param ArrayWriter $writer Escritor de los datos del documento.
     * @param string $path Ruta de la comuna en los datos.
     * @return void
     */
    private function normalizeComuna(ArrayWriter $writer, string $path): void
    {
        $comuna = $writer->getReader()->get($path, ValueType::mixed());
        if (!is_numeric($comuna)) {
            return;
        }

        $codigo = Comuna::tryFrom((int) $comuna);

        $writer->set($path, $codigo !== null ? $codigo->getNombre() : (string) $comuna);
    }
}

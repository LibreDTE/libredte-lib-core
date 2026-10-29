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

namespace libredte\lib\Core\Provider;

use Derafu\Repository\Contract\RepositorySourceProviderInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaClausulaVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaFormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaModalidadVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPuerto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTipoBulto;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTransporte;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaUnidad;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPagoExportacion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\MedioPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TagXml;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Traslado;

/**
 * Declara las fuentes de los repositorios incluidos en LibreDTE Core.
 */
final readonly class RepositorySourceProvider implements RepositorySourceProviderInterface
{
    /**
     * @param string $projectDir Directorio de la biblioteca que contiene los catálogos.
     */
    public function __construct(private string $projectDir)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getSources(): iterable
    {
        $directory = $this->projectDir . '/resources/data/repository';

        return [
            AduanaClausulaVenta::class => $directory . '/aduana_clausulas_venta.php',
            AduanaFormaPago::class => $directory . '/aduana_formas_pago.php',
            AduanaModalidadVenta::class => $directory . '/aduana_modalidades_venta.php',
            AduanaMoneda::class => $directory . '/aduana_moneda.php',
            AduanaPais::class => $directory . '/aduana_paises.php',
            AduanaPuerto::class => $directory . '/aduana_puertos.php',
            AduanaTipoBulto::class => $directory . '/aduana_tipos_bulto.php',
            AduanaTransporte::class => $directory . '/aduana_transportes.php',
            AduanaUnidad::class => $directory . '/aduana_unidades.php',
            Comuna::class => $directory . '/comunas.php',
            TipoDocumentoInterface::class => $directory . '/tipos_documento.php',
            FormaPago::class => $directory . '/formas_pago.php',
            FormaPagoExportacion::class => $directory . '/formas_pago_exportacion.php',
            ImpuestoAdicionalRetencion::class => $directory . '/impuestos_adicionales_retenciones.php',
            MedioPago::class => $directory . '/medios_pago.php',
            TagXml::class => $directory . '/tag_xml.php',
            Traslado::class => $directory . '/traslados.php',
        ];
    }
}

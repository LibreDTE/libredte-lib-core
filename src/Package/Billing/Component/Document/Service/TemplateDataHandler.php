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

namespace libredte\lib\Core\Package\Billing\Component\Document\Service;

use Derafu\Lib\Core\Enum\Currency;
use Derafu\Lib\Core\Helper\Date;
use Derafu\Lib\Core\Helper\Rut;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Template\Abstract\AbstractTemplateDataHandler;
use Derafu\Lib\Core\Package\Prime\Component\Template\Contract\DataHandlerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTransporte;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPagoExportacion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\MedioPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TagXml;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Traslado;
use TCPDF2DBarcode;

/**
 * Servicio para traducir los datos de los documentos a su representación para
 * ser utilizada en la renderización del documento.
 */
class TemplateDataHandler extends AbstractTemplateDataHandler implements DataHandlerInterface
{
    /**
     * Constructor del handler.
     *
     * @param EntityComponentInterface $entityComponent
     */
    public function __construct(
        private EntityComponentInterface $entityComponent
    ) {
    }

    /**
     * Mapa de campos a handlers para los documentos tributarios electrónicos.
     *
     * @return array
     */
    protected function createHandlers(): array
    {
        return [
            // Tipos de documento.
            'TipoDTE' => $this->entityComponent->getRepository(
                TipoDocumentoInterface::class
            ),
            'TpoDocRef' => 'alias:TipoDTE',
            // RUT.
            'RUTEmisor' => fn (string $rut) => Rut::formatFull($rut),
            'RUTRecep' => 'alias:RUTEmisor',
            'RUTSolicita' => 'alias:RUTEmisor',
            'RUTTrans' => 'alias:RUTEmisor',
            'RUTChofer' => 'alias:RUTEmisor',
            // Comuna.
            'CdgSIISucur' => fn (string $comuna) =>
                $this->entityComponent->getRepository(
                    Comuna::class
                )->find($comuna)->getDireccionRegional()
            ,
            'CiudadOrigen' => fn (string $comuna) =>
                $this->entityComponent->getRepository(
                    Comuna::class
                )->find($comuna)->getCiudad()
            ,
            'CiudadRecep' => 'alias:CiudadOrigen',
            // Fechas largas.
            'FchEmis' => fn (string $fecha) => Date::formatSpanish($fecha),
            'FchRef' => 'alias:FchEmis',
            'FchVenc' => 'alias:FchEmis',
            'FchCancel' => 'alias:FchEmis',
            // Fechas cortas.
            'PeriodoDesde' => function (string $fecha) {
                $timestamp = strtotime($fecha);
                return date('d/m/Y', $timestamp);
            },
            'PeriodoHasta' => 'alias:PeriodoDesde',
            'FchPago' => 'alias:PeriodoDesde',
            // Solo año de una fecha.
            'FchResol' => fn (string $fecha) => explode('-', $fecha, 2)[0],
            // Datos de Aduana.
            'Aduana' => function (string $tagXmlAndValue) {
                [$tagXml, $value] = explode(':', $tagXmlAndValue);
                $xmlTagEntity = $this->entityComponent->getRepository(
                    TagXml::class
                )->find($tagXml);
                $name = $xmlTagEntity->getGlosa();
                $entityClass = $xmlTagEntity->getEntity();
                if ($entityClass) {
                    $description = $this->entityComponent->getRepository(
                        $entityClass
                    )->find($value)->getGlosa();
                } else {
                    $description = $this->handle($tagXml, $value);
                }
                if ($name && !in_array($description, [false, null, ''], true)) {
                    return $name . ': ' . $description;
                }
                return '';
            },
            'TotItems' => 'alias:Number',
            // Otros datos que se mapean de un código a su glosa usando un
            // repositorio.
            'TipoImp' => $this->entityComponent->getRepository(
                ImpuestoAdicionalRetencion::class
            ),
            'MedioPago' => $this->entityComponent->getRepository(
                MedioPago::class
            ),
            'FmaPago' => $this->entityComponent->getRepository(
                FormaPago::class
            ),
            'FmaPagExp' => $this->entityComponent->getRepository(
                FormaPagoExportacion::class
            ),
            'Nacionalidad' => $this->entityComponent->getRepository(
                AduanaPais::class
            ),
            'CodPaisRecep' => 'alias:Nacionalidad',
            'IndTraslado' => $this->entityComponent->getRepository(
                Traslado::class
            ),
            'CodViaTransp' => $this->entityComponent->getRepository(
                AduanaTransporte::class
            ),
            //  Timbre Electrónico del Documento (TED).
            'TED' => function (string $timbre) {
                $pdf417 = new TCPDF2DBarcode($timbre, 'PDF417,,5');
                $png = $pdf417->getBarcodePngData(1, 1, [0,0,0]);
                return 'data:image/png;base64,' . base64_encode($png);
            },
            // Montos sin decimales y formato de Chile en separadores.
            'Number' => function (int|float|string $num) {
                $num = round((float) $num);
                return number_format($num, 0, ',', '.');
            },
            // Montos según moneda.
            'MontoMoneda' => function (string $value) {
                [$codigo, $num] = explode(':', $value);
                $result = $this->entityComponent->getRepository(
                    AduanaMoneda::class
                )->findBy(['glosa' => $codigo]);
                $currency = ($result[0] ?? null)?->getCurrency() ?? Currency::XXX;
                return $currency->format((float) $num);
            },
        ];
    }
}

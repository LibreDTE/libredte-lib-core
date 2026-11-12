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

namespace libredte\lib\Core\Package\Billing\Component\Document\Service;

use Com\Tecnick\Barcode\Barcode;
use Derafu\Date\Contract\DateFormatterInterface;
use Derafu\Enum\Currency;
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Renderer\Abstract\AbstractHandlerFormatter;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Translation\Contract\TranslatableMessageInterface;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaClausulaVenta;
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
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Servicio para traducir los datos de los documentos a su representación para
 * ser utilizada en la renderización del documento.
 */
class TemplateDataFormatter extends AbstractHandlerFormatter
{
    /**
     * Constructor del handler.
     *
     * @param RepositoryManagerInterface $repositoryManager
     * @param DateFormatterInterface $dateFormatter Describe las fechas con
     * mensajes traducibles.
     * @param TranslatorInterface|null $translator Traductor de los mensajes. Es
     * responsabilidad de la aplicación proveerlo: sin traductor los mensajes
     * se escriben en inglés.
     * @param string $locale Idioma al que se traducen los mensajes.
     */
    public function __construct(
        private RepositoryManagerInterface $repositoryManager,
        private DateFormatterInterface $dateFormatter,
        private ?TranslatorInterface $translator = null,
        private string $locale = 'es'
    ) {
    }

    /**
     * Traduce un mensaje al idioma configurado, o lo entrega como texto si no
     * hay traductor.
     */
    private function translate(TranslatableMessageInterface $message): string
    {
        return $this->translator === null
            ? (string) $message
            : $message->trans($this->translator, $this->locale)
        ;
    }

    /**
     * Mapa de campos a handlers para los documentos tributarios electrónicos.
     *
     * @return array<string, mixed>
     */
    protected function createHandlers(): array
    {
        return [
            // Tipos de documento.
            'TipoDTE' => $this->repositoryManager->getRepository(
                TipoDocumentoInterface::class
            ),
            'TpoDocRef' => 'alias:TipoDTE',
            // RUT.
            'RUTEmisor' => fn (string $rut) => Rut::formatFull($rut),
            'RUTRecep' => 'alias:RUTEmisor',
            'RUTSolicita' => 'alias:RUTEmisor',
            'RUTTrans' => 'alias:RUTEmisor',
            'RUTChofer' => 'alias:RUTEmisor',
            // Comuna. El repositorio de comunas entrega una comuna "falsa" si
            // la solicitada no existe, por lo que su find() nunca retorna null.
            'CdgSIISucur' => fn (string $comuna) =>
                ValueCaster::requireObject(
                    $this->repositoryManager->getRepository(Comuna::class),
                    ComunaRepository::class
                )->find($comuna)->getDireccionRegional()
            ,
            'CiudadOrigen' => fn (string $comuna) =>
                ValueCaster::requireObject(
                    $this->repositoryManager->getRepository(Comuna::class),
                    ComunaRepository::class
                )->find($comuna)->getCiudad()
            ,
            'CiudadRecep' => 'alias:CiudadOrigen',
            // Fechas largas.
            'FchEmis' => fn (string $fecha) => $this->translate(
                $this->dateFormatter->formatDate($fecha)
            ),
            'FchRef' => 'alias:FchEmis',
            'FchVenc' => 'alias:FchEmis',
            'FchCancel' => 'alias:FchEmis',
            // Fechas cortas.
            'PeriodoDesde' => function (string $fecha) {
                $timestamp = ValueCaster::requireInt(strtotime($fecha));
                return date('d/m/Y', $timestamp);
            },
            'PeriodoHasta' => 'alias:PeriodoDesde',
            'FchPago' => 'alias:PeriodoDesde',
            'FchSalida' => 'alias:PeriodoDesde',
            'FchLlegada' => 'alias:PeriodoDesde',
            // Solo año de una fecha.
            'FchResol' => fn (string $fecha) => explode('-', $fecha, 2)[0],
            // Datos de Aduana.
            'Aduana' => function (string $tagXmlAndValue) {
                [$tagXml, $value] = explode(':', $tagXmlAndValue, 2);
                $xmlTagEntity = $this->repositoryManager->getRepository(
                    TagXml::class
                )->find($tagXml);
                // Nota: un tag desconocido se omite en silencio (se retorna
                // '') en lugar de informarse como error.
                if ($xmlTagEntity === null) {
                    return '';
                }
                $name = $xmlTagEntity->getGlosa();
                $entityClass = $xmlTagEntity->getEntity();
                if ($entityClass) {
                    $description = ValueCaster::requireString(
                        $this->repositoryManager->getRepository(
                            $entityClass
                        )->find($value)
                    );
                } else {
                    $description = $value;
                }
                if ($name && !in_array($description, [false, null, ''], true)) {
                    return $name . ': ' . $description;
                }
                return '';
            },
            'CodModVenta' => $this->repositoryManager->getRepository(
                AduanaModalidadVenta::class
            ),
            'CodClauVenta' => $this->repositoryManager->getRepository(
                AduanaClausulaVenta::class
            ),
            'CodViaTransp' => $this->repositoryManager->getRepository(
                AduanaTransporte::class
            ),
            'CodTpoBultos' => $this->repositoryManager->getRepository(
                AduanaTipoBulto::class
            ),
            'CodPaisRecep' => 'alias:Nacionalidad',
            'CodPaisDestin' => 'alias:Nacionalidad',
            'CodPtoEmbarque' => $this->repositoryManager->getRepository(
                AduanaPuerto::class
            ),
            'CodPtoDesemb' => 'alias:CodPtoEmbarque',
            'CodUnidMedTara' => $this->repositoryManager->getRepository(
                AduanaUnidad::class
            ),
            'CodUnidPesoBruto' => 'alias:CodUnidMedTara',
            'CodUnidPesoNeto' => 'alias:CodUnidMedTara',
            'Nacionalidad' => $this->repositoryManager->getRepository(
                AduanaPais::class
            ),
            'TotItems' => 'alias:Number',
            // Otros datos que se mapean de un código a su glosa usando un
            // repositorio.
            'TipoImp' => $this->repositoryManager->getRepository(
                ImpuestoAdicionalRetencion::class
            ),
            'MedioPago' => $this->repositoryManager->getRepository(
                MedioPago::class
            ),
            'FmaPago' => $this->repositoryManager->getRepository(
                FormaPago::class
            ),
            'FmaPagExp' => $this->repositoryManager->getRepository(
                FormaPagoExportacion::class
            ),
            'IndTraslado' => $this->repositoryManager->getRepository(
                Traslado::class
            ),
            //  Timbre Electrónico del Documento (TED).
            'TED' => function (string $timbre) {
                $pdf417 = (new Barcode())->getBarcodeObj('PDF417,,5', $timbre);
                $png = $pdf417->getPngData(false);
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
                $result = $this->repositoryManager->getRepository(
                    AduanaMoneda::class
                )->findBy(['glosa' => $codigo]);
                $currency = ($result[0] ?? null)?->getCurrency() ?? Currency::XXX;
                return $currency->format((float) $num);
            },
        ];
    }
}

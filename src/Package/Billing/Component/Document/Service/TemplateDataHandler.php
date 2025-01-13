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

use Closure;
use Derafu\Lib\Core\Helper\Rut;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Template\Contract\DataHandlerInterface;
use Derafu\Lib\Core\Package\Prime\Component\Template\Service\DataHandler;
use Derafu\Lib\Core\Support\Store\Contract\RepositoryInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaTransporte;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\FormaPago;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TagXml;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Traslado;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;
use TCPDF2DBarcode;

/**
 * Servicio para traducir los datos de los documentos a su representación para
 * ser utilizada en la renderización del documento.
 */
class TemplateDataHandler implements DataHandlerInterface
{
    /**
     * Mapa de handlers.
     *
     * @var array
     */
    private array $handlers;

    /**
     * Handler por defecto para manejar los casos.
     *
     * @var DataHandlerInterface
     */
    private DataHandlerInterface $handler;

    /**
     * Constructor del handler.
     *
     * @param EntityComponentInterface $entityComponent
     * @param DataHandlerInterface|null $handler
     */
    public function __construct(
        private EntityComponentInterface $entityComponent,
        DataHandlerInterface $handler = null
    ) {
        $this->handler = $handler ?? new DataHandler();
    }

    /**
     * @inheritDoc
     */
    public function handle(string $id, mixed $data): string
    {
        // Si no hay valor asignado en los datos se entrega un string vacio.
        if (!$data) {
            return '';
        }

        // Buscar el handler.
        $handler = $this->getHandler($id);

        // Ejecutar el handler sobre los datos para formatearlos.
        assert($this->handler instanceof DataHandler);
        return $this->handler->handle($id, $data, $handler);
    }

    /**
     * Obtiene el handler de un campo a partir de su ID.
     *
     * @param string $id
     * @return Closure|RepositoryInterface
     */
    private function getHandler(string $id): Closure|RepositoryInterface
    {
        if (!isset($this->handlers)) {
            $this->handlers = $this->createHandlers();
        }

        if (!isset($this->handlers[$id])) {
            throw new RendererException(sprintf(
                'El formato para %s no está definido. Los disponibles son: %s.',
                $id,
                implode(', ', array_keys($this->handlers))
            ));
        }

        if (is_string($this->handlers[$id]) && str_starts_with($this->handlers[$id], 'alias:')) {
            [$alias, $handler] = explode(':', $this->handlers[$id], 2);

            if (!isset($this->handlers[$handler])) {
                throw new RendererException(sprintf(
                    'El alias %s del formato para %s no está definido. Los disponibles son: %s.',
                    $handler,
                    $id,
                    implode(', ', array_keys($this->handlers))
                ));
            }

            return $this->handlers[$handler];
        }

        return $this->handlers[$id];
    }

    /**
     * Mapa de campos a handlers para los documentos tributarios electrónicos.
     *
     * @return array
     */
    private function createHandlers(): array
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
            'FchEmis' => function (string $fecha) {
                $timestamp = strtotime($fecha);
                return date('d/m/Y', $timestamp); // TODO: Formato largo.
            },
            'FchRef' => 'alias:FchEmis',
            'FchVenc' => 'alias:FchEmis',
            // Fechas cortas.
            'PeriodoDesde' => function (string $fecha) {
                $timestamp = strtotime($fecha);
                return date('d/m/Y', $timestamp);
            },
            'PeriodoHasta' => 'alias:PeriodoDesde',
            // Solo año de una fecha.
            'FchResol' => function (string $fecha) {
                return explode('-', $fecha, 2)[0];
            },
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
            // Otros datos que se mapean de un código a su glosa usando un
            // repositorio.
            'FmaPago' => $this->entityComponent->getRepository(
                FormaPago::class
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
            }
        ];
    }
}

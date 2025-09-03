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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\ApiResource;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafBagInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Worker que permite crear CAF falsos (usando CafFaker) para pruebas.
 */
#[Worker(name: 'caf_faker', component: 'identifier', package: 'billing')]
class CafFakerWorker extends AbstractWorker implements CafFakerWorkerInterface
{
    protected string $cafFakerClass = CafFaker::class;

    public function __construct(
        private XmlServiceInterface $xmlService,
        private CafLoaderWorkerInterface $cafLoader
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[ApiResource(
        parametersExample: [
            'emisor' => [
                'rut' => '12345678-9',
            ],
            'codigoDocumento' => 33,
            'folioDesde' => 1,
            'folioHasta' => 100,
        ],
    )]
    public function create(
        EmisorInterface $emisor,
        int $codigoDocumento,
        ?int $folioDesde = 1,
        ?int $folioHasta = null
    ): CafBagInterface {
        $xml = $this->createXml(
            $emisor,
            $codigoDocumento,
            $folioDesde,
            $folioHasta
        );

        return $this->cafLoader->load($xml);
    }

    /**
     * Crea un CAF falso y lo entrega como un documento XML.
     *
     * @param EmisorInterface $emisor
     * @param int $codigoDocumento
     * @param int $folioDesde
     * @param int|null $folioHasta
     * @return XmlDocumentInterface
     */
    protected function createXml(
        EmisorInterface $emisor,
        int $codigoDocumento,
        int $folioDesde,
        ?int $folioHasta = null
    ): XmlDocumentInterface {
        $array = $this->createArray(
            $emisor,
            $codigoDocumento,
            $folioDesde,
            $folioHasta
        );

        return $this->xmlService->encode($array);
    }

    /**
     * Crea un CAF falso y lo entrega como arreglo.
     *
     * @param EmisorInterface $emisor
     * @param int $codigoDocumento
     * @param int $folioDesde
     * @param int|null $folioHasta
     * @return array
     */
    protected function createArray(
        EmisorInterface $emisor,
        int $codigoDocumento,
        int $folioDesde,
        ?int $folioHasta = null
    ): array {
        // Si no se indica folio final se creará un CAF con un solo folio.
        if ($folioHasta === null) {
            $folioHasta = $folioDesde;
        }

        // Generar la estructura del CAF falso como arreglo.
        $class = $this->cafFakerClass;
        return (new $class())
            ->setEmisor($emisor->getRut(), $emisor->getRazonSocial())
            ->setTipoDocumento($codigoDocumento)
            ->setRangoFolios($folioDesde, $folioHasta)
            ->toArray()
        ;
    }
}

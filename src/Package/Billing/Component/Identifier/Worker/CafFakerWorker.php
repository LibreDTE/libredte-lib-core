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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
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
    #[Operation(
        parameters: [
            'emisor' => [
                'example' => [
                    'rut' => '12345678-9',
                    'razon_social' => 'Empresa de Prueba SpA',
                ],
            ],
            'codigoDocumento' => ['example' => 33],
            'folioDesde' => ['example' => 1],
            'folioHasta' => ['example' => 100],
        ],
    )]
    public function create(
        EmisorInterface $emisor,
        int $codigoDocumento,
        ?int $folioDesde = 1,
        ?int $folioHasta = null
    ): CafInterface {
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

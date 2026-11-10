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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Crea un CAF falso para pruebas, como documento XML.
 *
 * Usa `CreateArrayJob` para generar la estructura del CAF y la codifica a XML.
 */
#[Job(name: 'create_xml', worker: 'caf_faker', component: 'identifier', package: 'billing')]
class CreateXmlJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private CreateArrayJob $createArrayJob,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Crea un CAF falso y lo entrega como un documento XML.
     *
     * @param EmisorInterface $emisor Emisor del CAF.
     * @param int $codigoDocumento Código del tipo de documento.
     * @param int $folioDesde Folio inicial.
     * @param int|null $folioHasta Folio final. Si es `null`, el CAF tendrá un
     * solo folio (el inicial).
     * @return XmlDocumentInterface
     */
    public function create(
        EmisorInterface $emisor,
        int $codigoDocumento,
        int $folioDesde,
        ?int $folioHasta = null
    ): XmlDocumentInterface {
        $array = $this->createArrayJob->create(
            $emisor->getRut(),
            $emisor->getRazonSocial(),
            $codigoDocumento,
            $folioDesde,
            $folioHasta ?? $folioDesde
        );

        return $this->xmlService->encode($array);
    }
}

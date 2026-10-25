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
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;

/**
 * Worker que permite cargar archivos CAF.
 */
#[Worker(name: 'caf_loader', component: 'identifier', package: 'billing')]
class CafLoaderWorker extends AbstractWorker implements CafLoaderWorkerInterface
{
    protected string $cafClass = Caf::class;

    public function __construct(
        private RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'xml' => ['example' => ''],
        ],
    )]
    public function load(string|XmlDocumentInterface $xml): CafInterface
    {
        $class = $this->cafClass;
        $caf = new $class($xml);

        $this->validateTipoDocumento($caf->getTipoDocumento());

        return $caf;
    }

    /**
     * Valida que el código de tipo de documento del CAF exista en el repositorio.
     *
     * @param int $codigoTipoDocumento
     * @return void
     * @throws CafLoaderException
     */
    private function validateTipoDocumento(int $codigoTipoDocumento): void
    {
        $tipoDocumento = $this->repositoryManager
            ->getRepository(TipoDocumentoInterface::class)
            ->find($codigoTipoDocumento)
        ;

        if (!$tipoDocumento) {
            throw new CafLoaderException([
                'Document type code {code} was not found to process the CAF.',
                'code' => $codigoTipoDocumento,
            ]);
        }
    }
}

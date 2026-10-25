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

namespace libredte\lib\Core\Package\Billing\Component\Book;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ValidatorWorkerInterface;

/**
 * Componente "billing.book".
 *
 * Gestión de libros y registros tributarios electrónicos:
 * Libro de Ventas, Libro de Compras, Libro de Boletas, Libro de Guías de
 * Despacho y Resumen de Ventas Diarias (ConsumoFolios).
 *
 * El flujo principal usa dos workers en secuencia:
 *   1. `LoaderWorker`: normaliza los datos de entrada según tipo y formato.
 *   2. `BuilderWorker`: construye el XML y retorna la entidad resultante.
 */
#[Component(name: 'book', package: 'billing')]
class BookComponent extends AbstractComponent implements BookComponentInterface
{
    public function __construct(
        private LoaderWorkerInterface $loaderWorker,
        private BuilderWorkerInterface $builderWorker,
        private ValidatorWorkerInterface $validatorWorker,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'loader' => $this->loaderWorker,
            'builder' => $this->builderWorker,
            'validator' => $this->validatorWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getLoaderWorker(): LoaderWorkerInterface
    {
        return $this->loaderWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getBuilderWorker(): BuilderWorkerInterface
    {
        return $this->builderWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getValidatorWorker(): ValidatorWorkerInterface
    {
        return $this->validatorWorker;
    }
}

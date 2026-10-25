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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\L10n\Cl\Rut\Rut;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorStrategyInterface;

/**
 * Clase abstracta (base) para las estrategias de validación de documentos
 * tributarios.
 */
abstract class AbstractValidatorStrategy extends AbstractStrategy implements ValidatorStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Validar el del Emisor y Receptor.
        Rut::validate($data['Encabezado']['Emisor']['RUTEmisor']);
        Rut::validate($data['Encabezado']['Receptor']['RUTRecep']);

        // Aplicar validación de la estrategias.
        $this->validateDocument($bag);
    }

    /**
     * Validación personalizada de cada estrategia.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    abstract protected function validateDocument(DocumentBagInterface $bag): void;
}

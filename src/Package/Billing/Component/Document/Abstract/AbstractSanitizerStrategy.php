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

use Derafu\Array\ArrayWriter;
use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\L10n\Cl\Rut\Rut;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerStrategyInterface;

/**
 * Clase abstracta (base) para las estrategias de sanitización de documentos
 * tributarios.
 */
abstract class AbstractSanitizerStrategy extends AbstractStrategy implements SanitizerStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function sanitize(DocumentBagInterface $bag): array
    {
        $this->sanitizeEssentials($bag);
        $this->sanitizeDocument($bag);

        return $bag->requireNormalizedData();
    }

    /**
     * Sanitización personalizada de cada estrategia.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    abstract protected function sanitizeDocument(DocumentBagInterface $bag): void;

    /**
     * Limpia los datos esenciales del documento.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function sanitizeEssentials(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();
        $writer = ArrayWriter::fromReference($data);

        // Los campos se sanitizan de manera independiente, por lo que se leen
        // todos desde el mismo lector.
        $reader = $bag->requireNormalizedDataReader();

        // Formatear los RUT.
        foreach (['Emisor.RUTEmisor', 'Receptor.RUTRecep'] as $field) {
            $path = 'Encabezado.' . $field;
            $writer->set($path, Rut::format($reader->requireAsString($path)));
        }

        // Limpiar datos del emisor.
        $acteco = $reader->getAsString('Encabezado.Emisor.Acteco');
        if (!empty($acteco) && strlen($acteco) === 5) {
            $writer->set('Encabezado.Emisor.Acteco', '0' . $acteco);
        }

        // Limpiar datos del receptor.
        $maxLengths = [
            'RznSocRecep' => 100,
            'GiroRecep' => 40,
            'Contacto' => 80,
            'CorreoRecep' => 80,
            'DirRecep' => 70,
            'CmnaRecep' => 20,
        ];
        foreach ($maxLengths as $field => $maxLength) {
            $path = 'Encabezado.Receptor.' . $field;
            $value = $reader->getAsString($path);
            if (!empty($value)) {
                $writer->set($path, mb_substr($value, 0, $maxLength));
            }
        }

        // Actualizar los datos normalizados con los sanitizados.
        $bag->setNormalizedData($writer->getReader()->toMap());
    }
}

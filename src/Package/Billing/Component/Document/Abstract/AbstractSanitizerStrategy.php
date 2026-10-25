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
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\SanitizerException;

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

        return $bag->getNormalizedData();
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
        $data = $bag->getNormalizedData();

        // Si no viene un folio asignado error.
        if (!isset($data['Encabezado']['IdDoc']['Folio'])) {
            throw new SanitizerException(
                'Es obligatorio indicar el folio del documento.'
            );
        }

        // Formatear los RUT.
        $data['Encabezado']['Emisor']['RUTEmisor'] = Rut::format(
            $data['Encabezado']['Emisor']['RUTEmisor']
        );
        $data['Encabezado']['Receptor']['RUTRecep'] = Rut::format(
            $data['Encabezado']['Receptor']['RUTRecep']
        );

        // Limpiar datos del emisor.
        if (!empty($data['Encabezado']['Emisor']['Acteco'])) {
            if (strlen((string)$data['Encabezado']['Emisor']['Acteco']) === 5) {
                $data['Encabezado']['Emisor']['Acteco'] =
                    '0' . $data['Encabezado']['Emisor']['Acteco']
                ;
            }
        }

        // Limpiar datos del receptor.
        if (!empty($data['Encabezado']['Receptor']['RznSocRecep'])) {
            $data['Encabezado']['Receptor']['RznSocRecep'] =
                mb_substr($data['Encabezado']['Receptor']['RznSocRecep'], 0, 100)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['GiroRecep'])) {
            $data['Encabezado']['Receptor']['GiroRecep'] =
                mb_substr($data['Encabezado']['Receptor']['GiroRecep'], 0, 40)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['Contacto'])) {
            $data['Encabezado']['Receptor']['Contacto'] =
                mb_substr($data['Encabezado']['Receptor']['Contacto'], 0, 80)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['CorreoRecep'])) {
            $data['Encabezado']['Receptor']['CorreoRecep'] =
                mb_substr($data['Encabezado']['Receptor']['CorreoRecep'], 0, 80)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['DirRecep'])) {
            $data['Encabezado']['Receptor']['DirRecep'] =
                mb_substr($data['Encabezado']['Receptor']['DirRecep'], 0, 70)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['CmnaRecep'])) {
            $data['Encabezado']['Receptor']['CmnaRecep'] =
                mb_substr($data['Encabezado']['Receptor']['CmnaRecep'], 0, 20)
            ;
        }

        // Actualizar los datos normalizados con los sanitizados.
        $bag->setNormalizedData($data);
    }
}

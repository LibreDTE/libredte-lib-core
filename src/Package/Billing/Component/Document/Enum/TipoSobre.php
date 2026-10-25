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

namespace libredte\lib\Core\Package\Billing\Component\Document\Enum;

/**
 * Enum del Tag XML donde va el documento (tag DTE).
 */
enum TipoSobre: int
{
    /**
     * Caso que representa que el envío es de DTE.
     *
     * Este sobre se usa para todo menos boletas.
     */
    case ENVIO_DTE = 0;

    /**
     * Caso que representa que el envío es de boletas.
     */
    case ENVIO_BOLETA = 1;

    /**
     * Configuración (reglas) para el documento XML del sobre de documentos.
     */
    private const CONFIG = [
        self::ENVIO_DTE->value => [
            // Máxima cantidad de tipos de documentos en el sobre.
            'SubTotDTE_max' => 20,
            // Máxima cantidad de documentos en un sobre.
            'DTE_max' => 2000,
            // Tag XML para el sobre.
            'tag' => 'EnvioDTE',
            // Schema principal del XML del sobre.
            'schema' => 'EnvioDTE_v10.xsd',
        ],
        self::ENVIO_BOLETA->value => [
            // Máxima cantidad de tipos de documentos en el sobre.
            'SubTotDTE_max' => 2,
            // Máxima cantidad de documentos en un sobre.
            'DTE_max' => 1000,
            // Tag XML para el sobre.
            'tag' => 'EnvioBOLETA',
            // Schema principal del XML del sobre.
            'schema' => 'EnvioBOLETA_v11.xsd',
        ],
    ];

    /**
     * Obtiene el nombre del tipo de sobre.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return $this->name;
    }

    /**
     * Obtiene la cantidad máxima de tipos de documentos que el sobre puede
     * tener.
     *
     * @return int
     */
    public function getMaxTiposDocumentos(): int
    {
        return self::CONFIG[$this->value]['SubTotDTE_max'];
    }

    /**
     * Obtiene la cantidad máximo de documentos que se pueden incluir en el
     * sobre.
     *
     * @return int
     */
    public function getMaxDocumentos(): int
    {
        return self::CONFIG[$this->value]['DTE_max'];
    }

    /**
     * Obtiene el tag del XML del nodo raíz del sobre.
     *
     * @return string
     */
    public function getTagXml(): string
    {
        return self::CONFIG[$this->value]['tag'];
    }

    /**
     * Obtiene el nombre del esquema que se debe utilizar en el XML del sobre.
     *
     * @return string
     */
    public function getSchema(): string
    {
        return self::CONFIG[$this->value]['schema'];
    }

    /**
     * Indica si el sobre es de boletas.
     *
     * @return bool `true`es sobre de boletas, `false` es de otros DTE.
     */
    public function areBoletas(): bool
    {
        return $this === self::ENVIO_BOLETA;
    }
}

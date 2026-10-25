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

namespace libredte\lib\Core\Package\Billing\Component\Book\Entity;

use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ResumenVentasDiariasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;

/**
 * Entidad que representa el Resumen de Ventas Diarias (RVD).
 *
 * Anteriormente denominado Consumo de Folios (RCOF). El XML usa el tag raíz
 * `<ConsumoFolios>` por compatibilidad con el esquema `ConsumoFolio_v10.xsd`.
 */
class ResumenVentasDiarias extends AbstractBook implements ResumenVentasDiariasInterface
{
    /**
     * Tipo de libro.
     */
    protected TipoLibro $tipo = TipoLibro::RVD;

    /**
     * {@inheritDoc}
     *
     * El RVD usa `DocumentoConsumoFolios` en lugar de `EnvioLibro`.
     */
    public function getId(): string
    {
        return (string) ($this->getXmlDocument()->query('//DocumentoConsumoFolios/@ID') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaInicial(): string
    {
        return (string) ($this->getXmlDocument()->query('//Caratula/FchInicio') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaFinal(): string
    {
        return (string) ($this->getXmlDocument()->query('//Caratula/FchFinal') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getSecuencia(): int
    {
        return (int) ($this->getXmlDocument()->query('//Caratula/SecEnvio') ?? 1);
    }
}

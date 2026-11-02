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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract;

use Derafu\Array\ArrayCaster;
use Derafu\Array\ArrayReader;
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Value\ValueType;

/**
 * Clase abstracta para las implementaciones de fábricas de contribuyentes,
 * emisores y receptores de documentos tributarios.
 */
abstract class AbstractContribuyenteFactory
{
    /**
     * Crea un lector de los datos de entrada de la fábrica.
     *
     * Un `false` en los datos es un valor que no se informó, igual que `null`.
     *
     * @param array<string, mixed> $data
     */
    protected function createReader(array $data): ArrayReader
    {
        return ArrayReader::fromArray(ArrayCaster::nullify($data, [false]));
    }

    /**
     * Normaliza los datos del contribuyente que podrían venir en diferentes
     * índices.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function normalizeData(array $data): array
    {
        $reader = $this->createReader($data);
        $normalized = [];

        $rutType = ValueType::oneOf(ValueType::int(), ValueType::string());
        $normalized['rut'] = (
            $reader->get('rut', $rutType)
            ?? $reader->get('RUTEmisor', $rutType)
            ?? $reader->get('RUTRecep', $rutType)
        ) ?: '66666666-6';

        if (is_int($normalized['rut'])) {
            $normalized['rut'] = Rut::addDv($normalized['rut']);
        }

        $normalized['razon_social'] = (
            $reader->getAsString('razon_social')
            ?? $reader->getAsString('RznSoc')
            ?? $reader->getAsString('RznSocEmisor')
            ?? $reader->getAsString('RznSocRecep')
        ) ?: null;

        $normalized['giro'] = (
            $reader->getAsString('giro')
            ?? $reader->getAsString('GiroEmis')
            ?? $reader->getAsString('GiroEmisor')
            ?? $reader->getAsString('GiroRecep')
        ) ?: null;

        $normalized['actividad_economica'] = (
            $reader->getAsInt('actividad_economica')
            ?? $reader->getAsInt('Acteco')
        ) ?: null;

        $normalized['telefono'] = (
            $reader->getAsString('telefono')
            ?? $reader->getAsString('Telefono')
            ?? $reader->getAsString('Contacto')
        ) ?: null;

        $normalized['email'] = (
            $reader->getAsString('email')
            ?? $reader->getAsString('CorreoEmisor')
            ?? $reader->getAsString('CorreoRecep')
        ) ?: null;

        $normalized['direccion'] = (
            $reader->getAsString('direccion')
            ?? $reader->getAsString('DirOrigen')
            ?? $reader->getAsString('DirRecep')
        ) ?: null;

        $normalized['comuna'] = (
            $reader->getAsString('comuna')
            ?? $reader->getAsString('CmnaOrigen')
            ?? $reader->getAsString('CmnaRecep')
        ) ?: null;

        $normalized['ciudad'] = (
            $reader->getAsString('ciudad')
            ?? $reader->getAsString('CiudadOrigen')
            ?? $reader->getAsString('CiudadRecep')
        ) ?: null;

        return $normalized;
    }
}

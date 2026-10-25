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

use Derafu\L10n\Cl\Rut\Rut;

/**
 * Clase abstracta para las implementaciones de fábricas de contribuyentes,
 * emisores y receptores de documentos tributarios.
 */
abstract class AbstractContribuyenteFactory
{
    /**
     * Normaliza los datos del contribuyente que podrían venir en diferentes
     * índices.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $normalized = [];

        $normalized['rut'] = (
            $data['rut']
            ?? $data['RUTEmisor']
            ?? $data['RUTRecep']
            ?? null
        ) ?: '66666666-6';

        if (is_int($normalized['rut'])) {
            $normalized['rut'] = Rut::addDv($normalized['rut']);
        }

        $normalized['razon_social'] = (
            (string) (
                $data['razon_social']
                ?? $data['RznSoc']
                ?? $data['RznSocEmisor']
                ?? $data['RznSocRecep']
                ?? null
            )
        ) ?: null;

        $normalized['giro'] = (
            (string) (
                $data['giro']
                ?? $data['GiroEmis']
                ?? $data['GiroEmisor']
                ?? $data['GiroRecep']
                ?? null
            )
        ) ?: null;

        $normalized['actividad_economica'] = (
            (int) (
                $data['actividad_economica']
                ?? $data['Acteco']
                ?? null
            )
        ) ?: null;

        $normalized['telefono'] = (
            (string) (
                $data['telefono']
                ?? $data['Telefono']
                ?? $data['Contacto']
                ?? null
            )
        ) ?: null;

        $normalized['email'] = (
            (string) (
                $data['email']
                ?? $data['CorreoEmisor']
                ?? $data['CorreoRecep']
                ?? null
            )
        ) ?: null;

        $normalized['direccion'] = (
            (string) (
                $data['direccion']
                ?? $data['DirOrigen']
                ?? $data['DirRecep']
                ?? null
            )
        ) ?: null;

        $normalized['comuna'] = (
            (string) (
                $data['comuna']
                ?? $data['CmnaOrigen']
                ?? $data['CmnaRecep']
                ?? null
            )
        ) ?: null;

        $normalized['ciudad'] = (
            (string) (
                $data['ciudad']
                ?? $data['CiudadOrigen']
                ?? $data['CiudadRecep']
                ?? null
            )
        ) ?: null;

        return $normalized;
    }
}

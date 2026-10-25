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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Factory;

use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Support\Factory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;

/**
 * Fábrica de una entidad de emisor.
 */
class EmisorFactory extends AbstractContribuyenteFactory implements EmisorFactoryInterface
{
    /**
     * Clase de la entidad de los emisores.
     *
     * @var class-string<EmisorInterface>
     */
    private string $class = Emisor::class;

    /**
     * {@inheritDoc}
     */
    public function create(array $data): EmisorInterface
    {
        $normalized = $this->normalizeData($data);

        [$normalized['rut'], $normalized['dv']] = Rut::toArray($normalized['rut']);

        $emisor = Factory::create($normalized, $this->class);

        $autorizacion_dte = $data['autorizacion_dte']
            ?? $data['autorizacionDte']
            ?? null
        ;

        if (!empty($autorizacion_dte)) {
            $emisor->setAutorizacionDte(
                new AutorizacionDte(
                    $autorizacion_dte['fecha_resolucion']
                        ?? $autorizacion_dte['FchResol']
                        ?? '',
                    (int) (
                        $autorizacion_dte['numero_resolucion']
                        ?? $autorizacion_dte['NroResol']
                        ?? 0
                    )
                )
            );
        }

        return $emisor;
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeData(array $data): array
    {
        $normalized = parent::normalizeData($data);

        $normalized['sucursal'] = (
            (string) (
                $data['sucursal']
                ?? $data['Sucursal']
                ?? null
            )
        ) ?: null;

        $normalized['codigo_sucursal'] = (
            (int) (
                $data['codigo_sucursal']
                ?? $data['CdgSIISucur']
                ?? null
            )
        ) ?: null;

        $normalized['vendedor'] = (
            (string) (
                $data['vendedor']
                ?? $data['CdgVendedor']
                ?? null
            )
        ) ?: null;

        $normalized['logo'] = (
            (string) (
                $data['logo']
                ?? null
            )
        ) ?: null;

        return $normalized;
    }
}

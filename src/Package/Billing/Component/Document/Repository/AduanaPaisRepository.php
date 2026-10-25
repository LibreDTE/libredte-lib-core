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

namespace libredte\lib\Core\Package\Billing\Component\Document\Repository;

use Derafu\Repository\Repository;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;

/**
 * Repositorio para trabajar con los países de aduana.
 */
class AduanaPaisRepository extends Repository
{
    /**
     * Entrega el país de aduana que tiene el código ISO 3166-1 alpha-2
     * indicado (el que usa UBL en `Country/IdentificationCode`).
     *
     * El código se compara sin distinguir mayúsculas y minúsculas. Si el país
     * no existe en el SII, o el SII no tiene un código para él, se entrega
     * `null`.
     *
     * @param string $codigoISO Código ISO 3166-1 alpha-2 (por ejemplo, `DE`).
     * @return AduanaPais|null
     */
    public function findOneByCodigoISO(string $codigoISO): ?AduanaPais
    {
        $codigoISO = mb_strtoupper(trim($codigoISO), 'UTF-8');

        if (preg_match('/^[A-Z]{2}$/', $codigoISO) !== 1) {
            return null;
        }

        $pais = $this->findOneBy(['codigo_iso' => $codigoISO]);

        return $pais instanceof AduanaPais ? $pais : null;
    }
}

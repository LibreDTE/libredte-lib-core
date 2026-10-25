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
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;

/**
 * Repositorio para trabajar con los tipos de documentos.
 */
class TipoDocumentoRepository extends Repository
{
    /**
     * Entrega el listado de todos los documentos registrados en el repositorio
     * de datos.
     *
     * @return array
     */
    public function getDocumentos(): array
    {
        return (array) $this->all();
    }

    /**
     * Entrega el listado de documentos tributarios.
     *
     * Este listado incluirá:
     *
     *   - Documentos tributarios no electrónicos.
     *   - Documentos tributarios electrónicos.
     *   - Documentos tributarios adicionales (otros documentos, ej: rango 9XX).
     *
     * @return array
     */
    public function getDocumentosTributarios(): array
    {
        return $this->findBy([
            'categoria' => CategoriaDocumento::TRIBUTARIO,
        ]);
    }

    /**
     * Entrega el listado de documentos informativos.
     *
     * Este listado incluirá:
     *
     *   - Documentos informativos oficiales del SII (categoría "I").
     *   - Documentos informativos no oficiales del SII (categoría "R").
     *
     * @return array
     */
    public function getDocumentosInformativos(): array
    {
        return $this->findBy([
            'categoria' => [
                CategoriaDocumento::INFORMATIVO,
                CategoriaDocumento::REFERENCIA,
            ],
        ]);
    }

    /**
     * Entrega el listado de documentos tributarios electrónicos del SII.
     *
     * @return array
     */
    public function getDocumentosTributariosElectronicos(): array
    {
        return $this->findBy([
            'categoria' => CategoriaDocumento::TRIBUTARIO,
            'electronico' => true,
        ]);
    }

    /**
     * Entrega el listado de documentos tributarios electrónicos del SII.
     *
     * @return array
     */
    public function getDocumentosTributariosElectronicosCedibles(): array
    {
        return $this->findBy([
            'categoria' => CategoriaDocumento::TRIBUTARIO,
            'electronico' => true,
            'cedible' => true,
        ]);
    }

    /**
     * Entrega el listado de documentos tributarios electrónicos que pueden ser
     * emitidos utilizando LibreDTE.
     *
     * @return array
     */
    public function getDocumentosDisponibles(): array
    {
        return $this->findBy([
            'categoria' => CategoriaDocumento::TRIBUTARIO,
            'electronico' => true,
            'disponible' => true,
        ]);
    }

    /**
     * Entrega el listado de documentos válidos como referencia en un DTE.
     *
     * Incluye todos los documentos excepto los tributarios no electrónicos.
     *
     * @return TipoDocumentoInterface[]
     */
    public function getReferenciables(): array
    {
        return array_values(array_filter(
            $this->findBy([]),
            fn (TipoDocumentoInterface $t) => !(
                $t->getCategoria() === CategoriaDocumento::TRIBUTARIO
                && $t->isElectronico() !== true
            )
        ));
    }

    /**
     * Busca un documento a partir de su alias.
     *
     * @param string $alias
     * @return TipoDocumentoInterface|null
     */
    public function findByAlias(string $alias): ?TipoDocumentoInterface
    {
        $result = $this->findBy([
            'alias' => $alias,
        ]);

        if (!isset($result[0])) {
            return null;
        }

        return $result[0];
    }

    /**
     * Busca un documento a partir de su interfaz.
     *
     * @param string $interface
     * @return TipoDocumentoInterface|null
     */
    public function findByInterface(string $interface): ?TipoDocumentoInterface
    {
        $result = $this->findBy([
            'interface' => $interface,
        ]);

        if (!isset($result[0])) {
            return null;
        }

        return $result[0];
    }
}

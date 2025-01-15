<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Repository;

use Derafu\Lib\Core\Package\Prime\Component\Entity\Repository\Repository;
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

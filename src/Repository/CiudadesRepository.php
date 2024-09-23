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

namespace libredte\lib\Core\Repository;

use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;

/**
 * Repositorio para acceder a los datos de ciudades.
 *
 * Esta clase proporciona métodos para obtener la ciudad asociada a una comuna
 * en el sistema LibreDTE.
 */
class CiudadesRepository
{
    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    private DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase.
     *
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Obtiene la ciudad asociada a una comuna.
     *
     * @param string $comuna Nombre de la comuna.
     * @return string|false Nombre de la ciudad asociada o `false` si no se
     * encuentra.
     */
    public function getCiudad(string $comuna): string|false
    {
        return $this->dataProvider->getValue(
            'ciudades',
            mb_strtoupper($comuna, 'UTF-8'),
            false
        );
    }
}

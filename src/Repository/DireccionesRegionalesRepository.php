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
 * Repositorio para trabajar con las direcciones regionales del SII.
 */
class DireccionesRegionalesRepository
{
    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    private DataProviderInterface $dataProvider;

    /**
     * Constructor del repositorio.
     *
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Obtiene la dirección regional del SII basada en el nombre o código de la
     * comuna.
     *
     * Si el argumento es una cadena no numérica, intenta encontrar una dirección
     * regional correspondiente en el arreglo de direcciones. Si no se encuentra,
     * devuelve la cadena original en mayúsculas. Si el argumento es numérico,
     * asume que es un código de sucursal y devuelve un formato de sucursal. Si
     * el argumento es falso o vacío, devuelve 'N.N.' como valor por defecto.
     *
     * @param mixed $comuna El nombre de la comuna o el código de la sucursal.
     * @return string La dirección regional correspondiente, un formato de
     * sucursal para códigos numéricos, la misma entrada en mayúsculas si no se
     * encuentra en el arreglo, o 'N.N.' si la entrada es falsa o vacía.
     */
    public function getDireccionRegional($comuna): string
    {
        if (!$comuna) {
            return 'N.N.';
        }

        if (!is_numeric($comuna)) {
            $comuna = mb_strtoupper($comuna, 'UTF-8');
            $direccionRegional = $this->dataProvider->getValue(
                'direcciones_regionales',
                $comuna
            );

            return $direccionRegional ?? $comuna;
        }

        return 'SUC ' . $comuna;
    }
}

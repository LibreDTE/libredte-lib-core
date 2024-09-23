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
 * Clase AbstractRepository
 *
 * Clase base para todos los repositorios que manejan datos de configuración
 * basados en un identificador y su valor correspondiente. Los datos son
 * proporcionados por una implementación de la interfaz DataProviderInterface.
 */
abstract class AbstractRepository
{
    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Clave de los datos en el proveedor.
     *
     * @var string
     */
    protected string $dataKey;

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
     * Obtiene el valor asociado a un código en el conjunto de datos.
     *
     * @param string|int $codigo Código para buscar el valor.
     * @return string|int Valor asociado al código o el propio código si no se
     * encuentra.
     */
    public function getValor(string|int $codigo): string|int
    {
        return $this->dataProvider->getValue($this->dataKey, $codigo);
    }

    /**
     * Obtiene el código asociado a un valor en el conjunto de datos.
     *
     * @param string|int $valor Valor para buscar el código correspondiente.
     * @return string|int Código asociado al valor o el propio valor si no se
     * encuentra.
     */
    public function getCodigo(string|int $valor): string|int
    {
        $data = $this->dataProvider->getData($this->dataKey);
        return array_search($valor, $data, true) ?: $valor;
    }
}

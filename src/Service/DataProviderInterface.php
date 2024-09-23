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

namespace libredte\lib\Core\Service;

use UnexpectedValueException;

/**
 * Interfaz que define los métodos necesarios para un proveedor de datos (Data
 * Provider).
 *
 * Un Data Provider es responsable de suministrar conjuntos de datos asociados
 * a claves específicas.
 */
interface DataProviderInterface
{
    /**
     * Verifica si hay un conjunto de datos disponible para una clave
     * específica.
     *
     * @param string $key Clave que identifica el conjunto de datos.
     * @return bool `true` si existe un conjunto de datos asociado a la clave,
     * `false` en caso contrario.
     */
    public function hasData(string $key): bool;

    /**
     * Obtiene un conjunto de datos completo asociado con una clave específica.
     *
     * @param string $key Clave que identifica el conjunto de datos.
     * @return array Conjunto de datos asociado a la clave.
     * @throws UnexpectedValueException Si la clave no existe en el proveedor
     * de datos.
     */
    public function getData(string $key): array;

    /**
     * Agrega o registra un nuevo conjunto de datos bajo una clave específica.
     *
     * @param string $key Clave que identifica el conjunto de datos.
     * @param array $data Conjunto de datos a asociar a la clave.
     * @return void
     */
    public function addData(string $key, array $data): void;

    /**
     * Elimina un conjunto de datos del proveedor, identificado por una clave
     * específica.
     *
     * @param string $key Clave que identifica el conjunto de datos que se
     * desea eliminar.
     * @return void
     */
    public function removeData(string $key): void;

    /**
     * Devuelve una lista de todas las claves disponibles dentro del proveedor
     * de datos.
     *
     * @return array Arreglo de strings que contiene todas las claves
     * disponibles.
     */
    public function getAllKeys(): array;

    /**
     * Obtiene un valor específico dentro de un conjunto de datos, dada una
     * clave y un código.
     *
     * @param string $key Clave que identifica el conjunto de datos.
     * @param string|int $code Código que se desea buscar dentro del conjunto
     * de datos.
     * @param mixed $default Valor por defecto en caso que no se encuentr el
     * valor solicitado.
     * @return mixed Valor asociado al código dentro del conjunto de datos.
     * Si el código no se encuentra, se devuelve el valor por defecto.
     * @throws UnexpectedValueException Si la clave no existe en el proveedor
     * de datos.
     */
    public function getValue(
        string $key,
        string|int $code,
        mixed $default = null
    ): mixed;

    /**
     * Obtiene un conjunto de datos que pueden ser filtrados dentro de los
     * datos.
     *
     * Este método requiere que los valores en los datos sean arreglos para
     * poder realizar el filtrado.
     *
     * Si se desea filtrar por el ID o código de los datos se puede utilizar
     * cualquiera de estos índices (filtros): `id`, `code` o `codigo`.
     *
     * Si se desea filtrar buscando coincidencia de más de un valor para el
     * mismo filtro (condición "OR") se debe pasar el valor del filtro como un
     * arreglo.
     *
     * La búsqueda se hace utilizando comparación estricta, por lo que se si el
     * tipo de datos del valor del atributo buscado no coincide no se
     * considerará como encontrado.
     *
     * @return array Arreglo con los valores (arreglos) de los datos que
     * coinciden con la búsqueda solicitada.
     */
    public function search(string $key, array $filters): array;
}

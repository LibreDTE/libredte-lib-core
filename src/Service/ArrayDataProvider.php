<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

use LogicException;
use libredte\lib\Core\Service\PathManager;

/**
 * Clase que implementa la interfaz DataProviderInterface.
 *
 * Esta clase actúa como un proveedor de datos en memoria, permitiendo el
 * almacenamiento, recuperación y manejo de conjuntos de datos asociados a
 * claves específicas. Si los datos no están en memoria, los cargará desde
 * archivos.
 */
final class ArrayDataProvider implements DataProviderInterface
{
    /**
     * Almacén de datos en memoria.
     *
     * @var array
     */
    private array $dataStore = [];

    /**
     * Ruta base (con placeholder) para encontrar los archivos de datos.
     *
     * @var string
     */
    private ?string $dataFilepath;

    /**
     * Constructor del proveedor de datos.
     *
     * @param string|null $dataFilepath Ruta base para archivos de datos.
     */
    public function __construct(string $dataFilepath = null)
    {
        $this->dataFilepath = $dataFilepath;
    }

    /**
     * {@inheritdoc}
     */
    public function hasData(string $key): bool
    {
        if (!array_key_exists($key, $this->dataStore)) {
            try {
                $this->loadData($key);
            } catch (LogicException $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(string $key): array
    {
        if (!$this->hasData($key)) {
            throw new LogicException(sprintf(
                'No se encontraron datos para la clave %s.',
                $key
            ));
        }

        return $this->dataStore[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function addData(string $key, array $data): void
    {
        $this->dataStore[$key] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function removeData(string $key): void
    {
        if (array_key_exists($key, $this->dataStore)) {
            unset($this->dataStore[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllKeys(): array
    {
        return array_keys($this->dataStore);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(string $key, string|int $code, mixed $default = null): mixed
    {
        $data = $this->getData($key);

        return $data[$code] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $key, array $filters): array
    {
        // Obtener datos.
        $data = $this->getData($key);

        // Estandarizar valores de filtros como arreglos.
        // Se aplicará el filtro a los valores como un OR.
        foreach ($filters as $filter => &$values) {
            if (!is_array($values)) {
                $values = [$values];
            }
        }

        // Filtrar los datos del repositorio.
        return array_filter($data, function ($row, $code) use ($filters) {
            // Recorrer los filtros e ir descartando lo que no coincida.
            foreach ($filters as $filter => $values) {
                // El filtro solicitado es por ID o código.
                if (in_array($filter, ['id', 'code', 'codigo'], true)) {
                    if (!in_array($code, $values, true)) {
                        return false;
                    }
                }

                // Se quiere filtrar por algún atributo de los valores de los
                // datos del repositorio.
                else {
                    $value = $row[$filter] ?? null;
                    if (!in_array($value, $values, true)) {
                        return false;
                    }
                }
            }

            // Pasó todos los filtros, por lo que se debe incluir el registro
            // en los datos que se están buscando.
            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Carga los datos asociados a una clave desde un archivo.
     *
     * @param string $key Clave que identifica el conjunto de datos.
     * @return void
     * @throws LogicException Si el archivo de datos no existe o no se puede
     * cargar.
     */
    private function loadData(string $key): void
    {
        if (isset($this->dataFilepath)) {
            $filepath = sprintf($this->dataFilepath, $key);
            $filepath = is_readable($filepath) ? $filepath : null;
        } else {
            $filepath = PathManager::getDataPath($key);
        }

        if ($filepath === null) {
            throw new LogicException(sprintf(
                'El archivo de datos para la clave %s no existe.',
                $key
            ));
        }

        $data = include $filepath;

        if (!is_array($data)) {
            throw new LogicException(sprintf(
                'El archivo de datos para la clave %s no contiene un array válido.',
                $key
            ));
        }

        $this->dataStore[$key] = $data;
    }
}

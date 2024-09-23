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

namespace libredte\lib\Core\Helper;

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Writer;
use LogicException;
use RuntimeException;

/**
 * Clase para manejar archivos CSV utilizando League\Csv.
 */
class Csv
{
    /**
     * Carga un CSV desde un string.
     *
     * @param string $csvString El contenido del CSV como string.
     * @param string $separator Separador a utilizar para diferenciar entre una
     * columna u otra.
     * @param string $textDelimiter Delimitador del texto para "rodear" cada
     * campo del CSV.
     * @return array El arreglo de datos del CSV.
     * @throws Exception Si ocurre un error durante la lectura del CSV.
     */
    public static function load(
        string $csvString,
        string $separator = ';',
        string $textDelimiter = '"'
    ): array {
        try {
            $csv = Reader::createFromString($csvString);
            $csv->setDelimiter($separator);
            $csv->setEnclosure($textDelimiter);
            return iterator_to_array($csv->getRecords());
        } catch (\Exception $e) {
            throw new RuntimeException('Error al leer el CSV desde el string: ' . $e->getMessage());
        }
    }

    /**
     * Lee un archivo CSV.
     *
     * @param string $file Archivo a leer.
     * @param string $separator Separador a utilizar para diferenciar entre una columna u otra.
     * @param string $textDelimiter Delimitador del texto para "rodear" cada campo del CSV.
     * @return array El arreglo de datos del archivo CSV.
     * @throws Exception Si ocurre un error durante la lectura del CSV.
     */
    public static function read(
        string $file,
        string $separator = ';',
        string $textDelimiter = '"'
    ): array {
        $csv = Reader::createFromPath($file, 'r');
        $csv->setDelimiter($separator);
        $csv->setEnclosure($textDelimiter);
        return iterator_to_array($csv->getRecords());
    }

    /**
     * Genera un CSV a partir de un arreglo y lo entrega como string.
     *
     * @param array $data Arreglo utilizado para generar el CSV.
     * @param string $separator Separador a utilizar.
     * @param string $textDelimiter Delimitador del texto.
     * @return string CSV generado como string.
     * @throws CannotInsertRecord Si ocurre un error durante la generación del CSV.
     */
    public static function generate(
        array $data,
        string $separator = ';',
        string $textDelimiter = '"'
    ): string {
        $csv = Writer::createFromString('');
        $csv->setDelimiter($separator);
        $csv->setEnclosure($textDelimiter);
        $csv->insertAll($data);
        return $csv->toString();
    }

    /**
     * Escribe un CSV a un archivo desde un arreglo.
     *
     * @param array $data Arreglo utilizado para generar la planilla.
     * @param string $file Nombre del archivo que se debe generar.
     * @param string $separator Separador a utilizar.
     * @param string $textDelimiter Delimitador del texto.
     * @return void
     */
    public static function write(
        array $data,
        string $file,
        string $separator = ';',
        string $textDelimiter = '"'
    ): void {
        $csvString = self::generate($data, $separator, $textDelimiter);

        if (@file_put_contents($file, $csvString) === false) {
            throw new LogicException(sprintf(
                'No se pudo escribir el archivo CSV en la ruta: %s',
                $file
            ));
        }
    }

    /**
     * Envía un CSV al navegador web.
     *
     * @param array $data Arreglo utilizado para generar la planilla.
     * @param string $file Nombre del archivo.
     * @param string $separator Separador a utilizar.
     * @param string $textDelimiter Delimitador del texto.
     * @return void
     */
    public static function send(
        array $data,
        string $file,
        string $separator = ';',
        string $textDelimiter = '"',
        bool $sendHttpHeaders = true,
    ): void {
        $csvString = self::generate($data, $separator, $textDelimiter);

        if ($sendHttpHeaders) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $file . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        echo $csvString;
    }
}

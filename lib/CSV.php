<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sasco\LibreDTE;

/**
 * Manejar archivos CSV
 *
 * Esta clase está basada en la clase: \sowerphp\general\Utility_Spreadsheet_CSV
 * disponible en:
 * <https://github.com/SowerPHP/extension-general/blob/master/Utility/Spreadsheet/CSV.php>
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-17
 */
class CSV
{

    /**
     * Lee un archivo CSV
     * @param archivo archivo a leer (ejemplo índice tmp_name de un arreglo $_FILES)
     * @param separador separador a utilizar para diferenciar entre una columna u otra
     * @param delimitadortexto Delimitador del texto para "rodear" cada campo del CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public static function read($archivo, $separador = ';', $delimitadortexto = '"')
    {
        if (($handle = fopen($archivo, 'r')) !== FALSE) {
            $data = array();
            $i = 0;
            while (($row = fgetcsv($handle, 0, $separador, $delimitadortexto)) !== FALSE) {
                $j = 0;
                foreach ($row as &$col) {
                    $data[$i][$j++] = $col;
                }
                ++$i;
            }
            fclose($handle);
        }
        return $data;
    }

    /**
     * Crea un archivo CSV a partir de un arreglo
     * @param data Arreglo utilizado para generar la planilla
     * @param archivo Nombre del archivo sin extensión (sin .csv)
     * @param separador separador a utilizar para diferenciar entre una columna u otra
     * @param delimitadortexto Delimitador del texto para "rodear" cada campo del CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public static function generate(array $data, $archivo, $separador = ';', $delimitadortexto = '"')
    {
        ob_clean();
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename='.$archivo.'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        foreach($data as &$row) {
            foreach($row as &$col) {
                $col = $delimitadortexto.rtrim(str_replace('<br />', ', ', strip_tags($col, '<br>')), " \t\n\r\0\x0B,").$delimitadortexto;
            }
            echo implode($separador, $row),"\r\n";
            unset($row);
        }
        unset($data);
        exit(0);
    }

    /**
     * Crea un archivo CSV a partir de un arreglo guardándolo en el sistema de archivos
     * @param data Arreglo utilizado para generar la planilla
     * @param archivo Nombre del archivo que se debe generar
     * @param separador separador a utilizar para diferenciar entre una columna u otra
     * @param delimitadortexto Delimitador del texto para "rodear" cada campo del CSV
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public static function save(array $data, $archivo, $separador = ';', $delimitadortexto = '"')
    {
        $fd = fopen($archivo, 'w');
        foreach($data as &$row) {
            foreach($row as &$col) {
                $col = $delimitadortexto.rtrim(str_replace('<br />', ', ', strip_tags($col, '<br>')), " \t\n\r\0\x0B,").$delimitadortexto;
            }
            fwrite($fd, implode($separador, $row)."\r\n");
            unset($row);
        }
        fclose($fd);
    }

}

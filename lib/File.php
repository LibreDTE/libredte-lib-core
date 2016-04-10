<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sasco\LibreDTE;

/**
 * Clase para manejar archivos y directorios
 *
 * Esta clase permite realizar diversas acciones sobre archivos y directorios
 * que se encuentren en el servidor donde se ejecuta la aplicación.
 *
 * Los métodos se copiaron desde la clase \sowerphp\general\View_Helper_PDF
 * disponible en:
 *
 * <https://github.com/SowerPHP/extension-general/blob/master/Utility/File.php>
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-09
 */
class File
{

    /**
     * Borra recursivamente un directorio
     * @param dir Directorio a borrar
     * @author http://en.kioskea.net/faq/793-warning-rmdir-directory-not-empty
     * @version 2015-04-21
     */
    public static function rmdir($dir)
    {
        // List the contents of the directory table
        $dir_content = scandir ($dir);
        // Is it a directory?
        if ($dir_content!==false) {
            // For each directory entry
            foreach ($dir_content as &$entry) {
                // Unix symbolic shortcuts, we go
                if (!in_array ($entry, array ('.','..'))) {
                    // We find the path from the beginning
                    $entry = $dir.DIRECTORY_SEPARATOR. $entry;
                    // This entry is not an issue: it clears
                    if (!is_dir($entry)) {
                        unlink ($entry);
                    } else { // This entry is a folder, it again on this issue
                        self::rmdir($entry);
                    }
                }
            }
        }
        // It has erased all entries in the folder, we can now erase
        rmdir ($dir);
    }

    /**
     * Método que entrega el mimetype de un archivo
     * @param file Ruta hacia el fichero
     * @return Mimetype del fichero o =false si no se pudo determinar
     * @author http://stackoverflow.com/a/23287361
     * @version 2015-11-03
     */
    public static function mimetype($file)
    {
        if (!function_exists('finfo_open'))
            return false;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mimetype;
    }

    /**
     * Método que empaqueta y comprime archivos (uno o varios, o directorios).
     * Si se pide usar formato zip entonces se usará ZipArchive de PHP para
     * comprimir
     * @param filepath Directorio (o archivo) que se desea comprimir
     * @param options Arreglo con opciones para comprmir (format, download, delete)
     * @todo Preparar datos si se pasa un arreglo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]delaf.cl)
     * @version 2015-11-03
     */
    public static function compress($file, $options = [])
    {
        // definir opciones por defecto
        $options = array_merge([
            'format' => 'gz',
            'delete' => false,
            'download' => true,
            'commands' => [
                'gz' => 'gzip --keep :in',
                'tar.gz' => 'tar czf :in.tar.gz :in',
                'tar' => 'tar cf :in.tar :in',
                'bz2' => 'bzip2 --keep :in',
                'tar.bz2' => 'tar cjf :in.tar.bz2 :in',
                'zip' => 'zip -r :in.zip :in',
            ],
        ], $options);
        // si el archivo no se puede leer se entrega =false
        if (!is_readable($file)) {
            \sasco\LibreDTE\Log::write(Estado::COMPRESS_ERROR_READ, Estado::get(Estado::COMPRESS_ERROR_READ));
            return false;
        }
        // si es formato gz y es directorio se cambia a tgz
        if (is_dir($file)) {
            if ($options['format']=='gz') $options['format'] = 'tar.gz';
            else if ($options['format']=='bz2') $options['format'] = 'tar.bz2';
        }
        // obtener directorio que contiene al archivo/directorio y el nombre de este
        $filepath = $file;
        $dir = dirname($file);
        $file = basename($file);
        $file_compressed = $file.'.'.$options['format'];
        // empaquetar/comprimir directorio/archivo
        if ($options['format']=='zip') {
            // crear archivo zip
            $zip = new \ZipArchive();
            if ($zip->open($dir.DIRECTORY_SEPARATOR.$file.'.zip', \ZipArchive::CREATE)!==true) {
                \sasco\LibreDTE\Log::write(Estado::COMPRESS_ERROR_ZIP, Estado::get(Estado::COMPRESS_ERROR_ZIP));
                return false;
            }
            // agregar un único archivo al zip
            if (!is_dir($filepath)) {
                $zip->addFile($filepath, $file);
            }
            // agregar directorio al zip
            else if (is_dir($filepath)) {
                $Iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filepath));
                foreach ($Iterator as $f) {
                    if (!$f->isDir()) {
                        $path = $f->getPath().DIRECTORY_SEPARATOR.$f->getFilename();
                        $zip->addFile($path, str_replace($filepath, '', $file.DIRECTORY_SEPARATOR.$path));
                    }
                }
            }
            // escribir en el sistema de archivos y cerrar archivo
            file_put_contents($dir.DIRECTORY_SEPARATOR.$file_compressed, $zip->getStream(md5($filepath)));
            $zip->close();
        } else {
            exec('cd '.$dir.' && '.str_replace(':in', $file, $options['commands'][$options['format']]));
        }
        // enviar archivo
        if ($options['download']) {
            ob_clean();
            header ('Content-Disposition: attachment; filename='.$file_compressed);
            $mimetype = self::mimetype($dir.DIRECTORY_SEPARATOR.$file_compressed);
            if ($mimetype)
                header ('Content-Type: '.$mimetype);
            header ('Content-Length: '.filesize($dir.DIRECTORY_SEPARATOR.$file_compressed));
            readfile($dir.DIRECTORY_SEPARATOR.$file_compressed);
            unlink($dir.DIRECTORY_SEPARATOR.$file_compressed);
        }
        // borrar directorio o archivo que se está comprimiendo si así se ha
        // solicitado
        if ($options['delete']) {
            if (is_dir($filepath)) self::rmdir($filepath);
            else unlink($filepath);
        }
    }

}

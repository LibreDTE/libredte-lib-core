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

use RuntimeException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use ZipStream\OperationMode;
use ZipStream\ZipStream;

/**
 * Clase para trabajar con archivos.
 */
class File
{
    /**
     * Borra recursivamente un directorio.
     *
     * @param string $dir Directorio a borrar.
     * @return void
     */
    public static function rmdir(string $dir): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($dir);
    }

    /**
     * Entrega el mimetype de un archivo.
     *
     * @param string $file Ruta hacia el fichero.
     * @return string|false Mimetype del fichero o false si no se pudo determinar.
     */
    public static function mimetype(string $file): string|false
    {
        if (!file_exists($file)) {
            return false;
        }

        $mimeTypes = new MimeTypes();
        return $mimeTypes->guessMimeType($file) ?: false;
    }

    /**
     * Empaqueta y comprime archivos o directorios en un archivo ZIP.
     *
     * @param string $file Directorio o archivo que se desea comprimir.
     * @param bool $download Indica si se debe enviar el archivo comprimido a
     * través del navegador. Si se solicita descargar, el archivo descargado se
     * eliminará luego de ser enviado por el navegador siempre. Para controlar
     * esto usar compress() y luego send() (por separado).
     * @param bool $delete Indica si se debe eliminar el archivo o directorio
     * original que se comprimió luego de ser procesado.
     * @return void
     */
    public static function compress(string $file, bool $download = false, bool $delete = false): void
    {
        if (!is_readable($file)) {
            throw new RuntimeException(sprintf(
                'No se puede leer el archivo %s que se desea comprimir.',
                $file
            ));
        }

        $zipFilePath = $file . '.zip';

        self::zip($file, $zipFilePath);

        if ($download) {
            self::send($zipFilePath, true);
        }

        if ($delete) {
            self::rmdir($file);
        }
    }

    /**
     * Comprime un archivo o directorio usando ZipStream.
     *
     * Este método utiliza la biblioteca `ZipStream` para comprimir el archivo
     * o directorio especificado en un archivo ZIP. En caso de error durante la
     * compresión, las excepciones lanzadas por `ZipStream` se propagarán sin
     * ser capturadas.
     *
     * @param string $file Ruta del archivo o directorio a comprimir.
     * @param string $zipFilePath Nombre del archivo comprimido resultante.
     * @return void
     *
     * @throws RuntimeException Si no se puede abrir el archivo para escritura.
     */
    public static function zip(string $file, string $zipFilePath): void
    {
        // Abre un flujo para el archivo de salida.
        $outputStream = fopen($zipFilePath, 'w');
        if (!$outputStream) {
            throw new RuntimeException(sprintf(
                'No se puede abrir el archivo para escritura: $%s',
                $zipFilePath
            ));
        }

        try {
            // Crear instancia de ZipStream con modo de operación NORMAL.
            $zip = new ZipStream(
                operationMode: OperationMode::NORMAL,
                outputStream: $outputStream,
                sendHttpHeaders: false,
            );

            // Si es un directorio, agregar todos los archivos de forma recursiva.
            if (is_dir($file)) {
                $finder = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($file)
                );
                foreach ($finder as $foundFile) {
                    if (!$foundFile->isDir()) {
                        $localName = str_replace($file . DIRECTORY_SEPARATOR, '', $foundFile->getPathname());
                        $zip->addFileFromPath($localName, $foundFile->getPathname());
                    }
                }
            } else {
                // Si es un archivo, agregarlo directamente.
                $zip->addFileFromPath(basename($file), $file);
            }

            // Finaliza la creación del archivo zip.
            $zip->finish();
        } finally {
            // Cierra el flujo de salida.
            fclose($outputStream);
        }
    }

    /**
     * Envía un archivo para su descarga a través del navegador.
     *
     * @param string $file
     * @return void
     */
    private static function send(string $file, bool $delete = false): void
    {
        if (!file_exists($file)) {
            return;
        }

        $mimetype = self::mimetype($file);
        if ($mimetype) {
            header('Content-Type: ' . $mimetype);
        }

        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($file);

        if ($delete) {
            unlink($file);
        }
    }
}

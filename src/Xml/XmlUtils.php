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

namespace libredte\lib\Core\Xml;

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;

/**
 * Utilidades (métodos auxiliares) para trabajar con XML.
 */
class XmlUtils
{
    /**
     * Ejecuta una consulta XPath en un documento XML.
     *
     * @param DOMDocument $document Documento XML donde se ejecutará la consulta.
     * @param string $expression Expresión XPath a ejecutar.
     * @return DOMNodeList Nodos resultantes de la consulta XPath.
     */
    public static function xpath(DOMDocument $document, string $expression): DOMNodeList
    {
        $xpath = new DOMXPath($document);
        $result = @$xpath->query($expression);

        if ($result === false) {
            throw new InvalidArgumentException(sprintf(
                'Expresión XPath inválida: %s',
                $expression
            ));
        }

        return $result;
    }

    /**
     * Codifica el string como ISO-8859-1 si es que fue pasado como UTF-8.
     *
     * @param string $string String en UTF-8 o ISO-8859-1.
     * @return string String en ISO-8859-1 si se logró convertir.
     */
    public static function utf2iso(string $string): string
    {
        if (!mb_detect_encoding($string, 'UTF-8', true)) {
            return $string;
        }

        return (string) mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
    }

    /**
     * Codifica el string como UTF-8 si es que fue pasado como ISO-8859-1.
     *
     * @param string $string String en UTF-8 o ISO-8859-1.
     * @return string String en UTF-8 si se logró convertir.
     */
    public static function iso2utf(string $string): string
    {
        if (!mb_detect_encoding($string, 'ISO-8859-1', true)) {
            return $string;
        }

        return (string) mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }

    /**
     * Sanitiza los valores que son asignados a los tags del XML.
     *
     * @param string $string Texto que se asignará como valor al nodo XML.
     * @return string Texto sanitizado.
     */
    public static function sanitize(string $string): string
    {
        // Si no se paso un texto o bien es un número no se hace nada.
        if (!$string || is_numeric($string)) {
            return $string;
        }

        // Convertir "predefined entities" de XML.
        $string = str_replace(
            ['&amp;', '&#38;', '&lt;', '&#60;', '&gt;', '&#62', '&quot;', '&#34;', '&apos;', '&#39;'],
            ['&', '&', '<', '<', '>', '>', '"', '"', '\'', '\''],
            $string
        );

        $string = str_replace('&', '&amp;', $string);

        /*$string = str_replace(
            ['"', '\''],
            ['&quot;', '&apos;'],
            $string
        );*/

        // Entregar texto sanitizado.
        return $string;
    }

    /**
     * Corrige las entities '&apos;' y '&quot;' en el XML.
     *
     * La corrección se realiza solo dentro del contenido de tags del XML, pero
     * no en los atributos de los tags.
     *
     * @param string $string XML a corregir.
     * @return string XML corregido.
     */
    public static function fixEntities(string $string): string
    {
        $newString = '';
        $n_letras = strlen($string);
        $convertir = false;
        for ($i = 0; $i < $n_letras; ++$i) {
            if ($string[$i] === '>') {
                $convertir = true;
            }
            if ($string[$i] === '<') {
                $convertir = false;
            }
            $newString .= $convertir
                ? str_replace(
                    ['\'', '"'],
                    ['&apos;', '&quot;'],
                    $string[$i]
                )
                : $string[$i]
            ;
        }
        return $newString;
    }
}

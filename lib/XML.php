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
 * Clase para trabajar con XMLs
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-08-20
 */
class XML extends \DomDocument
{

    /**
     * Constructor de la clase XML
     * @param version Versión del documento XML
     * @param encoding Codificación del documento XML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-05
     */
    public function __construct($version = '1.0', $encoding = 'ISO-8859-1')
    {
        parent::__construct($version, $encoding);
        $this->formatOutput = true;
    }

    /**
     * Método que genera nodos XML a partir de un arreglo
     * @param array Arreglo con los datos que se usarán para generar XML
     * @param parent DOMElement padre para los elementos, o =null para que sea la raíz
     * @return Objeto \sasco\LibreDTE\XML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function generate(array $array, \DOMElement &$parent = null)
    {
        if ($parent===null)
            $parent = &$this;
        foreach ($array as $key => $value) {
            if ($key=='@attributes') {
                foreach ($value as $attr => $val) {
                    if ($val!==false)
                        $parent->setAttribute($attr, $val);
                }
            } else if ($key=='@value') {
                $parent->nodeValue = $this->sanitize($value);
            } else {
                if (is_array($value)) {
                    $keys = array_keys($value);
                    if (!is_int($keys[0])) {
                        $value = [$value];
                    }
                    foreach ($value as $value2) {
                        $Node = new \DOMElement($key);
                        $parent->appendChild($Node);
                        $this->generate($value2, $Node);
                    }
                } else {
                    if (is_object($value) and $value instanceof \DOMElement) {
                        $Node = $this->importNode($value, true);
                    } else {
                        $Node = new \DOMElement($key, $this->sanitize($value));
                    }
                    $parent->appendChild($Node);
                }
            }
        }
        return $this;
    }

    /**
     * Método que sanitiza los valores que son asignados a los tags del XML
     * @param txt String que que se asignará como valor al nodo XML
     * @return String sanitizado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    private function sanitize($txt)
    {
        // si no se paso un texto o bien es un número no se hace nada
        if (!$txt or is_numeric($txt))
            return $txt;
        // convertir "predefined entities" de XML
        $txt = str_replace(
            ['&amp;', '&#38;', '&lt;', '&#60;', '&gt;', '&#62', '&quot;', '&#34;', '&apos;', '&#39;'],
            ['&', '&', '<', '<', '>', '>', '"', '"', '\'', '\''],
            $txt
        );
        $txt = str_replace(
            ['&', '"', '\''],
            ['&amp;', '&quot;', '&apos;'],
            $txt
        );
        // quitar acentos, eñes y otros caracteres especiales
        $txt = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ', 'ü', 'Ü'],
            ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N', 'u', 'U'],
            $txt
        );
        // entregar texto sanitizado
        return $txt;
    }

    /**
     * Método para realizar consultas XPATH al documento XML
     * @param expression Expresión XPath a ejecutar
     * @return DOMNodeList
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-05
     */
    public function xpath($expression)
    {
        return (new \DOMXPath($this))->query($expression);
    }

    /**
     * Método que entrega el código XML canonicalizado y con la codificación que
     * corresponde
     * @return String con código XML canonicalizado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-30
     */
    public function C14N($exclusive = null, $with_comments = null, array $xpath = null, array $ns_prefixes = null)
    {
        return $this->encode(parent::C14N($exclusive, $with_comments, $xpath, $ns_prefixes));
    }

    /**
     * Método que entrega el código XML aplanado y con la codificación que
     * corresponde
     * @return String con código XML aplanado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-08-30
     */
    public function getFlattened($xpath = null)
    {
        $xml = $xpath ? $this->encode($this->xpath($xpath)->item(0)->C14N()) : $this->C14N();
        $xml = preg_replace("/\>\n\s+\</", '><', $xml);
        $xml = preg_replace("/\>\n\t+\</", '><', $xml);
        $xml = preg_replace("/\>\n+\</", '><', $xml);
        return trim($xml);
    }

    /**
     * Método que codifica el string como ISO-8859-1 si es que fue pasado como
     * UTF-8
     * @param string String en UTF-8 o ISO-8859-1
     * @return String en ISO-8859-1
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-02
     */
    public function encode($string)
    {
        return mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1']) != 'ISO-8859-1' ? utf8_decode($string) : $string;
    }

}

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

// errores de XML se almacenarán internamente y no serán procesados por PHP
// se deberán recuperar con: libxml_get_errors()
libxml_use_internal_errors(true);

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
                    if (!empty($value)) {
                        $keys = array_keys($value);
                        if (!is_int($keys[0])) {
                            $value = [$value];
                        }
                        foreach ($value as $value2) {
                            $Node = new \DOMElement($key);
                            $parent->appendChild($Node);
                            $this->generate($value2, $Node);
                        }
                    }
                } else {
                    if (is_object($value) and $value instanceof \DOMElement) {
                        $Node = $this->importNode($value, true);
                        $parent->appendChild($Node);
                    } else {
                        if ($value!==false) {
                            $Node = new \DOMElement($key, $this->sanitize($value));
                            $parent->appendChild($Node);
                        }
                    }
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
     * Método que entrega el código XML aplanado y con la codificación que
     * corresponde
     * @param xpath XPath para consulta al XML y extraer sólo una parte
     * @return String con código XML aplanado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-22
     */
    public function getFlattened($xpath = null)
    {
        if ($xpath) {
            $node = $this->xpath($xpath)->item(0);
            if (!$node)
                return false;
            $xml = $this->encode($node->C14N());
        } else {
            $xml = $this->C14N();
        }
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
    private function encode($string)
    {
        return mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1']) != 'ISO-8859-1' ? utf8_decode($string) : $string;
    }

    /**
     * Método que convierte el XML a un arreglo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-26
     */
    public function toArray(\DOMElement $dom = null, array &$array = null, $arregloNodos = false)
    {
        // determinar valores de parámetros
        if (!$dom)
            $dom = $this->documentElement;
        if (!$dom)
            return false;
        if ($array===null)
            $array = [$dom->tagName => null];
        // agregar atributos del nodo
        if ($dom->hasAttributes()) {
            $array[$dom->tagName]['@attributes'] = [];
            foreach ($dom->attributes as $attribute) {
                $array[$dom->tagName]['@attributes'][$attribute->name] = $attribute->value;
            }
        }
        // agregar nodos hijos
        if ($dom->hasChildNodes()) {
            foreach($dom->childNodes as $child) {
                if ($child instanceof \DOMText) {
                    $textContent = trim($child->textContent);
                    if ($textContent!="") {
                        if ($dom->childNodes->length==1) {
                            $array[$dom->tagName] = $textContent;
                        } else
                            $array[$dom->tagName]['@value'] = $textContent;
                    }
                }
                else if ($child instanceof \DOMElement) {
                    $nodos_gemelos = $this->countTwins($dom, $child->tagName);
                    if ($nodos_gemelos==1) {
                        if ($arregloNodos)
                            $this->toArray($child, $array);
                        else
                            $this->toArray($child, $array[$dom->tagName]);
                    }
                    // crear arreglo con nodos hijos que tienen el mismo nombre de tag
                    else {
                        if (!isset($array[$dom->tagName][$child->tagName]))
                            $array[$dom->tagName][$child->tagName] = [];
                        $siguiente = count($array[$dom->tagName][$child->tagName]);
                        $array[$dom->tagName][$child->tagName][$siguiente] = [];
                        $this->toArray($child, $array[$dom->tagName][$child->tagName][$siguiente], true);
                    }
                }
            }
        }
        // entregar arreglo
        return $array;
    }

    /**
     * Método que cuenta los nodos con el mismo nombre hijos deun DOMElement
     * No sirve usar: $dom->getElementsByTagName($tagName)->length ya que esto
     * entrega todos los nodos con el nombre, sean hijos, nietos, etc.
     * @return Cantidad de nodos hijos con el mismo nombre en el DOMElement
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    private function countTwins(\DOMElement $dom, $tagName)
    {
        $twins = 0;
        foreach ($dom->childNodes as $child) {
            if ($child instanceof \DOMElement and $child->tagName==$tagName)
                $twins++;
        }
        return $twins;
    }

    /**
     * Método que entrega los errores de libxml que pueden existir
     * @return Arreglo con los errores XML que han ocurrido
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-18
     */
    public function getErrors()
    {
        $errors = [];
        foreach (libxml_get_errors() as $e)
            $errors[] = $e->message;
        return $errors;
    }

    /**
     * Método que entrega el nombre del tag raíz del XML
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    public function getName()
    {
        return $this->documentElement->tagName;
    }

    /**
     * Método que entrega el nombre del archivo del schema del XML
     * @return Nombre del schema o bien =false si no se encontró
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    public function getSchema()
    {
        $schemaLocation = $this->documentElement->getAttribute('xsi:schemaLocation');
        if (!$schemaLocation or strpos($schemaLocation, ' ')===false)
            return false;
        list($uri, $xsd) = explode(' ', $schemaLocation);
        return $xsd;
    }

}

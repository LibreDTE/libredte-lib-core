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

use DOMElement;
use DOMNodeList;
use DOMText;

/**
 * Clase `XmlDecoder` crea un arreglo PHP a partir de un documento XML.
 */
class XmlDecoder
{
    /**
     * Convierte un documento XML a un arreglo PHP.
     *
     * @param XmlDocument|DOMElement $documentElement Documento XML que se
     * desea convertir a un arreglo de PHP o el elemento donde vamos a hacer la
     * conversión si no es el documento XML completo.
     * @param array|null $data Arreglo donde se almacenarán los resultados.
     * @param bool $twinsAsArray Indica si se deben tratar los nodos gemelos
     * como un arreglo.
     * @return array Arreglo con la representación del XML.
     */
    public static function decode(
        XmlDocument|DOMElement $documentElement,
        ?array &$data = null,
        bool $twinsAsArray = false
    ): array {
        // Si no viene un tagElement se busca uno, si no se obtiene se termina
        // la generación.
        $tagElement = $documentElement instanceof DOMElement
            ? $documentElement
            : $documentElement->documentElement
        ;
        if ($tagElement === null) {
            return [];
        }

        // Índice en el arreglo que representa al tag. Además es un nombre de
        // variable más corto :)
        $key = $tagElement->tagName;

        // Si no hay un arreglo de destino para los datos se crea un arreglo
        // con el índice del nodo principal con valor vacío.
        if ($data === null) {
            //$data = [$key => self::getEmptyValue()];
            $data = [$key => null];
        }

        // Si el tagElement tiene atributos se agregan al arreglo dentro del
        // índice especial '@attributes'.
        if ($tagElement->hasAttributes()) {
            $data[$key]['@attributes'] = [];
            foreach ($tagElement->attributes as $attribute) {
                $data[$key]['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        // Si el tagElement tiene nodos hijos se agregan al valor del tag.
        if ($tagElement->hasChildNodes()) {
            self::arrayAddChilds(
                $data,
                $tagElement,
                $tagElement->childNodes,
                $twinsAsArray
            );
        }

        // Entregar los datos del documento XML como un arreglo.
        return $data;
    }

    /**
     * Agrega nodos hijos de un documento XML a un arreglo PHP.
     *
     * @param array &$data Arreglo donde se agregarán los nodos hijos.
     * @param DOMElement $tagElement Nodo padre del que se extraerán los nodos
     * hijos.
     * @param DOMNodeList $childs Lista de nodos hijos del nodo padre.
     * @param bool $twinsAsArray Indica si se deben tratar los nodos gemelos
     * como un arreglo.
     * @return void
     */
    private static function arrayAddChilds(
        array &$data,
        DOMElement $tagElement,
        DOMNodeList $childs,
        bool $twinsAsArray,
    ): void {
        $key = $tagElement->tagName;
        // Se recorre cada uno de los nodos hijos.
        foreach ($childs as $child) {
            if ($child instanceof DOMText) {
                $textContent = trim($child->textContent);
                if ($textContent !== '') {
                    if ($tagElement->hasAttributes()) {
                        $data[$key]['@value'] = $textContent;
                    } elseif ($childs->length === 1 && empty($data[$key])) {
                        $data[$key] = $textContent;
                    } else {
                        $array[$key]['@value'] = $textContent;
                    }
                }
            } elseif ($child instanceof DOMElement) {
                $n_twinsNodes = self::nodeCountTwins(
                    $tagElement,
                    $child->tagName
                );
                if ($n_twinsNodes === 1) {
                    if ($twinsAsArray) {
                        self::decode($child, $data);
                    } else {
                        self::decode($child, $data[$key]);
                    }
                } else {
                    // Se crea una lista para el nodo hijo, pues tiene varios
                    // nodos iguales el XML.
                    if (!isset($data[$key][$child->tagName])) {
                        $data[$key][$child->tagName] = [];
                    }

                    // Se revisa si el nodo hijo es escalar. Si lo es, se añade
                    // a la lista directamente.
                    $textContent = trim($child->textContent);
                    if ($textContent !== '') {
                        $data[$key][$child->tagName][] = $textContent;
                    }
                    // Si el nodo hijo es un escalar, sino que es una lista de
                    // nodos, se construye como si fuese un arreglo normal con
                    // la llamada a decode().
                    else {
                        $siguiente = count($data[$key][$child->tagName]);
                        $data[$key][$child->tagName][$siguiente] = [];
                        self::decode(
                            $child,
                            $data[$key][$child->tagName][$siguiente],
                            true
                        );
                    }
                }
            }
        }
    }

    /**
     * Cuenta los nodos con el mismo nombre hijos de un DOMElement.
     *
     * @param DOMElement $dom Elemento DOM donde se buscarán los nodos.
     * @param string $tagName Nombre del tag a contar.
     * @return int Cantidad de nodos hijos con el mismo nombre.
     */
    private static function nodeCountTwins(DOMElement $dom, string $tagName): int
    {
        $twins = 0;
        foreach ($dom->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                $twins++;
            }
        }
        return $twins;
    }
}

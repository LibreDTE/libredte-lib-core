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
use DOMNode;
use InvalidArgumentException;

/**
 * Clase `XmlEncoder` que crea un documento XML a partir de un arreglo PHP.
 */
class XmlEncoder
{
    /**
     * Reglas para convertir de arreglo de PHP a XML y viceversa.
     *
     * @var array
     */
    private static array $rules = [
        // ¿Cómo se deben procesar los valores de los nodos?.
        'node_values' => [
            // Valores que hacen que el nodo no se genere (se omite).
            'skip_generation' => [null, false, []],
            // Valores que generan un nodo vacío.
            'generate_empty' => ['', true],
        ],
    ];

    /**
     * Convierte un arreglo PHP a un documento XML, generando los nodos y
     * respetando un espacio de nombres si se proporciona.
     *
     * @param array $data Arreglo con los datos que se usarán para generar XML.
     * @param array|null $namespace Espacio de nombres para el XML (URI y
     * prefijo).
     * @param DOMElement|null $parent Elemento padre para los nodos, o null
     * para que sea la raíz.
     * @param XmlDocument $doc El documento raíz del XML que se genera.
     * @return XmlDocument
     */
    public static function encode(
        array $data,
        ?array $namespace = null,
        ?DOMElement $parent = null,
        ?XmlDocument $doc = null
    ): XmlDocument {
        // Si no hay un documento XML completo (desde raíz, no vale un nodo),
        // entonces se crea, pues se necesitará para crear los futuros nodos.
        if ($doc === null) {
            $doc = new XmlDocument();
        }

        // Si no hay un elemento padre, entonces se está pidiendo crear el
        // documento XML desde 0 (desde el nodo raíz).
        if ($parent === null) {
            $parent = $doc;
        }

        // Iterar el primer nivel del arreglo para encontrar los tags que se
        // deben agregar al documento XML.
        foreach ($data as $key => $value) {

            // Si el índice es '@attributes' entonces el valor de este índice
            // es un arreglo donde la llave es el nombre del atributo del tag
            // de $parent y el valor es el valor del atributo.
            if ($key === '@attributes') {
                // Solo se agregan atributos si el valor es un arreglo.
                if (is_array($value)) {
                    self::nodeAddAttributes($parent, $value);
                }
            }

            // Si el índice es '@value' entonces se debe asignar directamente
            // el valor al nodo, pues es un escalar (no un arreglo con nodos
            // hijos). Este caso normalmente se usa cuando se crea un nodo que
            // debe tener valor y atributos.
            elseif ($key === '@value') {
                if (!self::skipValue($value)) {
                    $parent->nodeValue = XmlUtils::sanitize($value);
                }
            }

            // Acá el índice es el nombre de un nodo. En este caso, el nodo es
            // un arreglo. Por lo que se procesará recursivamente para agregar
            // a este nodo los nodos hijos que están en el arreglo.
            elseif (is_array($value)) {
                // Solo se crea el nodo si tiene nodos hijos. El nodo no será
                // creado si se pasa un arreglo vacio (sin hijos).
                if (!empty($value)) {
                    self::nodeAddChilds($doc, $parent, $key, $value, $namespace);
                }
            }

            // El nodo es un escalar (no es un arreglo, no son nodos hijos).
            // Por lo que se crea el nodo y asigna el valor directamente.
            else {
                if (!self::skipValue($value)) {
                    self::nodeAddValue(
                        $doc,
                        $parent,
                        $key,
                        (string) $value,
                        $namespace
                    );
                }
            }
        }

        // Entregar el documento XML generado.
        return $doc;
    }

    /**
     * Agrega atributos a un nodo XML a partir de un arreglo.
     *
     * @param DOMElement $node Nodo al que se agregarán los atributos.
     * @param array $attributes Arreglo de atributos (clave => valor).
     * @return void
     * @throws InvalidArgumentException Si un valor de atributo es un arreglo.
     */
    private static function nodeAddAttributes(DOMElement $node, array $attributes): void
    {
        foreach ($attributes as $attribute => $value) {
            // Si el valor del atributo es un arreglo no se puede asignar.
            if (is_array($value)) {
                throw new InvalidArgumentException(sprintf(
                    'El tipo de dato del valor ingresado para el atributo "%s" del nodo "%s" es incorrecto (no puede ser un arreglo). El valor es: %s',
                    $attribute,
                    $node->tagName,
                    json_encode($value)
                ));
            }
            // Asignar el valor del atributo solo si no se debe omitir según
            // el tipo valor que se quiera asignar.
            if (!self::skipValue($value)) {
                $node->setAttribute($attribute, $value);
            }
        }
    }

    /**
     * Agrega nodos hijos a un nodo XML a partir de un arreglo.
     *
     * @param XmlDocument $doc Documento XML en el que se agregarán los nodos.
     * @param DOMNode $parent Nodo padre al que se agregarán los
     * nodos hijos.
     * @param string $tagName Nombre del tag del nodo hijo.
     * @param array $childs Arreglo de datos de los nodos hijos.
     * @param array|null $namespace Espacio de nombres para el XML (URI y
     * prefijo).
     * @return void
     * @throws InvalidArgumentException Si un nodo hijo no es un arreglo.
     */
    private static function nodeAddChilds(
        XmlDocument $doc,
        DOMNode $parent,
        string $tagName,
        array $childs,
        ?array $namespace = null,
    ): void {
        $keys = array_keys($childs);
        if (!is_int($keys[0])) {
            $childs = [$childs];
        }
        foreach ($childs as $child) {
            // Omitir valores que deben ser saltados.
            if (self::skipValue($child)) {
                continue;
            }

            // Si el hijo es un arreglo se crea un nodo para el hijo y se
            // agregan los elementos que están en el arreglo.
            if (is_array($child)) {

                // Si el arreglo no es asociativo (con nuevos nodos) error.
                if (isset($child[0])) {
                    throw new InvalidArgumentException(sprintf(
                        'El nodo "%s" permite incluir arreglos, pero deben ser arreglos con otros nodos. El valor actual es incorrecto: %s',
                        $tagName,
                        json_encode($child)
                    ));
                }

                // Agregar nodos hijos del nodo hijo (agregar
                // asociativo al nodo $tagName).
                $Node = $namespace
                    ? $doc->createElementNS(
                        $namespace[0],
                        $namespace[1] . ':' . $tagName
                    )
                    : $doc->createElement($tagName)
                ;
                $parent->appendChild($Node);
                self::encode($child, $namespace, $Node, $doc);
            }
            // Si el hijo no es un arreglo, es simplemente un nodo duplicado
            // que se debe agregar en el mismo nivel que el nodo padre.
            else {
                $value = XmlUtils::sanitize((string) $child);
                $Node = $namespace
                    ? $doc->createElementNS(
                        $namespace[0],
                        $namespace[1] . ':' . $tagName,
                        $value
                    )
                    : $doc->createElement($tagName, $value)
                ;
                $parent->appendChild($Node);
            }
        }
    }

    /**
     * Agrega un nodo XML con un valor escalar a un nodo padre.
     *
     * @param XmlDocument $doc Documento XML en el que se agregarán los nodos.
     * @param DOMNode $parent Nodo padre al que se agregará el nodo hijo.
     * @param string $tagName Nombre del tag del nodo hijo.
     * @param string $value Valor del nodo hijo.
     * @param array|null $namespace Espacio de nombres para el XML (URI y
     * prefijo).
     * @return void
     */
    private static function nodeAddValue(
        XmlDocument $doc,
        DOMNode $parent,
        string $tagName,
        string $value,
        ?array $namespace = null,
    ): void {
        $value = XmlUtils::sanitize($value);
        $Node = $namespace
            ? $doc->createElementNS(
                $namespace[0],
                $namespace[1] . ':' . $tagName,
                $value
            )
            : $doc->createElement($tagName, $value)
        ;
        $parent->appendChild($Node);
    }

    /**
     * Verifica si un valor debe omitirse al generar un nodo XML.
     *
     * @param mixed $value Valor a verificar.
     * @return bool `true` si el valor debe omitirse, `false` en caso contrario.
     */
    private static function skipValue(mixed $value): bool
    {
        return in_array($value, self::$rules['node_values']['skip_generation'], true);
    }

    /**
     * Verifica si un valor debe generar un nodo XML vacío.
     *
     * @param mixed $value Valor a verificar.
     * @return bool `true` si el valor debe generar un nodo vacío, `false` en
     * caso contrario.
     */
    // private static function createWithEmptyValue(mixed $value): bool
    // {
    //     return in_array($value, self::$rules['node_values']['generate_empty'], true);
    // }
}

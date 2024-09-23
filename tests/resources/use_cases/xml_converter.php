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

use libredte\lib\Core\Xml\XmlException;

return [

    // Casos para testArrayToXmlAndBackToArray().
    'testArrayToXmlAndBackToArray' => [
        // Caso más simple con un solo elemento.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => ['root' => ['element' => 'value']],
            'expectedException' => null,
        ],
        // Arreglo con múltiples elementos y variantes.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expectedException' => null,
        ],
        // Arreglo con caracteres especiales que deben escaparse.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => ['root' => ['element' => 'Special: & < > " \'']],
            'expectedException' => null,
        ],
        // Arreglo con caracteres en UTF-8 que deben convertirse a ISO-8859-1.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'expected' => ['root' => ['element' => 'Árbol']],
            'expectedException' => null,
        ],
        // Arreglo inválido (vacío). No genera excepción, pero no es válido.
        'invalid_array' => [
            'data' => [],
            'expected' => [],
            'expectedException' => null,
        ],
        // Caso con atributos en los nodos.
        'element_with_attributes' => [
            'data' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expected' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expectedException' => null,
        ],
        // Caso con nodos vacíos.
        'empty_node' => [
            'data' => ['root' => ['element' => '']],
            'expected' => ['root' => ['element' => '']],
            'expectedException' => null,
        ],
        // Caso con múltiples valores repetidos.
        'repeated_nodes' => [
            'data' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expected' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expectedException' => null,
        ],
    ],

    // Casos para testArrayToXmlSaveXml().
    'testArrayToXmlSaveXml' => [
        // Caso simple con un solo elemento.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>'
                        . "\n<root>\n  <element>value</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Arreglo con múltiples elementos.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>'
                        . "\n<root>\n  <element1>value1</element1>\n"
                        . "  <element2>value2</element2>\n"
                        . "  <element3>value3</element3>\n</root>\n",
            'expectedException' => null,
        ],
        // Arreglo con caracteres especiales que deben escaparse.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>'
                        . "\n<root>\n  <element>Special: &amp; &lt; &gt; &quot; &apos;</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Arreglo con caracteres en UTF-8 que deben convertirse a ISO-8859-1.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>'
                        . "\n<root>\n  <element>" . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . "</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Arreglo inválido (vacío). No genera excepción, pero no es válido.
        'invalid_array' => [
            'data' => [],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n",
            'expectedException' => null,
        ],
        // Caso con atributos en los nodos.
        'element_with_attributes' => [
            'data' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n<root>\n  <element attr1=\"value1\">content</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Caso con nodos vacíos.
        'empty_node' => [
            'data' => ['root' => ['element' => '']],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n<root>\n  <element/>\n</root>\n",
            'expectedException' => null,
        ],
        // Caso con múltiples valores repetidos.
        'repeated_nodes' => [
            'data' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n<root>\n  <item>value1</item>\n  <item>value2</item>\n  <item>value3</item>\n</root>\n",
            'expectedException' => null,
        ],
        // Control de espacios en blanco.
        'whitespace_control' => [
            'data' => ['root' => ['element' => '  value  ']],
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n<root>\n  <element>  value  </element>\n</root>\n",
            'expectedException' => null,
        ],
    ],

    // Casos para testArrayToXmlC14N().
    'testArrayToXmlC14N' => [
        // Caso simple con un solo elemento.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // Arreglo con múltiples elementos.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => '<root><element1>value1</element1>'
                        . '<element2>value2</element2>'
                        . '<element3>value3</element3></root>',
            'expectedException' => null,
        ],
        // Arreglo con caracteres especiales que deben escaparse.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // Arreglo con caracteres en UTF-8 que deben mantenerse como UTF-8.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'expected' => '<root><element>Árbol</element></root>',
            'expectedException' => null,
        ],
        // Arreglo inválido (vacío). No genera excepción, pero no es válido.
        'invalid_array' => [
            'data' => [],
            'expected' => '',
            'expectedException' => null,
        ],
        // Caso con atributos en los nodos.
        'element_with_attributes' => [
            'data' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expected' => '<root><element attr1="value1">content</element></root>',
            'expectedException' => null,
        ],
        // Caso con nodos vacíos.
        'empty_node' => [
            'data' => ['root' => ['element' => '']],
            'expected' => '<root><element></element></root>',
            'expectedException' => null,
        ],
        // Caso con múltiples valores repetidos.
        'repeated_nodes' => [
            'data' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expected' => '<root><item>value1</item><item>value2</item><item>value3</item></root>',
            'expectedException' => null,
        ],
    ],

    // Casos para testArrayToXmlC14NWithIsoEncoding().
    'testArrayToXmlC14NWithIsoEncoding' => [
        // Caso simple con un solo elemento.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // Arreglo con múltiples elementos.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => '<root><element1>value1</element1>'
                        . '<element2>value2</element2>'
                        . '<element3>value3</element3></root>',
            'expectedException' => null,
        ],
        // Arreglo con caracteres especiales que deben escaparse.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // Arreglo con caracteres en UTF-8 que deben convertirse a ISO-8859-1.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'expected' => '<root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        // Arreglo inválido (vacío). No genera excepción, pero no es válido.
        'invalid_array' => [
            'data' => [],
            'expected' => '',
            'expectedException' => null,
        ],
    ],

    // Casos para testXmlToArray().
    'testXmlToArray' => [
        // XML simple con un solo elemento (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => ['root' => ['element' => 'value']],
            'expectedException' => null,
        ],
        // XML con múltiples elementos (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => [
                'root' => [
                    'element1' => 'value1',
                    'element2' => 'value2',
                ],
            ],
            'expectedException' => null,
        ],
        // XML con caracteres especiales escapados (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => [
                'root' => ['element' => 'Special: & < > " \''],
            ],
            'expectedException' => null,
        ],
        // XML en UTF-8 con caracteres especiales (ej. tildes, ñ).
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'expected' => ['root' => ['element' => 'Árbol']],
            'expectedException' => null,
        ],
        // XML en ISO-8859-1 con caracteres especiales (ej. tildes, ñ).
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expected' => ['root' => ['element' => 'Árbol']],
            'expectedException' => null,
        ],
        // XML mal formado (inválido), esperando una excepción.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => [],
            'expectedException' => XmlException::class,
        ],
        // Caso con atributos en los nodos.
        'element_with_attributes' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element attr1="value1">content</element></root>',
            'expected' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expectedException' => null,
        ],
        // Caso con nodos vacíos.
        'empty_node' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element></element></root>',
            'expected' => ['root' => ['element' => '']],
            'expectedException' => null,
        ],
        // Caso con múltiples valores repetidos.
        'repeated_nodes' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><item>value1</item><item>value2</item><item>value3</item></root>',
            'expected' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expectedException' => null,
        ],
    ],

    // Casos para testXmlToSaveXml().
    'testXmlToSaveXml' => [
        // XML simple con un solo elemento (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element>value</element>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML con múltiples elementos (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element1>value1</element1>'
                . "\n" . '  <element2>value2</element2>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML con caracteres especiales escapados (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element>Special: &amp; &lt; &gt; &quot; &apos;</element>'
                . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML en UTF-8 con caracteres especiales (ej. tildes, ñ).
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8')
                . '</element>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML en ISO-8859-1 con caracteres especiales (ej. tildes, ñ).
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8')
                . '</element>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML mal formado (inválido), esperando una excepción.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => '',
            'expectedException' => XmlException::class,
        ],
    ],

    // Casos para testXmlToC14N().
    'testXmlToC14N' => [
        // XML simple con un solo elemento (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // XML con múltiples elementos (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => '<root><element1>value1</element1><element2>value2</element2></root>',
            'expectedException' => null,
        ],
        // XML con caracteres especiales escapados (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // XML en UTF-8 con caracteres especiales (ej. tildes, ñ) debe mantenerse como UTF-8.
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'expected' => '<root><element>Árbol</element></root>',
            'expectedException' => null,
        ],
        // XML en ISO-8859-1 con caracteres especiales (ej. tildes, ñ) debe convertirse a UTF-8.
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expected' => '<root><element>Árbol</element></root>',
            'expectedException' => null,
        ],
        // XML mal formado (inválido), esperando una excepción.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => '',
            'expectedException' => XmlException::class,
        ],
    ],

    // Casos para testXmlToC14NWithIsoEncoding().
    'testXmlToC14NWithIsoEncoding' => [
        // XML simple con un solo elemento (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // XML con múltiples elementos (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => '<root><element1>value1</element1><element2>value2</element2></root>',
            'expectedException' => null,
        ],
        // XML con caracteres especiales escapados (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // XML en UTF-8 con caracteres especiales (ej. tildes, ñ) debe convertise a ISO-8859-1.
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'expected' => '<root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        // XML en ISO-8859-1 con caracteres especiales (ej. tildes, ñ) debe mantenerse como ISO-8859-1.
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expected' => '<root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        // XML mal formado (inválido), esperando una excepción.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => '',
            'expectedException' => XmlException::class,
        ],
    ],

];

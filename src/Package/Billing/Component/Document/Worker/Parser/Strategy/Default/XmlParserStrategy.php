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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Xml\XmlDocument;
use DOMDocument;
use DOMNode;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;

/**
 * Estrategia "billing.document.parser#strategy:default.xml".
 */
#[Strategy(name: 'default.xml', worker: 'parser', component: 'document', package: 'billing')]
class XmlParserStrategy extends AbstractStrategy implements ParserStrategyInterface
{
    /**
     * Constructor de la estrategia del parser.
     *
     * @param XmlServiceInterface $xmlService
     */
    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function parse(string $data): array
    {
        // Cargar los datos del XML a un arreglo.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($data);

        $dom = new DOMDocument();
        $dom->loadXML($xmlDocument->getXml());

        $array = $this->domToArray($dom->documentElement);

        // Obtener los datos del documento a generar.
        $documentoData = $array['Documento']
            ?? $array['Exportaciones']
            ?? $array['Liquidacion']
            ?? null
        ;
        if (is_array($documentoData)) {
            $documentoData = $this->limpiarXmlArray($documentoData);
            unset($documentoData['@attributes']);
        }
        if ($documentoData === null) {
            throw new ParserException(
                'El nodo raíz del XML del documento debe ser el tag "DTE". Dentro de este nodo raíz debe existir un tag "Documento", "Exportaciones" o "Liquidacion". Este segundo nodo es el que debe contener los datos del documento.'
            );
        }
        return $documentoData;
    }
    function domToArray(DOMNode $node): array|string {
        if ($node->nodeType == XML_TEXT_NODE) {
            return trim($node->nodeValue);
        }

        $output = [];

        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $output['@attributes'][$attr->nodeName] = $attr->nodeValue;
            }
        }

        foreach ($node->childNodes as $child) {
            if ($child->nodeType == XML_TEXT_NODE && trim($child->nodeValue) === '') {
                continue;
            }

            $value = $this->domToArray($child);
            $tag = $child->nodeName;

            if (isset($output[$tag])) {
                if (!is_array($output[$tag]) || !isset($output[$tag][0])) {
                    $output[$tag] = [$output[$tag]];
                }
                $output[$tag][] = $value;
            } else {
                $output[$tag] = $value;
            }
        }

        return $output;
    }
    private function limpiarXmlArray(array $data): array
    {
        $resultado = [];

        foreach ($data as $clave => $valor) {
            if (is_array($valor)) {
                if (array_keys($valor) === ['#text']) {
                    $resultado[$clave] = $valor['#text'];
                } elseif (array_is_list($valor)) {
                    $resultado[$clave] = array_map([$this, 'limpiarXmlArray'], $valor);
                } elseif (isset($valor['@attributes'])) {
                    unset($valor['@attributes']);
                    $resultado[$clave] = $this->limpiarXmlArray($valor);
                } else {
                    $resultado[$clave] = $this->limpiarXmlArray($valor);
                }
            } else {
                $resultado[$clave] = $valor;
            }
        }

        return $resultado;
    }
}

<?php

declare(strict_types=1);

namespace libredte\lib\Core\Package\Billing\Component\Document\Support;

use DOMNode;

trait XmlArrayTrait
{
    public function domToArray(DOMNode $node): array|string
    {
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


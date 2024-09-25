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

/**
 * Clase `XmlConverter` que proporciona métodos para convertir entre arreglos
 * PHP y documentos XML, permitiendo generar nodos XML a partir de datos
 * estructurados y viceversa.
 */
class XmlConverter
{
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
    public static function arrayToXml(
        array $data,
        ?array $namespace = null,
        ?DOMElement $parent = null,
        ?XmlDocument $doc = null
    ): XmlDocument {
        return XmlEncoder::encode($data, $namespace, $parent, $doc);
    }

    /**
     * Convierte un documento XML a un arreglo PHP.
     *
     * @param XmlDocument|DOMElement $doc Documento XML que se desea convertir a
     * un arreglo de PHP o el elemento donde vamos a hacer la conversión si no
     * es el documento XML completo.
     * @param array|null $data Arreglo donde se almacenarán los resultados.
     * @param bool $twinsAsArray Indica si se deben tratar los nodos gemelos
     * como un arreglo.
     * @return array Arreglo con la representación del XML.
     */
    public static function xmlToArray(
        XmlDocument|DOMElement $doc,
        ?array &$data = null,
        bool $twinsAsArray = false
    ): array {
        return XmlDecoder::decode($doc, $data, $twinsAsArray);
    }
}

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

use DomDocument;
use DOMNode;

/**
 * Clase que representa un documento XML.
 */
class XmlDocument extends DomDocument
{
    /**
     * Constructor del documento XML.
     *
     * @param string $version Versión del documento XML.
     * @param string $encoding Codificación del documento XML.
     */
    public function __construct(
        string $version = '1.0',
        string $encoding = 'ISO-8859-1'
    ) {
        parent::__construct($version, $encoding);

        $this->formatOutput = true;
        $this->preserveWhiteSpace = true;
    }

    /**
     * Entrega el nombre del tag raíz del XML.
     *
     * @return string Nombre del tag raíz.
     */
    public function getName(): string
    {
        return $this->documentElement->tagName;
    }

    /**
     * Obtiene el espacio de nombres (namespace) del elemento raíz del
     * documento XML.
     *
     * @return string|null Espacio de nombres del documento XML o `null` si no
     * está presente.
     */
    public function getNamespace(): ?string
    {
        $namespace = $this->documentElement->getAttribute('xmlns');

        return $namespace !== '' ? $namespace : null;
    }

    /**
     * Entrega el nombre del archivo del schema del XML.
     *
     * @return string|null Nombre del schema o `null` si no se encontró.
     */
    public function getSchema(): ?string
    {
        $schemaLocation = $this->documentElement->getAttribute(
            'xsi:schemaLocation'
        );

        if (!$schemaLocation || !str_contains($schemaLocation, ' ')) {
            return null;
        }

        return explode(' ', $schemaLocation)[1];
    }

    /**
     * Carga un string XML en la instancia del documento XML.
     *
     * @param string $source String con el documento XML a cargar.
     * @param int $options Opciones para la carga del XML.
     * @return bool `true` si el XML se cargó correctamente.
     * @throws XmlException Si no es posible cargar el XML.
     */
    public function loadXML(string $source, int $options = 0): bool
    {
        // Si no hay un string XML en el origen entonces se retorna `false`.
        if (empty($source)) {
            throw new XmlException('El contenido XML está vacío.');
        }

        // Convertir el XML si es necesario.
        preg_match('/<\?xml\s+version="([^"]+)"\s+encoding="([^"]+)"\?>/', $source, $matches);
        $version = $matches[1] ?? $this->xmlVersion;
        $encoding = strtoupper($matches[2] ?? $this->encoding);
        if ($encoding === 'UTF-8') {
            $source = XmlUtils::utf2iso($source);
            $source = str_replace(' encoding="UTF-8"?>', ' encoding="ISO-8859-1"?>', $source);
        }

        // Obtener estado actual de libxml y cambiarlo antes de cargar el XML
        // para obtener los errores en una variable si falla algo.
        $useInternalErrors = libxml_use_internal_errors(true);

        // Cargar el XML.
        $status = parent::loadXML($source, $options);

        // Obtener errores, limpiarlos y restaurar estado de errores de libxml.
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if (!$status) {
            throw new XmlException('Error al cargar el XML.', $errors);
        }

        // Retornar estado de la carga del XML.
        // Sólo retornará `true`, pues si falla lanza excepción.
        return true;
    }

    /**
     * Genera el documento XML como string.
     *
     * Wrapper de parent::saveXML() para poder corregir XML entities.
     *
     * Incluye encabezado del XML con versión y codificación.
     *
     * @param DOMNode|null $node Nodo a serializar.
     * @param int $options Opciones de serialización.
     * @return string XML serializado y corregido.
     */
    public function saveXML(?DOMNode $node = null, int $options = 0): string
    {
        $xml = parent::saveXML($node, $options);

        return XmlUtils::fixEntities($xml);
    }

    /**
     * Genera el documento XML como string.
     *
     * Wrapper de saveXML() para generar un string sin el encabezado del XML y
     * sin salto de línea inicial o final.
     *
     * @return string XML serializado y corregido.
     */
    public function getXML(): string
    {
        $xml = $this->saveXML();
        $xml = preg_replace(
            '/<\?xml\s+version="1\.0"\s+encoding="[^"]+"\s*\?>/i',
            '',
            $xml
        );

        return trim($xml);
    }

    /**
     * Entrega el string XML canonicalizado y con la codificación que
     * corresponde (ISO-8859-1).
     *
     * Esto básicamente usa C14N(), sin embargo, C14N() siempre entrega el XML
     * en codificación UTF-8. Por lo que este método permite obtenerlo con C14N
     * pero con la codificación correcta de ISO-8859-1. Además se corrigen las
     * XML entities.
     *
     * @param string|null $xpath XPath para consulta al XML y extraer solo una
     * parte, desde un tag/nodo específico.
     * @return string String XML canonicalizado.
     * @throws XmlException En caso de ser pasado un XPath y no encontrarlo.
     */
    public function C14NWithIsoEncoding(?string $xpath = null): string
    {
        // Si se proporciona XPath, filtrar los nodos.
        if ($xpath) {
            $node = XmlUtils::xpath($this, $xpath)->item(0);
            if (!$node) {
                throw new XmlException(sprintf(
                    'No fue posible obtener el nodo con el XPath %s.',
                    $xpath
                ));
            }
            $xml = $node->C14N();
        }
        // Usar C14N() para todo el documento si no se especifica XPath.
        else {
            $xml = $this->C14N();
        }

        // Corregir XML entities.
        $xml = XmlUtils::fixEntities($xml);

        // Convertir el XML aplanado de UTF-8 a ISO-8859-1.
        // Requerido porque C14N() siempre entrega los datos en UTF-8.
        $xml = XmlUtils::utf2iso($xml);

        // Entregar el XML canonicalizado.
        return $xml;
    }

    /**
     * Entrega el string XML canonicalizado, con la codificación que
     * corresponde (ISO-8859-1) y aplanado.
     *
     * Es un wrapper de C14NWithIsoEncoding() que aplana el XML resultante.
     *
     * @param string|null $xpath XPath para consulta al XML y extraer solo una
     * parte, desde un tag/nodo específico.
     * @return string String XML canonicalizado y aplanado.
     * @throws XmlException En caso de ser pasado un XPath y no encontrarlo.
     */
    public function C14NWithIsoEncodingFlattened(?string $xpath = null): string
    {
        // Obtener XML canonicalizado y codificado en ISO8859-1.
        $xml = $this->C14NWithIsoEncoding($xpath);

        // Eliminar los espacios entre tags.
        $xml = preg_replace("/>\s+</", '><', $xml);

        // Entregar el XML canonicalizado y aplanado.
        return $xml;
    }
}

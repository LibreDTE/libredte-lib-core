<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\Documento\Parser\Sii;

use libredte\lib\Core\Sii\Dte\Documento\Parser\DocumentoParserException;
use libredte\lib\Core\Sii\Dte\Documento\Parser\DocumentoParserInterface;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;

/**
 * Transforma los datos en formato XML con la estructura oficial del SII a un
 * arreglo PHP con la estructura oficial del SII.
 */
class XmlParser implements DocumentoParserInterface
{
    /**
     * Realiza la transformación de los datos del documento.
     *
     * @param string $data XML con los datos de entrada.
     * @return array Arreglo transformado a la estructura oficial del SII.
     */
    public function parse(string $data): array
    {
        // Cargar los datos del XML a un arreglo.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXML($data);
        $array = XmlConverter::xmlToArray($xmlDocument);

        // Obtener los datos del documento a generar.
        $documentoData = $array['DTE']['Documento']
            ?? $array['DTE']['Exportaciones']
            ?? $array['DTE']['Liquidacion']
            ?? null
        ;

        // Si el XML no tiene los tags válidos se lanza una excepción.
        if ($documentoData === null) {
            throw new DocumentoParserException(
                'El nodo raíz del XML del documento debe ser el tag "DTE". Dentro de este nodo raíz debe existir un tag "Documento", "Exportaciones" o "Liquidacion". Este segundo nodo es el que debe contener los datos del documento.'
            );
        }

        // Quitar los atributos que tenga el tag encontrado.
        unset($documentoData['@attributes']);

        // Entregar los datos parseados.
        return $documentoData;
    }
}

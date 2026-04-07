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

namespace libredte\lib\Core\Package\Billing\Component\Book\Entity;

use Derafu\Xml\Contract\XmlDocumentInterface;
use DOMDocument;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LibroBoletasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;

/**
 * Entidad que representa un Libro de Boletas Electrónicas.
 *
 * El resumen del período agrupa los totales por tipo de documento (`TpoDoc`)
 * y tipo de servicio (`TpoServ`).
 */
class LibroBoletas extends AbstractBook implements LibroBoletasInterface
{
    /**
     * Tipo de libro.
     */
    protected TipoLibro $tipo = TipoLibro::BOLETAS;

    /**
     * {@inheritDoc}
     *
     * Se sobrescribe para reemplazar el namespace de la firma electrónica por
     * lo esperado por el SII.
     */
    public function getXmlDocument(): XmlDocumentInterface
    {
        $xmlDocument = parent::getXmlDocument();

        assert($xmlDocument instanceof DOMDocument);

        $signature = $xmlDocument->getElementsByTagName('Signature')->item(0);

        if ($signature !== null && $signature->namespaceURI !== 'http://www.sii.cl/SiiDte') {
            $xmlString = str_replace(
                'xmlns="http://www.w3.org/2000/09/xmldsig#"',
                'xmlns="http://www.sii.cl/SiiDte"',
                $xmlDocument->saveXML()
            );
            $xmlDocument->loadXML($xmlString);
        }

        return $xmlDocument;
    }
}

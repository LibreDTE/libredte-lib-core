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

namespace libredte\lib\Core\Signature;

use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlException;

/**
 * Clase que maneja la generación de firmas electrónicas, en particular para
 * documentos XML.
 */
class SignatureGenerator
{
    /**
     * Firma los datos proporcionados utilizando un certificado digital.
     *
     * @param string $data Datos que se desean firmar.
     * @param string $privateKey Clave privada que se utilizará para firmar.
     * @param string|int $signatureAlgorithm Algoritmo que se utilizará para
     * firmar (por defecto SHA1).
     * @return string Firma digital en base64.
     */
    public static function sign(
        string $data,
        string $privateKey,
        string|int $signatureAlgorithm = OPENSSL_ALGO_SHA1
    ): string {
        // Firmar los datos.
        $signature = null;
        $result = openssl_sign(
            $data,
            $signature,
            $privateKey,
            $signatureAlgorithm
        );

        // Si no se logró firmar los datos se lanza una excepción.
        if ($result === false) {
            throw new SignatureException('No fue posible firmar los datos.');
        }

        // Entregar la firma en base64.
        return base64_encode($signature);
    }

    /**
     * Firma un documento XML utilizando RSA y SHA1.
     *
     * @param XmlDocument|string $xml Documento XML que se desea firmar.
     * @param Certificate $certificate Certificado digital para firmar.
     * @param ?string $reference Referencia a la que se hace la firma. Si no se
     * especifica se firmará el digest de todo el documento XML.
     * @return string String XML con la firma generada incluída en el tag
     * "Signature" al final del XML (último elemento dentro del nodo raíz).
     * @throws SignatureException Si ocurre algún problema al firmar.
     */
    public static function signXML(
        XmlDocument|string $xml,
        Certificate $certificate,
        ?string $reference = null
    ): string {
        // Si se pasó un objeto XmlDocument se convierte a string. Esto es
        // necesario para poder mantener el formato "lindo" si se pasó y poder
        // obtener el C14N de manera correcta.
        if (!is_string($xml)) {
            $xml = $xml->saveXML();
        }

        // Cargar el XML que se desea firmar.
        $doc = new XmlDocument();
        $doc->loadXML($xml);
        if (!$doc->documentElement) {
            throw new SignatureException(
                'No se pudo obtener el documentElement desde el XML a firmar (posible XML mal formado).'
            );
        }

        // Calcular el "DigestValue" de los datos de la referencia.
        $digestValue = self::digestXmlReference($doc, $reference);

        // Crear la instancia que representa el nodo de la firma con sus datos.
        $xmlSignature = (new XmlSignatureNode())
            ->setReference($reference)
            ->setDigestValue($digestValue)
            ->sign($certificate)
            ->getXML()
        ;

        // Agregar la firma del XML en el nodo Signature.
        $signatureElement = $doc->createElement('Signature', '');
        $doc->documentElement->appendChild($signatureElement);
        $xmlSigned = str_replace('<Signature/>', $xmlSignature, $doc->saveXML());

        // Entregar el string XML del documento XML firmado.
        return $xmlSigned;
    }

    /**
     * Genera la digestión SHA1 ("DigestValue") de un nodo del XML con cierta
     * referencia. Esto podrá ser usado luego para generar la firma del XML.
     *
     * Si no se indica una referencia se calculará el "DigestValue" sobre todo
     * el XML (nodo raíz).
     *
     * @param XmlDocument $doc Documento XML que se desea firmar.
     * @param ?string $reference Referencia a la que se hace la firma.
     * @return string Datos del XML que deben ser digeridos.
     * @throws XmlException En caso de no encontrar la referencia en el XML.
     */
    public static function digestXmlReference(
        XmlDocument $doc,
        ?string $reference = null
    ): string {
        // Se hará la digestión de una referencia (ID) específico en el XML.
        if (!empty($reference)) {
            $xpath = '//*[@ID="' . ltrim($reference, '#') . '"]';
            $dataToDigest = $doc->C14NWithIsoEncoding($xpath);
        }
        // Cuando no hay referencia, el digest es sobre todo el documento XML.
        // Si el XML ya tiene un nodo "Signature" dentro del nodo raíz se debe
        // eliminar ese nodo del XML antes de obtener su C14N.
        else {
            $docClone = clone $doc;
            $rootElement = $docClone->documentElement;
            $signatureElement = $rootElement
                ->getElementsByTagName('Signature')
                ->item(0)
            ;
            if ($signatureElement) {
                $rootElement->removeChild($signatureElement);
            }
            $dataToDigest = $docClone->C14NWithIsoEncoding();
        }

        // Calcular la digestión sobre los datos del XML en formato C14N.
        $digestValue = base64_encode(sha1($dataToDigest, true));

        // Entregar el digest calculado.
        return $digestValue;
    }
}

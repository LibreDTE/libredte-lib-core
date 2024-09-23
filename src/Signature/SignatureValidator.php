<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

/**
 * Clase que maneja la validación de firmas electrónicas.
 */
class SignatureValidator
{
    /**
     * Verifica la firma digital de datos.
     *
     * @param string $data Datos que se desean verificar.
     * @param string $signature Firma digital de los datos en base64.
     * @param string $publicKey Clave pública de la firma de los datos.
     * @param string|int $signatureAlgorithm Algoritmo que se usó para firmar
     * (por defecto SHA1).
     * @return bool `true` si la firma es válida, `false` si es inválida.
     * @throws SignatureException Si hubo un error al hacer la verificación.
     */
    public static function validate(
        string $data,
        string $signature,
        string $publicKey,
        string|int $signatureAlgorithm = OPENSSL_ALGO_SHA1
    ): bool
    {
        $publicKey = CertificateUtils::normalizePublicKey($publicKey);

        $result = openssl_verify(
            $data,
            base64_decode($signature),
            $publicKey,
            $signatureAlgorithm
        );

        if ($result === -1) {
            throw new SignatureException(
                'Ocurrió un error al verificar la firma electrónica de los datos.'
            );
        }

        return $result === 1;
    }

    /**
     * Verifica la validez de la firma de un XML utilizando RSA y SHA1.
     *
     * @param XmlDocument|string $xml String XML que se desea validar.
     * @return void
     * @throws SignatureException Si hubo un error al hacer la verificación.
     */
    public static function validateXml(XmlDocument|string $xml): void
    {
        // Si se pasó un objeto XmlDocument se convierte a string.
        if (!is_string($xml)) {
            $xml = $xml->saveXML();
        }

        // Cargar el string XML en un documento XML.
        $doc = new XmlDocument();
        $doc->loadXML($xml);
        if (!$doc->documentElement) {
            throw new SignatureException(
                'No se pudo obtener el documentElement desde el XML para validar su firma (posible XML mal formado).'
            );
        }

        // Buscar todos los elementos que sean tag Signature.
        // Un documento XML puede tener más de una firma.
        $signaturesElements = $doc->documentElement->getElementsByTagName(
            'Signature'
        );

        // Si no se encontraron firmas en el XML error.
        if (!$signaturesElements->length) {
            throw new SignatureException(
                'No se encontraron firmas que validar en el XML.'
            );
        }

        // Iterar cada firma encontrada.
        foreach ($signaturesElements as $signatureElement) {
            // Armar instancia del nodo de la firma.
            $xmlSignatureNode = new XmlSignatureNode();
            $xmlSignatureNode->loadXML($signatureElement->C14N());

            // Validar DigestValue de los datos firmados.
            $xmlSignatureNode->validate($doc);
        }
    }
}

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

use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;

/**
 * Clase que representa el nodo "Signature" en un XML firmado electrónicamente
 * utilizando el estándar de firma digital de XML (XML DSIG).
 */
class XmlSignatureNode
{
    /**
     * Documento XML que representa el nodo de la firma electrónica.
     *
     * @var XmlDocument
     */
    private XmlDocument $doc;

    /**
     * Instancia del certificado digital cuando se están creando firmas.
     *
     * @var Certificate
     */
    private Certificate $certificate;

    /**
     * Datos del nodo Signature.
     *
     * Por defecto se dejan vacíos los datos que se completarán posteriormente.
     * Ya sea mediante una asignación de los datos o bien mediante la carga de
     * un nuevo XML con los datos.
     *
     * @var array
     */
    private array $data = [
        // Nodo raíz es Signature.
        // Este es el nodo que se incluirá en los XML firmados.
        'Signature' => [
            '@attributes' => [
                'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
            ],
            // Datos que se firmarán. Acá el más importante es el tag
            // "DigestValue" que contiene un "resumen" (digestión) del C14N
            // del nodo de la referencia.
            'SignedInfo' => [
                '@attributes' => [
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                ],
                'CanonicalizationMethod' => [
                    '@attributes' => [
                        'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                    ],
                ],
                'SignatureMethod' => [
                    '@attributes' => [
                        'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                    ],
                ],
                'Reference' => [
                    '@attributes' => [
                        // Indica cuál es el nodo de la referencia, debe tener
                        // como prefijo un "#". Si está vacío se entiende que
                        // se desea firmar todo el XML.
                        'URI' => '', // Opcional.
                    ],
                    'Transforms' => [
                        'Transform' => [
                            '@attributes' => [
                                'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                            ],
                        ],
                    ],
                    'DigestMethod' => [
                        '@attributes' => [
                            'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                        ],
                    ],
                    'DigestValue' => '', // Obligatorio.
                ],
            ],
            // Firma del C14N del nodo "SignedInfo".
            // Se agrega después de construir el C14N del SignedInfo y firmar.
            'SignatureValue' => '', // Obligatorio.
            // Información de la clave pública para la validación posterior
            // de la firma electrónica.
            'KeyInfo' => [
                'KeyValue' => [
                    'RSAKeyValue' => [
                        'Modulus' => '', // Obligatorio.
                        'Exponent' => '', // Obligatorio.
                    ],
                ],
                'X509Data' => [
                    'X509Certificate' => '', // Obligatorio.
                ],
            ],
        ],
    ];

    /**
     * Carga el contenido de un nodo XML "Signature" en la instancia actual.
     *
     * @param string $xml String XML que representa el nodo "Signature".
     * @return self La instancia actual para encadenamiento de métodos.
     */
    public function loadXML(string $xml): self
    {
        // Cargar el XML y obtener su arreglo de datos.
        $this->doc = new XmlDocument();
        $this->doc->formatOutput = false;
        $this->doc->loadXML($xml);
        $this->data = XmlConverter::xmlToArray($this->doc);

        // Retornar instancia para encadenamiento.
        return $this;
    }

    /**
     * Obtiene el objeto `XmlDocument` que representa el nodo "Signature".
     *
     * @return XmlDocument El objeto `XmlDocument` con los datos del nodo
     * "Signature".
     */
    private function getXmlDocument(): XmlDocument
    {
        if (!isset($this->doc)) {
            $this->doc = new XmlDocument();
            $this->doc->formatOutput = false;
            XmlConverter::arrayToXml($this->data, null, null, $this->doc);
        }

        return $this->doc;
    }

    /**
     * Obtiene el XML que representa el nodo "Signature" como string.
     *
     * @return string El XML en formato string del nodo "Signature".
     */
    public function getXML(): string
    {
        return $this->getXmlDocument()->getXML();
    }

    /**
     * Obtiene la referencia asociada a la firma electrónica, si existe.
     *
     * @return ?string La referencia URI asociada al nodo "Signature", o `null`
     * si no tiene.
     */
    public function getReference(): ?string
    {
        $uri = $this->data['Signature']['SignedInfo']['Reference']['@attributes']['URI'];

        return $uri ? ltrim($uri, '#') : null;
    }

    /**
     * Establece la referencia URI para la firma electrónica.
     *
     * @param ?string $reference La referencia URI (debe incluir el prefijo "#").
     * @return self La instancia actual para encadenamiento de métodos.
     */
    public function setReference(?string $reference = null): self
    {
        // Asignar URI de la referencia (o vacia si se firma todo el XML).
        $uri = $reference ? ('#' . ltrim($reference, '#')) : '';
        $this->data['Signature']['SignedInfo']['Reference']['@attributes']['URI'] = $uri;

        // Asignar algoritmo de transformación al momento de obtener el C14N.
        $this->data['Signature']['SignedInfo']['Reference']['Transforms']['Transform']['@attributes']['Algorithm'] = $reference
            ? 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315'
            : 'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
        ;

        // Invalidar el documento XML del nodo Signature.
        unset($this->doc);

        // Retornar instancia para encadenamiento.
        return $this;
    }

    /**
     * Obtiene el valor del DigestValue del nodo "Reference".
     *
     * @return ?string El valor del DigestValue o `null` si no está definido.
     */
    public function getDigestValue(): ?string
    {
        $digestValue = $this->data['Signature']['SignedInfo']['Reference']['DigestValue'];

        return $digestValue ?: null;
    }

    /**
     * Establece el valor del DigestValue del nodo "Reference".
     *
     * @param string $digestValue El DigestValue calculado.
     * @return self La instancia actual para encadenamiento de métodos.
     */
    public function setDigestValue(string $digestValue): self
    {
        // Asignar el digest value.
        $this->data['Signature']['SignedInfo']['Reference']['DigestValue'] = $digestValue;

        // Invalidar el documento XML del nodo Signature.
        unset($this->doc);

        // Retornar instancia para encadenamiento.
        return $this;
    }

    /**
     * Obtiene el certificado asociado a la firma electrónica.
     *
     * @return ?Certificate El certificado asociado o `null` si no está asignado.
     */
    private function getCertificate(): ?Certificate
    {
        return $this->certificate ?? null;
    }

    /**
     * Asigna un certificado digital a la instancia actual y actualiza los
     * valores correspondientes en el nodo "KeyInfo" (módulo, exponente y
     * certificado en formato X509).
     *
     * @param Certificate $certificate El certificado digital a asignar.
     * @return self La instancia actual para encadenamiento de métodos.
     */
    private function setCertificate(Certificate $certificate): self
    {
        // Asignar el certificado a la instancia para posteriormente usarlo.
        $this->certificate = $certificate;

        // Agregar módulo, exponente y certificado. Este último contiene la
        // clave pública que permitirá a otros validar la firma del XML.
        $this->data['Signature']['KeyInfo']['KeyValue']['RSAKeyValue']['Modulus'] =
            $certificate->getModulus()
        ;
        $this->data['Signature']['KeyInfo']['KeyValue']['RSAKeyValue']['Exponent'] =
            $certificate->getExponent()
        ;
        $this->data['Signature']['KeyInfo']['X509Data']['X509Certificate'] =
            $certificate->getCertificate(true)
        ;

        // Invalidar el documento XML del nodo Signature.
        unset($this->doc);

        // Retornar instancia para encadenamiento.
        return $this;
    }

    /**
     * Firma el nodo "SignedInfo" del documento XML utilizando un certificado
     * digital. Si no se ha proporcionado previamente un certificado, este
     * puede ser pasado como argumento en la firma.
     *
     * @param ?Certificate $certificate El certificado digital a usar para
     * firmar. Si no se proporciona, se utilizará el ya asignado.
     * @return self La instancia actual para encadenamiento de métodos.
     * @throws SignatureException Si no se ha asignado el DigestValue o el
     * certificado digital.
     */
    public function sign(?Certificate $certificate = null): self
    {
        // Si se pasó un certificado digital se asigna.
        if ($certificate !== null) {
            $this->setCertificate($certificate);
        }

        // Validar que esté asignado el DigestValue.
        if ($this->getDigestValue() === null) {
            throw new SignatureException('No es posible generar la firma del nodo Signature si aun no se asigna el DigestValue.');
        }

        // Validar que esté asignado el certificado digital.
        if ($this->getCertificate() === null) {
            throw new SignatureException('No es posible generar la firma del nodo Signature si aun no se asigna el certificado digital.');
        }

        // Generar el string XML de los datos que se firmarán.
        $xpath = "//*[local-name()='Signature']/*[local-name()='SignedInfo']";
        $signedInfoC14N = $this->getXmlDocument()->C14NWithIsoEncoding($xpath);

        // Generar la firma de los datos, el tag "SignedInfo".
        $signature = SignatureGenerator::sign(
            $signedInfoC14N,
            $this->certificate->getPrivateKey()
        );

        // Asignar la firma calculada.
        return $this->setSignatureValue($signature);
    }

    /**
     * Obtiene la firma calculada para el nodo "SignedInfo".
     *
     * @return string El valor de la firma calculada en base64.
     */
    private function getSignatureValue(): string
    {
        return $this->data['Signature']['SignatureValue'];
    }

    /**
     * Establece el valor de la firma calculada para el nodo "SignedInfo".
     *
     * @param string $signatureValue El valor de la firma en base64.
     * @return self La instancia actual para encadenamiento de métodos.
     */
    private function setSignatureValue(string $signatureValue): self
    {
        // Asignar firma electrónica del nodo "SignedInfo".
        $this->data['Signature']['SignatureValue'] =
            CertificateUtils::wordwrap($signatureValue)
        ;

        // Invalidar el documento XML del nodo Signature.
        unset($this->doc);

        // Retornar instancia para encadenamiento.
        return $this;
    }

    /**
     * Obtiene el certificado X509 asociado al nodo "KeyInfo".
     *
     * @return string El certificado X509 en base64.
     */
    private function getX509Certificate(): string
    {
        return $this->data['Signature']['KeyInfo']['X509Data']['X509Certificate'];
    }

    /**
     * Valida el nodo de la firma electrónica del XML.
     *
     * Valida el DigestValue y la firma de dicho DigestValue.
     *
     * @param XmlDocument|string $xml Documento XML que se desea validar.
     * @return void
     * @throws SignatureException En caso de error de DigestValue o firma.
     */
    public function validate(XmlDocument|string $xml): void
    {
        $this->validateDigestValue($xml);
        $this->validateSignatureValue();
    }

    /**
     * Validar DigestValue de los datos firmados.
     *
     * @param XmlDocument|string $xml Documento XML que se desea validar.
     * @return void
     * @throws SignatureException Si el DigestValue no es válido.
     */
    public function validateDigestValue(XmlDocument|string $xml): void
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

        // Obtener digest que viene en en el XML (en el nodo de la firma).
        $digestValueXml = $this->getDigestValue();

        // Calcular el digest a partir del documento XML.
        $digestValueCalculated = SignatureGenerator::digestXmlReference(
            $doc,
            $this->getReference()
        );

        // Si los digest no coinciden no es válido.
        if ($digestValueXml !== $digestValueCalculated) {
            throw new SignatureException(sprintf(
                'El DigestValue que viene en el XML "%s" para la referencia "%s" no coincide con el valor calculado al validar "%s". Los datos de la referencia podrían haber sido manipulados después de haber sido firmados.',
                $digestValueXml,
                $this->getReference(),
                $digestValueCalculated
            ));
        }
    }

    /**
     * Valida la firma del nodo "SignedInfo" del XML utilizando el certificado
     * X509.
     *
     * @throws SignatureException Si la firma electrónica del XML no es válida.
     */
    public function validateSignatureValue(): void
    {
        // Generar el string XML de los datos que se validará su firma.
        $xpath = "//*[local-name()='Signature']/*[local-name()='SignedInfo']";
        $signedInfoC14N = $this->getXmlDocument()->C14NWithIsoEncoding($xpath);

        // Validar firma electrónica.
        $isValid = SignatureValidator::validate(
            $signedInfoC14N,
            $this->getSignatureValue(),
            $this->getX509Certificate()
        );

        // Si la firma electrónica no es válida se lanza una excepción.
        if (!$isValid) {
            throw new SignatureException(sprintf(
                'La firma electrónica del nodo "SignedInfo" del XML para la referencia "%s" no es válida.',
                $this->getReference()
            ));
        }
    }
}

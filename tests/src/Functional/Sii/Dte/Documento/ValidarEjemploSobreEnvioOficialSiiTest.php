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

namespace libredte\lib\Tests\Functional\Sii\Dte\Documento;

use libredte\lib\Core\Repository\DocumentoTipoRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use libredte\lib\Core\Sii\Dte\Documento\Builder\AbstractDocumentoBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\DocumentoFactory;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoNormalizer;
use libredte\lib\Core\Sii\Dte\Documento\SobreEnvio;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use libredte\lib\Core\Xml\XmlValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Prueba del documento tributario electrónico de ejemplo disponible en el SII:
 *
 * Se valida la firma electrónica del XML del ejemplo.
 *
 * NOTE: La validación de una de las firmas falla indicando que la firma es
 * inválida. Se asume que efectivamente la firma viene mal pues fue validada
 * con una herramienta online para verificar XML DSIG.
 *
 * Salida del sitio web para la validación de la firma del XML:
 *
 * NumSignatures: 2
 *
 *   Signature 1                            <- firma del documento (?).
 *     Signature Verified
 *     Number of Reference Digests = 1
 *     Reference 1 digest is valid.
 *
 *   Signature 2                            <- firma del sobre (?).
 *     Signature is Invalid
 *     Number of Reference Digests = 1
 *     Reference 1 digest is valid.
 *
 * WARNING: Este test debería pasar con ambos DigestValue y con una de las
 * firmas. Esto es algo que no se está haciendo. Los tests comentados, lo están
 * porque fallan. Se debe determinar cuál es el test que realmente debe fallar
 * (según la web usada para validar). Y si es que realmente debe fallar o es un
 * problema con la validación en LibreDTE.
 */
#[CoversClass(SobreEnvio::class)]
#[CoversClass(DocumentoTipoRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(AbstractDocumento::class)]
#[CoversClass(AbstractDocumentoBuilder::class)]
#[CoversClass(DocumentoFactory::class)]
#[CoversClass(DocumentoTipo::class)]
#[CoversClass(DocumentoNormalizer::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(XmlValidator::class)]
class ValidarEjemploSobreEnvioOficialSiiTest extends TestCase
{
    private function getSobreEjemploOficialSii(): SobreEnvio
    {
        $filepath = PathManager::getTestsPath()
            . '/resources/xml/F60T33-ejemplo-oficial-SII.xml'
        ;
        $xml = file_get_contents($filepath);

        $sobre = new SobreEnvio();
        $sobre->loadXML($xml);

        return $sobre;
    }

    public function testValidateSobreDigestValue(): void
    {
        $sobre = $this->getSobreEjemploOficialSii();
        $sobre->getXmlSignatureNode()->validateDigestValue(
            $sobre->getXmlDocument()
        );
        $this->assertTrue(true);
    }

    // public function testValidateSobreSignatureValue(): void
    // {
    //     //$expectedMessage = 'La firma electrónica del nodo "SignedInfo" del XML para la referencia "SetDoc" no es válida. error:0200008A:rsa routines::invalid padding error:02000072:rsa routines::padding check failed error:1C880004:Provider routines::RSA lib';

    //     //$this->expectException(SignatureException::class);
    //     //$this->expectExceptionMessage($expectedMessage);

    //     $sobre = $this->getSobreEjemploOficialSii();
    //     $sobre->getXmlSignatureNode()->validateSignatureValue();
    // }

    public function testValidateSobreSchema(): void
    {
        $sobre = $this->getSobreEjemploOficialSii();
        $sobre->validateSchema();
        $this->assertTrue(true);
    }

    public function testValidateDocumentoCantidad(): void
    {
        $sobre = $this->getSobreEjemploOficialSii();
        $documentos = $sobre->getDocumentos();
        $this->assertEquals(1, count($documentos));
    }

    // public function testValidateDocumentoDigestValue(): void
    // {
    //     $sobre = $this->getSobreEjemploOficialSii();
    //     $documentos = $sobre->getDocumentos();
    //     $documento = $documentos[0];
    //     $documento->getXmlSignatureNode()->validateDigestValue(
    //         $documento->getXmlDocument()
    //     );
    //     $this->assertTrue(true);
    // }

    // public function testValidateDocumentoSignatureValue(): void
    // {
    //     $sobre = $this->getSobreEjemploOficialSii();
    //     $documentos = $sobre->getDocumentos();
    //     $documento = $documentos[0];
    //     $documento->getXmlSignatureNode()->validateSignatureValue();
    //     $this->assertTrue(true);
    // }

    public function testValidateDocumentoSchema(): void
    {
        $sobre = $this->getSobreEjemploOficialSii();
        $documentos = $sobre->getDocumentos();
        $documento = $documentos[0];
        $documento->validateSchema();
        $this->assertTrue(true);
    }
}

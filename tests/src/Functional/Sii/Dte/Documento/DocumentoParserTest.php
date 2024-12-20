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

use libredte\lib\Core\Helper\Arr;
use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Repository\DocumentoTipoRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\SignatureValidator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\Contribuyente\Contribuyente;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\Caf;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\CafFaker;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use libredte\lib\Core\Sii\Dte\Documento\Builder\AbstractDocumentoBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\DocumentoFactory;
use libredte\lib\Core\Sii\Dte\Documento\Builder\FacturaAfectaBuilder;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoNormalizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoSanitizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoValidator;
use libredte\lib\Core\Sii\Dte\Documento\Parser\DocumentoParser;
use libredte\lib\Core\Sii\Dte\Documento\Parser\Sii\JsonParser;
use libredte\lib\Core\Sii\Dte\Documento\Parser\Sii\XmlParser;
use libredte\lib\Core\Sii\Dte\Documento\Parser\Sii\YamlParser;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDecoder;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlEncoder;
use libredte\lib\Core\Xml\XmlUtils;
use libredte\lib\Core\Xml\XmlValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Prueba del parser de datos de entrada para un documento tributario.
 */
#[CoversClass(Arr::class)]
#[CoversClass(Rut::class)]
#[CoversClass(DocumentoTipoRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(SignatureValidator::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Caf::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(AbstractDocumento::class)]
#[CoversClass(AbstractDocumentoBuilder::class)]
#[CoversClass(DocumentoFactory::class)]
#[CoversClass(FacturaAfectaBuilder::class)]
#[CoversClass(DocumentoTipo::class)]
#[CoversClass(DocumentoNormalizer::class)]
#[CoversClass(DocumentoSanitizer::class)]
#[CoversClass(DocumentoValidator::class)]
#[CoversClass(DocumentoParser::class)]
#[CoversClass(JsonParser::class)]
#[CoversClass(XmlParser::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(YamlParser::class)]
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(XmlValidator::class)]
class DocumentoParserTest extends TestCase
{
    protected static $parsersDir = __DIR__ . '/../../../../../resources/parsers';

    /**
     * Entrega los archivos con los casos para el test de los parsers.
     *
     * @return array
     */
    public static function provideInputFiles(): array
    {
        $parsersDir = realpath(static::$parsersDir);
        $inputFiles = [];
        foreach (scandir($parsersDir) as $parser) {
            if ($parser === '.' || $parser === '..') {
                continue;
            }
            $parserDir = $parsersDir . '/' . $parser;
            foreach (scandir($parserDir) as $format) {
                if ($format === '.' || $format === '..') {
                    continue;
                }
                $formatDir = $parserDir . '/' . $format;
                foreach (scandir($formatDir) as $inputFile) {
                    if ($inputFile === '.' || $inputFile === '..') {
                        continue;
                    }
                    $inputFilePath = $formatDir . '/' . $inputFile;
                    $id = sprintf(
                        '%s.%s:%s',
                        $parser,
                        $format,
                        basename($inputFilePath)
                    );
                    $inputFiles[$id] = [
                        $parser . '.' . $format,
                        $inputFilePath
                    ];
                }
            }
        }

        return $inputFiles;
    }

    #[DataProvider('provideInputFiles')]
    public function testDocumentoParser(string $format, string $file): void
    {
        // Parsear datos.
        $data = file_get_contents($file);
        $parser = new DocumentoParser();
        $parsedData = $parser->parse($data, $format);

        // Validar que se haya logrado crear un arreglo con los datos parseados.
        $this->assertIsArray($parsedData);
        $this->assertNotEmpty($parsedData);

        // Crear un documento en base a los datos parseados.
        $factory = new DocumentoFactory();
        $documento = $factory->createFromArray($parsedData);
        $this->assertInstanceOf(AbstractDocumento::class, $documento);

        // Timbrar, firmar el DTE y validar el esquema del XML generado.
        $emisor = $documento->getEmisor();
        $caf = $emisor->getFakeCaf(
            $documento->getTipo()->getCodigo(),
            $documento->getFolio()
        );
        $documento->timbrar($caf);
        $certificate = $emisor->getFakeCertificate();
        $documento->firmar($certificate);
        $documento->validateSignature();
        $documento->validateSchema();
        $this->assertTrue(true);
    }
}

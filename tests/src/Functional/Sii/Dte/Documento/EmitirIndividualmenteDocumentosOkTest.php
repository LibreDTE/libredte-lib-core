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
use libredte\lib\Core\Repository\ImpuestosAdicionalesRepository;
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
use libredte\lib\Core\Sii\Dte\Documento\Builder\BoletaAfectaBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\BoletaExentaBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\DocumentoFactory;
use libredte\lib\Core\Sii\Dte\Documento\Builder\FacturaAfectaBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\FacturaCompraBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\FacturaExentaBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\FacturaExportacionBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\GuiaDespachoBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\NotaCreditoBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\NotaCreditoExportacionBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\NotaDebitoBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\NotaDebitoExportacionBuilder;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoNormalizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoSanitizer;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDecoder;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlEncoder;
use libredte\lib\Core\Xml\XmlUtils;
use libredte\lib\Core\Xml\XmlValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(DocumentoFactory::class)]
#[CoversClass(Arr::class)]
#[CoversClass(Rut::class)]
#[CoversClass(DocumentoTipoRepository::class)]
#[CoversClass(ImpuestosAdicionalesRepository::class)]
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
#[CoversClass(BoletaAfectaBuilder::class)]
#[CoversClass(BoletaExentaBuilder::class)]
#[CoversClass(FacturaAfectaBuilder::class)]
#[CoversClass(FacturaCompraBuilder::class)]
#[CoversClass(FacturaExentaBuilder::class)]
#[CoversClass(FacturaExportacionBuilder::class)]
#[CoversClass(GuiaDespachoBuilder::class)]
#[CoversClass(NotaCreditoBuilder::class)]
#[CoversClass(NotaCreditoExportacionBuilder::class)]
#[CoversClass(NotaDebitoBuilder::class)]
#[CoversClass(NotaDebitoExportacionBuilder::class)]
#[CoversClass(DocumentoTipo::class)]
#[CoversClass(DocumentoNormalizer::class)]
#[CoversClass(DocumentoSanitizer::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(XmlValidator::class)]
class EmitirIndividualmenteDocumentosOkTest extends TestCase
{
    public static function provideDocumentosOk(): array
    {
        // Buscar los archivos con los casos para el test.
        $testsPath = PathManager::getTestsPath();
        $filesPath = $testsPath . '/resources/yaml/documentos_ok/*/*.yaml';
        $files = glob($filesPath);

        // Armar datos de prueba.
        $documentosOk = [];
        foreach ($files as $file) {
            $documentosOk[basename($file)] = [$file];
        }

        // Entregar los datos para las pruebas.
        return $documentosOk;
    }

    private function crearDocumento(string $file): array
    {
        $factory = new DocumentoFactory();

        // Cargar datos del caso de prueba.
        $yaml = file_get_contents($file);
        $data = Yaml::parse($yaml);
        $test = $data['Test'];
        unset($data['Test']);
        $test['caso'] = basename($file);

        // Crear documento tributario del caso.
        $documento = $factory->createFromArray($data);

        // Entregar documento y datos del test.
        return [$documento, $test];
    }

    #[DataProvider('provideDocumentosOk')]
    public function testValidarDocumentoNormalizado(string $file): void
    {
        list($documento, $test) = $this->crearDocumento($file);

        // Obtener todos los datos del documento.
        $actualValues = $documento->getData();

        // Validar los valores esperados de forma recursiva.
        $this->validateExpectedValues(
            $test['ExpectedValues'],
            $actualValues,
            $test['caso']
        );
    }

    /**
     * Valida recursivamente los valores esperados en el arreglo de los datos
     * reales entregados.
     */
    private function validateExpectedValues(
        array $expectedValues,
        array $actualValues,
        string $caso,
        string $parentKey = ''
    ): void {
        foreach ($expectedValues as $key => $expectedValue) {

            // Construir el nombre completo de la clave para los mensajes de
            // error.
            $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

            // Corroborar que el índice esté presente en los datos reales.
            $this->assertArrayHasKey($key, $actualValues, sprintf(
                'En el caso %s no existe el campo %s.',
                $caso,
                $fullKey
            ));

            // Si el valor esperado es un arreglo, llamar recursivamente.
            if (is_array($expectedValue)) {
                $this->validateExpectedValues(
                    $expectedValue,
                    $actualValues[$key],
                    $caso,
                    $fullKey
                );
            }

            // Si el valor esperado no es un arreglo, se compara directamente.
            else {
                // Si el valor actual es un flotante, se revisa si se debe
                // convertir a un entero para usar assertSame() con el tipo de
                // datos correcto y que no falle porque se compara entero con
                // float.
                if (
                    is_float($actualValues[$key])
                    && floor($actualValues[$key]) == $actualValues[$key]
                ) {
                    $actualValues[$key] = (int) $actualValues[$key];
                }

                // Realizar validación del valor.
                $this->assertSame(
                    $expectedValue,
                    $actualValues[$key],
                    sprintf(
                        'En el caso %s el valor %s para %s no cuadra con el valor esperado %s.',
                        $caso,
                        $actualValues[$key],
                        $fullKey,
                        $expectedValue
                    )
                );
            }
        }
    }

    #[DataProvider('provideDocumentosOk')]
    public function testValidarXmlNormalizado(string $file): void
    {
        list($documento, $test) = $this->crearDocumento($file);
        $needle = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $xml = $documento->saveXml();
        $this->assertStringContainsString($needle, $xml);
    }

    #[DataProvider('provideDocumentosOk')]
    public function testTimbrarDocumento(string $file): void
    {
        list($documento, $test) = $this->crearDocumento($file);
        $emisor = $documento->getEmisor();
        $caf = $emisor->getFakeCaf(
            $documento->getTipo()->getCodigo(),
            $documento->getFolio()
        );

        $documento->timbrar($caf);

        $this->assertTrue(true); // TODO: validar timbre con CafFaker (?).
    }

    #[DataProvider('provideDocumentosOk')]
    public function testFirmarDocumento(string $file): void
    {
        list($documento, $test) = $this->crearDocumento($file);
        $this->assertInstanceOf(AbstractDocumento::class, $documento);

        $emisor = $documento->getEmisor();
        $caf = $emisor->getFakeCaf(
            $documento->getTipo()->getCodigo(),
            $documento->getFolio()
        );
        $documento->timbrar($caf);
        $certificate = $emisor->getFakeCertificate();

        $xml = $documento->firmar($certificate);

        $documento->validateSignature();
        $documento->validateSchema();

        $this->assertTrue(true);
    }

    #[DataProvider('provideDocumentosOk')]
    public function testCrearPdf(string $file): void
    {
        list($documento, $test) = $this->crearDocumento($file);

        $this->markTestIncomplete('Test no implementado.');
    }
}

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
use libredte\lib\Core\Sii\Dte\Documento\Normalization\BoletasNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DescuentosRecargosNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DetalleNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoNormalizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoSanitizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\ExportacionNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\ImpuestoAdicionalRetencionNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\IvaMntTotalNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\TransporteNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\UtilsTrait;
use libredte\lib\Core\Sii\Dte\Documento\SobreEnvio;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use libredte\lib\Core\Xml\XmlValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
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
#[CoversTrait(DescuentosRecargosNormalizationTrait::class)]
#[CoversTrait(DetalleNormalizationTrait::class)]
#[CoversClass(DocumentoNormalizer::class)]
#[CoversClass(DocumentoSanitizer::class)]
#[CoversTrait(BoletasNormalizationTrait::class)]
#[CoversTrait(ExportacionNormalizationTrait::class)]
#[CoversTrait(ImpuestoAdicionalRetencionNormalizationTrait::class)]
#[CoversTrait(IvaMntTotalNormalizationTrait::class)]
#[CoversTrait(TransporteNormalizationTrait::class)]
#[CoversTrait(UtilsTrait::class)]
#[CoversClass(SobreEnvio::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(XmlValidator::class)]
class CrearSobreEnvioDocumentosOkTest extends TestCase
{
    private function getDocumentosDteFiles()
    {
        // Buscar los archivos con los casos para el test.
        $testsPath = PathManager::getTestsPath();
        $filesPath = $testsPath . '/resources/yaml/documentos_ok/*/*.yaml';
        $files = glob($filesPath);

        // Omitir boletas.
        $dte39 = 'yaml/documentos_ok/039_';
        $dte41 = 'yaml/documentos_ok/041_';

        // Armar datos de prueba.
        $documentos = [];
        foreach ($files as $file) {
            if (str_contains($file, $dte39) || str_contains($file, $dte41)) {
                continue;
            }

            $documentos[] = $file;
        }

        // Entregar los datos para las pruebas.
        return $documentos;
    }

    private function getDocumentosBoletasFiles()
    {
        // Buscar los archivos con los casos para el test.
        $testsPath = PathManager::getTestsPath();
        $filesPath = $testsPath . '/resources/yaml/documentos_ok/*/*.yaml';
        $files = glob($filesPath);

        // Incluir boletas.
        $dte39 = 'yaml/documentos_ok/039_';
        $dte41 = 'yaml/documentos_ok/041_';

        // Armar datos de prueba.
        $documentos = [];
        foreach ($files as $file) {
            if (!str_contains($file, $dte39) && !str_contains($file, $dte41)) {
                continue;
            }

            $documentos[] = $file;
        }

        // Entregar los datos para las pruebas.
        return $documentos;
    }

    private function crearDocumento(string $file, $folio): AbstractDocumento
    {
        $factory = new DocumentoFactory();

        // Cargar datos del caso de prueba.
        $yaml = file_get_contents($file);
        $data = Yaml::parse($yaml);
        $test = $data['Test'];
        unset($data['Test']);
        $test['caso'] = basename($file);

        // Ajustar RUT emisor y folio del documento del caso de prueba.
        $data['Encabezado']['Emisor']['RUTEmisor'] = getenv('LIBREDTE_COMPANY');
        $data['Encabezado']['IdDoc']['Folio'] = $folio;

        // Crear documento tributario del caso.
        $documento = $factory->createFromArray($data);

        // Entregar documento.
        return $documento;
    }

    public function testCrearSobreEnvioDte()
    {
        $files = $this->getDocumentosDteFiles();
        $emisor = null;
        $certificate = null;

        $sobre = new SobreEnvio();
        $folio = 1;

        foreach ($files as $file) {
            $documento = $this->crearDocumento($file, $folio++);

            if (!isset($emisor)) {
                $emisor = $documento->getEmisor();
                $certificate = $emisor->getFakeCertificate();
            }

            $caf = $emisor->getFakeCaf(
                $documento->getTipo()->getCodigo(),
                $documento->getFolio()
            );

            $documento->timbrar($caf);
            $documento->firmar($certificate);

            $documento->validateSignature();
            $documento->validateSchema();

            $sobre->agregar($documento);
        }

        $sobre->setCaratula([
            'FchResol' => getenv('LIBREDTE_ENV_TEST_AUTH_DATE'),
            'NroResol' => 0,
            'RutEnvia' => $certificate->getID(),
        ]);

        $xml = $sobre->firmar($certificate);

        $sobre->validateSignature(); // Validar firma con excepciones.
        $sobre->validateSchema(); // El esquema se valida con excepciones.

        $this->assertIsString($xml); // Todo OK.
    }

    public function testCrearSobreEnvioBoleta()
    {
        $files = $this->getDocumentosBoletasFiles();
        $emisor = null;
        $certificate = null;

        $sobre = new SobreEnvio();
        $folio = 1;

        foreach ($files as $file) {
            $documento = $this->crearDocumento($file, $folio++);

            if (!isset($emisor)) {
                $emisor = $documento->getEmisor();
                $certificate = $emisor->getFakeCertificate();
            }

            $caf = $emisor->getFakeCaf(
                $documento->getTipo()->getCodigo(),
                $documento->getFolio()
            );

            $documento->timbrar($caf);
            $documento->firmar($certificate);

            $documento->validateSignature();
            $documento->validateSchema();

            $sobre->agregar($documento);
        }

        $sobre->setCaratula([
            'FchResol' => getenv('LIBREDTE_ENV_TEST_AUTH_DATE'),
            'NroResol' => 0,
            'RutEnvia' => $certificate->getID(),
        ]);

        $xml = $sobre->firmar($certificate);

        $sobre->validateSignature(); // Validar firma con excepciones.
        $sobre->validateSchema(); // El esquema se valida con excepciones.

        $this->assertIsString($xml); // Todo OK.
    }
}

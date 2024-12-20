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
use libredte\lib\Core\Signature\CertificateException;
use libredte\lib\Core\Signature\CertificateLoader;
use libredte\lib\Core\Signature\CertificateUtils;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\SignatureValidator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\Contribuyente\Contribuyente;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\Caf;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use libredte\lib\Core\Sii\Dte\Documento\Builder\AbstractDocumentoBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\DocumentoFactory;
use libredte\lib\Core\Sii\Dte\Documento\Builder\FacturaAfectaBuilder;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoNormalizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoSanitizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoValidator;
use libredte\lib\Core\Sii\Dte\Documento\SobreEnvio;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlUtils;
use libredte\lib\Core\Xml\XmlValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(SobreEnvio::class)]
#[CoversClass(Arr::class)]
#[CoversClass(Rut::class)]
#[CoversClass(DocumentoTipoRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(CertificateUtils::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(SignatureValidator::class)]
#[CoversClass(XmlSignatureNode::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Caf::class)]
#[CoversClass(AbstractDocumento::class)]
#[CoversClass(AbstractDocumentoBuilder::class)]
#[CoversClass(DocumentoFactory::class)]
#[CoversClass(FacturaAfectaBuilder::class)]
#[CoversClass(DocumentoTipo::class)]
#[CoversClass(DocumentoNormalizer::class)]
#[CoversClass(DocumentoSanitizer::class)]
#[CoversClass(DocumentoValidator::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlUtils::class)]
#[CoversClass(XmlValidator::class)]
class CrearSobreEnvioAmbienteCertificacionTest extends TestCase
{
    public function testDteCertificacionSubirSii(): void
    {
        $factory = new DocumentoFactory();

        // Cargar archivo CAF.
        $cafFile = PathManager::getTestsPath() .
            '/resources/caf/33.xml'
        ;
        if (!file_exists($cafFile)) {
            $this->markTestSkipped(sprintf('Archivo %s no existe.', $cafFile));
        }
        $cafXml = file_get_contents($cafFile);
        $caf = new Caf();
        $caf->loadXML($cafXml);

        // Cargar certificado digital.
        try {
            $certificate = CertificateLoader::createFromFile(
                getenv('LIBREDTE_CERTIFICATE_FILE'),
                getenv('LIBREDTE_CERTIFICATE_PASS'),
            );
        } catch (CertificateException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        // Buscar archivo del caso.
        $testsPath = PathManager::getTestsPath();
        $filesPath = $testsPath . '/resources/yaml/documentos_ok/033_*/033_001_*.yaml';
        $file = glob($filesPath)[0];

        // Cargar datos del caso de prueba.
        $yaml = file_get_contents($file);
        $data = Yaml::parse($yaml);
        unset($data['Test']);

        // Ajustar folio con el valor pasado.
        $data['Encabezado']['IdDoc']['Folio'] = $caf->getFolioDesde();

        // Sanitizar el arreglo quitando todo lo que no sea ASCII.
        // array_walk_recursive($data, function (&$value) {
        //     if (is_string($value)) {
        //         // Reemplazar caracteres no ASCII con "?".
        //         $value = preg_replace('/[^\x20-\x7E]/', '?', $value);
        //     }
        // });

        // Crear documento tributario con los datos del caso.
        $documento = $factory->createFromArray($data);

        // Timbrar el documento.
        $documento->timbrar($caf);

        // Firmar el documento y validar su firma.
        $documento->firmar($certificate);
        $documento->validateSignature(); // Validar firma con excepciones.
        $documento->validateSchema(); // Validar esquema con excepciones.

        // Crear sobre, agregar documento y asignar carátula.
        $sobre = new SobreEnvio();
        $sobre->agregar($documento);
        $sobre->setCaratula([
            'FchResol' => getenv('LIBREDTE_ENV_TEST_AUTH_DATE'),
            'NroResol' => 0,
            'RutEnvia' => $certificate->getID(),
        ]);

        // Firmar y validar firma.
        $xml = $sobre->firmar($certificate);
        $sobre->validateSignature(); // Validar firma con excepciones.

        // Validar el esquema del XML.
        $sobre->validateSchema(); // Validar esquema con excepciones.

        // Guardar el XML.
        file_put_contents($file . '.xml', $xml);

        // Todo OK.
        $this->assertTrue(true);
    }
}

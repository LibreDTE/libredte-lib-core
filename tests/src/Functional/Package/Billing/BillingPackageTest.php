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

namespace libredte\lib\Tests\Functional\Package\Billing;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataHandler;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\LoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\XmlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\RendererWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\IdentifierComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoDocumentoFactory::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(XmlParserStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafBag::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(LoaderWorker::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(TemplateDataHandler::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class BillingPackageTest extends TestCase
{
    public function testBillingPackageBillerBill(): void
    {
        $RUTEmisor = '76192083-9';
        $TipoDTE = 33;
        $Folio = 1;

        // Datos para la creación del documento tributario.
        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => $TipoDTE,
                    'Folio' => $Folio,
                ],
                'Emisor' => [
                    'RUTEmisor' => $RUTEmisor,
                    'RznSoc' => 'SASCO SpA',
                    'GiroEmis' => 'Tecnología, Informática y Telecomunicaciones',
                    'Acteco' => 726000,
                    'DirOrigen' => 'Santiago',
                    'CmnaOrigen' => 'Santiago',
                ],
                'Receptor' => [
                    'RUTRecep' => '60803000-K',
                    'RznSocRecep' => 'Servicio de Impuestos Internos',
                    'GiroRecep' => 'Gobierno',
                    'DirRecep' => 'Santiago',
                    'CmnaRecep' => 'Santiago',
                ],
            ],
            'Detalle' => [
                'NmbItem' => 'Servicio Plus de LibreDTE',
                'QtyItem' => 12,
                'PrcItem' => 40000,
            ],
        ];

        // Generar un CAF falso.
        $cafFaker = Application::getInstance()
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
        ;
        $cafBag = $cafFaker->create(new Emisor($RUTEmisor), $TipoDTE, $Folio);

        // Generar un certificado falso.
        $certificateFaker = Application::getInstance()
            ->getPrimePackage()
            ->getCertificateComponent()
            ->getFakerWorker()
        ;
        $certificate = $certificateFaker->create($RUTEmisor);

        // Obtener el biller.
        $biller = Application::getInstance()
            ->getBillingPackage()
            ->getDocumentComponent()
        ;

        // Facturar con los datos, CAF y certificado.
        $bag = $biller->bill($data, $cafBag->getCaf(), $certificate);

        // Corroborar el monto total del documento.
        $document = $bag->getDocument();
        $this->assertSame(571200, $document->getMontoTotal());

        // Validar el esquema del documento generado.
        // Acá se valida el XML pasando la bolsa.
        $biller->getValidatorWorker()->validateSchema($bag);

        // Validar la firma electrónica del documento generado.
        // Acá se valida la firma pasando la bolsa.
        $biller->getValidatorWorker()->validateSignature($bag);

        // Cargar el XML con el loader y corroborar el total.
        $xml = $document->saveXml();
        $newBag = $biller->getLoaderWorker()->loadXml($xml);
        $this->assertSame(571200, $newBag->getDocument()->getMontoTotal());

        // Validar el esquema del documento cargado.
        // Acá se valida el XML pasando el string XML.
        // Importante: no es posible validar un XML del LoaderWorker. Pues este
        // quita el tag Signature dañando el esquema del XML.
        $biller->getValidatorWorker()->validateSchema($xml);

        // Validar la firma electrónica del documento cargado.
        // Acá se valida la firma pasando el string XML.
        // Importante: no es posible validar un XML del LoaderWorker. Pues este
        // quita el tag Signature.
        $biller->getValidatorWorker()->validateSignature($xml);

        // Renderizar el documento cargado en la nueva bolsa.
        $renderer = $biller->getRendererWorker();
        $newBag->getOptions()->set('renderer.format', 'html');
        $renderedData = $renderer->render($newBag);
        $this->assertNotEmpty($renderedData);
        $this->assertIsString($renderedData);
    }
}

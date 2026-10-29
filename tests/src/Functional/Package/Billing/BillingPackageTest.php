<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Tests\Functional\Package\Billing;

use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
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
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataFormatter;
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
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFakerWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafLoaderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderedDocument;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderResult;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\FormatterHandlerProvider;
use libredte\lib\Core\Provider\TemplatePathProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
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
#[CoversClass(Receptor::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(Caf::class)]
#[CoversClass(IdentifierComponent::class)]
#[CoversClass(CafFaker::class)]
#[CoversClass(CafFakerWorker::class)]
#[CoversClass(CafLoaderWorker::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(LoaderWorker::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(RenderResult::class)]
#[CoversClass(RenderedDocument::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(TemplateDataFormatter::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(OperacionDocumento::class)]
#[UsesClass(FormatterHandlerProvider::class)]
#[UsesClass(TemplatePathProvider::class)]
class BillingPackageTest extends TestCase
{
    public function testBillingPackageBillerBill(): void
    {
        $RUTEmisor = '76192083-9';
        $TipoDTE = 33;
        $Folio = 1;

        // Data for creating the tax document.
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

        // Generate a fake CAF.
        $cafFaker = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getIdentifierComponent()
            ->getCafFakerWorker()
        ;
        $caf = $cafFaker->create(new Emisor($RUTEmisor), $TipoDTE, $Folio);

        // Generate a fake certificate.
        $certificateFaker = new CertificateFaker(new CertificateLoader());
        $certificate = $certificateFaker->createFake(id: $RUTEmisor);

        // Get the biller.
        $biller = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
        ;

        // Bill with the data, CAF, and certificate.
        $bag = $biller->bill($data, $caf, $certificate);

        // Assert the document total amount.
        $document = $bag->getDocument();
        $this->assertSame(571200, $document->getMontoTotal());

        // Validate the generated document schema.
        // Here the XML is validated by passing the bag.
        $biller->getValidatorWorker()->validateSchema($bag);

        // Validate the electronic signature of the generated document.
        // Here the signature is validated by passing the bag.
        $result = $biller->getValidatorWorker()->validateSignature($bag);
        $this->assertTrue($result->isValid());

        // Load the XML with the loader and assert the total.
        $xml = $document->saveXml();
        $newBag = $biller->getLoaderWorker()->loadXml($xml);
        $this->assertSame(571200, $newBag->getDocument()->getMontoTotal());

        // Validate the loaded document schema.
        // Here the XML is validated by passing the XML string.
        // Important: validating XML from LoaderWorker is not possible because it
        // removes the Signature tag, breaking the XML schema.
        $biller->getValidatorWorker()->validateSchema($xml);

        // Validate the electronic signature of the loaded document.
        // Here the signature is validated by passing the XML string.
        // Important: validating XML from LoaderWorker is not possible because it
        // removes the Signature tag.
        $result = $biller->getValidatorWorker()->validateSignature($xml);
        $this->assertTrue($result->isValid());

        // Render the loaded document in the new bag.
        $renderer = $biller->getRendererWorker();
        $newBag->getOptions()->set('renderer.format', 'html');
        $renderedData = (string) $renderer->render($newBag);
        $this->assertNotEmpty($renderedData);
    }
}

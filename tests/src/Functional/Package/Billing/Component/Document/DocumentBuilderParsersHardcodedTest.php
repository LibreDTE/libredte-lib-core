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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Document;

use Derafu\Backbone\Exception\StrategyNotFoundException;
use Derafu\Config\Options;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\XmlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\YamlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(XmlParserStrategy::class)]
#[CoversClass(YamlParserStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class DocumentBuilderParsersHardcodedTest extends TestCase
{
    private BuilderWorkerInterface $builder;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->builder = $app
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBuilderWorker()
        ;
    }

    public function testBuildFromArrayWithoutTipoDte(): void
    {
        $this->expectException(DocumentException::class);
        $this->expectExceptionMessage('Falta indicar el tipo de documento (TipoDTE) en los datos del DTE.');

        $data = [];

        $bag = new DocumentBag($data);

        $document = $this->builder->build($bag);
    }

    public function testBuildFromArrayWithEmptyTipoDte(): void
    {
        $this->expectException(DocumentException::class);
        $this->expectExceptionMessage('Falta indicar el tipo de documento (TipoDTE) en los datos del DTE.');

        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => '',
                ],
            ],
        ];

        $bag = new DocumentBag($data);

        $document = $this->builder->build($bag);
    }

    public function testBuildFromArrayWithInvalidIntTipoDte(): void
    {
        $this->expectException(StrategyNotFoundException::class);
        $this->expectExceptionMessage('Strategy documento_35 not found in service builder (billing.document.builder).');

        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 35,
                ],
            ],
        ];

        $bag = new DocumentBag($data);

        $document = $this->builder->build($bag);
    }

    public function testBuildFromArrayWithInvalidStringTipoDte(): void
    {
        $this->expectException(StrategyNotFoundException::class);
        $this->expectExceptionMessage('Strategy factura not found in service builder (billing.document.builder).');

        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 'factura',
                ],
            ],
        ];

        $bag = new DocumentBag($data);

        $document = $this->builder->build($bag);
    }

    public function testBuildFromArrayOk(): void
    {
        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 33,
                ],
                'Emisor' => [
                    'RUTEmisor' => '76192083-9',
                    'RznSoc' => 'SASCO SpA',
                    'GiroEmis' => 'Tecnología, Informática y Telecomunicaciones',
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

        $bag = new DocumentBag($data);

        $this->builder->build($bag);

        $this->assertSame(571200, $bag->getDocument()->getMontoTotal());
    }

    public function testBuildFromXmlOk(): void
    {
        $data = '<DTE><Documento><Encabezado><IdDoc><TipoDTE>33</TipoDTE></IdDoc><Emisor><RUTEmisor>76192083-9</RUTEmisor><RznSoc>SASCO SpA</RznSoc><GiroEmis>Tecnología, Informática y Telecomunicaciones</GiroEmis><DirOrigen>Santiago</DirOrigen><CmnaOrigen>Santiago</CmnaOrigen></Emisor><Receptor><RUTRecep>60803000-K</RUTRecep><RznSocRecep>Servicio de Impuestos Internos</RznSocRecep><GiroRecep>Gobierno</GiroRecep><DirRecep>Santiago</DirRecep><CmnaRecep>Santiago</CmnaRecep></Receptor></Encabezado><Detalle><NmbItem>Servicio Plus de LibreDTE</NmbItem><QtyItem>12</QtyItem><PrcItem>40000</PrcItem></Detalle></Documento></DTE>';

        $bag = new DocumentBag(
            inputData: $data,
            options: new Options(['parser' => ['strategy' => 'default.xml']])
        );

        $this->builder->build($bag);

        $this->assertSame(571200, $bag->getDocument()->getMontoTotal());
    }

    public function testBuildFromYamlOk(): void
    {
        $data = <<<YAML
        Encabezado:
            IdDoc:
                TipoDTE: 33
            Emisor:
                RUTEmisor: '76192083-9'
                RznSoc: 'SASCO SpA'
                GiroEmis: 'Tecnología, Informática y Telecomunicaciones'
                DirOrigen: 'Santiago'
                CmnaOrigen: 'Santiago'
            Receptor:
                RUTRecep: '60803000-K'
                RznSocRecep: 'Servicio de Impuestos Internos'
                GiroRecep: 'Gobierno'
                DirRecep: 'Santiago'
                CmnaRecep: 'Santiago'
        Detalle:
            NmbItem: 'Servicio Plus de LibreDTE'
            QtyItem: 12
            PrcItem: 40000
        YAML;

        $bag = new DocumentBag(
            inputData: $data,
            options: new Options(['parser' => ['strategy' => 'default.yaml']])
        );

        $this->builder->build($bag);

        $this->assertSame(571200, $bag->getDocument()->getMontoTotal());
    }

    public function testBuildFromJsonOk(): void
    {
        $data = '{
            "Encabezado": {
                "IdDoc": {
                    "TipoDTE": 33
                },
                "Emisor": {
                    "RUTEmisor": "76192083-9",
                    "RznSoc": "SASCO SpA",
                    "GiroEmis": "Tecnología, Informática y Telecomunicaciones",
                    "DirOrigen": "Santiago",
                    "CmnaOrigen": "Santiago"
                },
                "Receptor": {
                    "RUTRecep": "60803000-K",
                    "RznSocRecep": "Servicio de Impuestos Internos",
                    "GiroRecep": "Gobierno",
                    "DirRecep": "Santiago",
                    "CmnaRecep": "Santiago"
                }
            },
            "Detalle": {
                "NmbItem": "Servicio Plus de LibreDTE",
                "QtyItem": 12,
                "PrcItem": 40000
            }
        }';

        $bag = new DocumentBag($data);

        $this->builder->build($bag);

        $this->assertSame(571200, $bag->getDocument()->getMontoTotal());
    }
}

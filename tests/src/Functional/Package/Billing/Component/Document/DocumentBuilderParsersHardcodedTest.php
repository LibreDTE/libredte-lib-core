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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Document;

use Derafu\Lib\Core\Foundation\Exception\StrategyException;
use Derafu\Lib\Core\Support\Store\DataContainer;
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
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
class DocumentBuilderParsersHardcodedTest extends TestCase
{
    private BuilderWorkerInterface $builder;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->builder = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBuilderWorker()
        ;
    }

    public function testDocumentoFactoryFromArrayWithoutTipoDTE(): void
    {
        $this->expectException(DocumentException::class);
        $this->expectExceptionMessage('Falta indicar el tipo de documento (TipoDTE) en los datos del DTE.');

        $data = [];

        $bag = new DocumentBag($data);

        $document = $this->builder->build($bag);
    }

    public function testDocumentoFactoryFromArrayEmptyTipoDTE(): void
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

    public function testDocumentoFactoryFromArrayWrongIntTipoDTE(): void
    {
        $this->expectException(StrategyException::class);
        $this->expectExceptionMessage('No se encontró la estrategia documento_35 en el worker Billing Document Builder (billing.document.builder).');

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

    public function testDocumentoFactoryFromArrayWrongStringTipoDTE(): void
    {
        $this->expectException(StrategyException::class);
        $this->expectExceptionMessage('No se encontró la estrategia factura en el worker Billing Document Builder (billing.document.builder).');

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

    public function testDocumentoFactoryFromArrayOk(): void
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

        $document = $this->builder->build($bag);

        $this->assertSame(571200, $document->getMontoTotal());
    }

    public function testDocumentoFactoryFromXmlOk(): void
    {
        $data = '<DTE><Documento><Encabezado><IdDoc><TipoDTE>33</TipoDTE></IdDoc><Emisor><RUTEmisor>76192083-9</RUTEmisor><RznSoc>SASCO SpA</RznSoc><GiroEmis>Tecnología, Informática y Telecomunicaciones</GiroEmis><DirOrigen>Santiago</DirOrigen><CmnaOrigen>Santiago</CmnaOrigen></Emisor><Receptor><RUTRecep>60803000-K</RUTRecep><RznSocRecep>Servicio de Impuestos Internos</RznSocRecep><GiroRecep>Gobierno</GiroRecep><DirRecep>Santiago</DirRecep><CmnaRecep>Santiago</CmnaRecep></Receptor></Encabezado><Detalle><NmbItem>Servicio Plus de LibreDTE</NmbItem><QtyItem>12</QtyItem><PrcItem>40000</PrcItem></Detalle></Documento></DTE>';

        $bag = new DocumentBag(
            inputData: $data,
            options: new DataContainer(['parser' => ['strategy' => 'default.xml']])
        );

        $document = $this->builder->build($bag);

        $this->assertSame(571200, $document->getMontoTotal());
    }

    public function testDocumentoFactoryFromYamlOk(): void
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
            options: new DataContainer(['parser' => ['strategy' => 'default.yaml']])
        );

        $document = $this->builder->build($bag);

        $this->assertSame(571200, $document->getMontoTotal());
    }

    public function testDocumentoFactoryFromJsonOk(): void
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

        $document = $this->builder->build($bag);

        $this->assertSame(571200, $document->getMontoTotal());
    }
}

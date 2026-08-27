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

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CategoriaDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\OperacionDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoPresentacion;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;
use libredte\lib\Core\Package\Billing\Component\Document\Factory\TipoDocumentoFactory;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataFormatter;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Support\RenderedDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Support\RenderResult;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Builder\Strategy\BoletaAfectaBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Builder\Strategy\FacturaAfectaBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeBoletaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\BoletaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeBoletasTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Renderer\Strategy\Template\EstandarRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\RendererWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\BoletaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\BoletaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Receptor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;

/**
 * Pruebas de las presentaciones (tributaria/cedible) y copias múltiples del
 * `RendererWorker`, incluyendo su integración real con la plantilla estándar
 * (no un doble/mock de la estrategia).
 */
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(TipoDocumentoFactory::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(FacturaAfectaBuilderStrategy::class)]
#[CoversClass(BoletaAfectaBuilderStrategy::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(BoletaAfectaNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(BoletaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(BoletaAfectaValidatorStrategy::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(Receptor::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(EstandarRendererStrategy::class)]
#[CoversClass(RenderResult::class)]
#[CoversClass(RenderedDocument::class)]
#[CoversClass(TipoPresentacion::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(NormalizeBoletaAfectaJob::class)]
#[CoversTrait(NormalizeBoletasTrait::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(TemplateDataFormatter::class)]
#[CoversClass(CategoriaDocumento::class)]
#[CoversClass(OperacionDocumento::class)]
class RendererPresentacionesTest extends TestCase
{
    private DocumentComponentInterface $biller;

    protected function setUp(): void
    {
        $this->biller = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
        ;
    }

    /**
     * Crea (en borrador, sin CAF ni certificado, no relevante para estas
     * pruebas) una factura afecta (33): tipo de documento que sí admite
     * acuse de recibo.
     */
    private function facturaAfecta(): DocumentBagInterface
    {
        return $this->biller->bill([
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 33,
                    'Folio' => 1,
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
                'QtyItem' => 1,
                'PrcItem' => 40000,
            ],
        ]);
    }

    /**
     * Crea (en borrador) una boleta afecta (39): tipo de documento que NO
     * admite acuse de recibo (`TipoDocumento::requiresAcuseRecibo()`).
     */
    private function boletaAfecta(): DocumentBagInterface
    {
        return $this->biller->bill([
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 39,
                    'Folio' => 1,
                ],
                'Emisor' => [
                    'RUTEmisor' => '76192083-9',
                    'RznSocEmisor' => 'SASCO SpA',
                    'GiroEmisor' => 'Tecnología, Informática y Telecomunicaciones',
                    'DirOrigen' => 'DBG',
                    'CmnaOrigen' => 'Santa Cruz',
                ],
                'Receptor' => [
                    'RUTRecep' => '66666666-6',
                    'RznSocRecep' => 'Sin RUT',
                    'DirRecep' => 'Santiago',
                    'CmnaRecep' => 'Santiago',
                ],
            ],
            'Detalle' => [
                'NmbItem' => 'Producto',
                'QtyItem' => 1,
                'PrcItem' => 1190,
            ],
        ]);
    }

    public function testDefaultRenderingIsSingleTributariaWithoutLabelSuffix(): void
    {
        $bag = $this->facturaAfecta();
        $bag->getOptions()->set('renderer.format', 'html');

        $result = $this->biller->getRendererWorker()->render($bag);

        $this->assertCount(1, $result->getRenderings());
        $this->assertTrue($result->hasRendering('tributaria'));
        $this->assertFalse($result->hasRendering('cedible'));

        $rendering = $result->getRendering('tributaria');
        $this->assertSame(1, $rendering->getCopies());
        $this->assertSame($bag->getId() . '.html', $rendering->getFilename());
        $this->assertStringNotContainsString('CEDIBLE', $rendering->getContent());

        // __toString() funciona porque hay exactamente 1 archivo.
        $this->assertSame($rendering->getContent(), (string) $result);
    }

    public function testCedibleAndTributariaSeGeneranAmbasParaFacturaConNombresDistintos(): void
    {
        $bag = $this->facturaAfecta();
        $bag->getOptions()->set('renderer.format', 'html');
        $bag->getOptions()->set('renderer.renderings', [
            'tributaria' => 1,
            'cedible' => 1,
        ]);

        $result = $this->biller->getRendererWorker()->render($bag);

        $this->assertCount(2, $result->getRenderings());
        $this->assertTrue($result->hasRendering('tributaria'));
        $this->assertTrue($result->hasRendering('cedible'));

        $tributaria = $result->getRendering('tributaria');
        $cedible = $result->getRendering('cedible');

        $this->assertSame($bag->getId() . '_tributaria.html', $tributaria->getFilename());
        $this->assertSame($bag->getId() . '_cedible.html', $cedible->getFilename());

        $this->assertStringNotContainsString('CEDIBLE', $tributaria->getContent());
        $this->assertStringContainsString('CEDIBLE', $cedible->getContent());
        $this->assertStringContainsString('Acuse de recibo', $cedible->getContent());

        // Con 2 archivos, __toString() ya no es ambiguo-resoluble: falla.
        $this->expectException(RendererException::class);
        (string) $result;
    }

    public function testMultipleCopiesSeAlmacenanUnaVezYSoloSeExpandenEnToArray(): void
    {
        $bag = $this->facturaAfecta();
        $bag->getOptions()->set('renderer.format', 'html');
        $bag->getOptions()->set('renderer.renderings', [
            'tributaria' => 3,
        ]);

        $result = $this->biller->getRendererWorker()->render($bag);

        // Una sola presentación (label), sin duplicar en memoria.
        $this->assertCount(1, $result->getRenderings());
        $rendering = $result->getRendering('tributaria');
        $this->assertSame(3, $rendering->getCopies());

        // __toString() sigue funcionando: hay 1 archivo único (3 copias del mismo).
        $this->assertSame($rendering->getContent(), (string) $result);

        // La expansión (con número de copia y nombre ajustado) solo ocurre acá.
        $array = $result->toArray();
        $this->assertCount(3, $array['renderings']);

        foreach ($array['renderings'] as $i => $entry) {
            $copyNumber = $i + 1;
            $this->assertSame($copyNumber, $entry['copyNumber']);
            $this->assertSame($rendering->getContent(), $entry['content']);
            $this->assertSame(
                $bag->getId() . '_' . $copyNumber . '.html',
                $entry['filename']
            );
        }
    }

    public function testRenderingDesconocidoLanzaExcepcion(): void
    {
        $bag = $this->facturaAfecta();
        $bag->getOptions()->set('renderer.renderings', [
            'presentacion_inexistente' => 1,
        ]);

        $this->expectException(RendererException::class);

        $this->biller->getRendererWorker()->render($bag);
    }

    public function testCedibleSeOmiteEnSilencioParaBoletaSiSePideJuntoATributaria(): void
    {
        $bag = $this->boletaAfecta();
        $bag->getOptions()->set('renderer.format', 'html');
        $bag->getOptions()->set('renderer.renderings', [
            'tributaria' => 1,
            'cedible' => 1,
        ]);

        $result = $this->biller->getRendererWorker()->render($bag);

        $this->assertCount(1, $result->getRenderings());
        $this->assertTrue($result->hasRendering('tributaria'));
        $this->assertFalse($result->hasRendering('cedible'));
    }

    public function testRenderLanzaExcepcionSiSoloSePideCedibleParaBoletaYQuedaVacio(): void
    {
        $bag = $this->boletaAfecta();
        $bag->getOptions()->set('renderer.renderings', [
            'cedible' => 1,
        ]);

        $this->expectException(RendererException::class);

        $this->biller->getRendererWorker()->render($bag);
    }
}

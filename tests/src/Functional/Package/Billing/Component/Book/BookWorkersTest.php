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

namespace libredte\lib\Tests\Functional\Package\Billing\Component\Book;

use Derafu\Array\ArrayReader;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\BookComponent;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\LoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use libredte\lib\Core\Package\Billing\Component\Book\Support\BookBag;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\AbstractLibroComprasVentasBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Builder\Strategy\LibroVentas\BuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\Loader\Strategy\AbstractLibroComprasVentasArrayLoaderStrategy;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\LoaderWorker;
use libredte\lib\Core\Package\Billing\Component\Book\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TipoLibro::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(AbstractLibroComprasVentasBuilderStrategy::class)]
#[CoversClass(BuilderStrategy::class)]
#[CoversClass(AbstractArrayLoaderStrategy::class)]
#[CoversClass(AbstractLibroComprasVentasArrayLoaderStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(AutorizacionDte::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(BookComponent::class)]
#[CoversClass(LoaderWorker::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(BookBag::class)]
#[CoversClass(BookException::class)]
#[CoversClass(AbstractBook::class)]
#[CoversClass(LibroComprasVentas::class)]
class BookWorkersTest extends TestCase
{
    private LoaderWorkerInterface $loader;

    private BuilderWorkerInterface $builder;

    private ValidatorWorkerInterface $validator;

    private CertificateInterface $certificate;

    private EmisorInterface $emisor;

    protected function setUp(): void
    {
        $component = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getBookComponent()
        ;

        $this->loader = $component->getLoaderWorker();
        $this->builder = $component->getBuilderWorker();
        $this->validator = $component->getValidatorWorker();

        $this->certificate = (new CertificateFaker(new CertificateLoader()))->createFake(id: '76192083-9');
        $this->emisor = (new EmisorFactory())->create([
            'rut' => '76192083-9',
            'razon_social' => 'SASCO SpA',
            'autorizacion_dte' => ['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80],
        ]);
    }

    /**
     * Libro de ventas construido con el flujo completo (carga, construcción y firma).
     */
    private function ventas(): BookBag
    {
        $bag = new BookBag(
            tipo: TipoLibro::VENTAS,
            caratula: ['PeriodoTributario' => '2024-01'],
            detalle: [[
                'TpoDoc' => 33, 'NroDoc' => 1, 'FchDoc' => '2024-01-05', 'RUTDoc' => '11111111-1',
                'TasaImp' => 19, 'MntNeto' => 1000,
            ]],
            certificate: $this->certificate,
            emisor: $this->emisor,
        );
        $this->loader->load($bag);
        $this->builder->build($bag);

        return $bag;
    }

    public function testTheComponentExposesItsWorkers(): void
    {
        $component = Application::getInstance()->getPackageRegistry()->getBillingPackage()->getBookComponent();

        $workers = $component->getWorkers();

        $this->assertSame($this->loader, $workers['loader']);
        $this->assertSame($this->builder, $workers['builder']);
        $this->assertSame($this->validator, $workers['validator']);
    }

    // Bolsa.

    public function testBagDefaults(): void
    {
        $bag = new BookBag(TipoLibro::VENTAS);

        $this->assertSame(TipoLibro::VENTAS, $bag->getTipo());
        $this->assertSame([], $bag->getInputData());
        $this->assertSame([], $bag->getCaratula());
        $this->assertSame([], $bag->getDetalle());
        $this->assertSame([], $bag->getResumen());
        $this->assertNull($bag->getBook());
        $this->assertNull($bag->getCertificate());
        $this->assertNull($bag->getEmisor());
        $this->assertNull($bag->getData());
        $this->assertNull($bag->getBookAuth());
    }

    public function testBagSetters(): void
    {
        $bag = new BookBag(TipoLibro::VENTAS);

        $this->assertSame($bag, $bag->setInputData('a;b;c'));
        $this->assertSame('a;b;c', $bag->getInputData());
        $bag->setInputData(['x' => 1]);
        $this->assertSame(['x' => 1], $bag->getInputData());

        $bag->setCaratula(['PeriodoTributario' => '2024-02'])->setDetalle([['TpoDoc' => 33]])->setResumen([['TpoDoc' => 33, 'TotDoc' => 1]]);
        $this->assertSame(['PeriodoTributario' => '2024-02'], $bag->getCaratula());
        $this->assertSame([['TpoDoc' => 33]], $bag->getDetalle());
        $this->assertSame([['TpoDoc' => 33, 'TotDoc' => 1]], $bag->getResumen());

        $this->assertSame($bag, $bag->setCertificate($this->certificate)->setEmisor($this->emisor));
        $this->assertSame($this->certificate, $bag->getCertificate());
        $this->assertSame($this->emisor, $bag->getEmisor());
    }

    public function testBagWithoutBookThrowsWhenTheBookIsRequired(): void
    {
        $this->expectException(BookException::class);
        $this->expectExceptionMessage('book');
        (new BookBag(TipoLibro::VENTAS))->requireBook();
    }

    public function testBagWithCertificateIsACopyWithTheOtherCertificate(): void
    {
        $original = $this->ventas();
        $other = (new CertificateFaker(new CertificateLoader()))->createFake(id: '12345678-5');

        $copy = $original->withCertificate($other);

        $this->assertNotSame($original, $copy);
        $this->assertSame($other, $copy->getCertificate());
        $this->assertSame($this->certificate, $original->getCertificate());
        $this->assertSame($original->getBook(), $copy->getBook());
        $this->assertSame($original->getEmisor(), $copy->getEmisor());
        $this->assertSame($original->getTipo(), $copy->getTipo());
    }

    public function testBagOfABuiltBookSerializes(): void
    {
        $bag = $this->ventas();

        $this->assertSame('76192083-9', $bag->requireBook()->getCaratula()['RutEmisorLibro']);
        $this->assertSame(['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80], $bag->getBookAuth());
        $this->assertNotNull($bag->getData());

        $array = ArrayReader::fromArray($bag->toArray());
        $this->assertSame($bag->requireBook()->toArray(), $array->requireMap('book'));
        $this->assertSame(TipoLibro::VENTAS->toArray(), $array->requireMap('book_type'));
        $this->assertSame(['fecha_resolucion' => '2014-08-22', 'numero_resolucion' => 80], $array->requireMap('book_auth'));
        $this->assertSame($bag->getDetalle(), $array->requireList('detalle'));

        $json = ArrayReader::fromValue($bag->jsonSerialize());
        $this->assertSame($bag->requireBook()->jsonSerialize(), $json->requireMap('book'));
        $this->assertJson((string) json_encode($bag));
    }

    public function testBagWithoutBookSerializesWithNulls(): void
    {
        $bag = new BookBag(TipoLibro::GUIAS, caratula: ['a' => 1]);

        $array = $bag->toArray();
        $this->assertNull($array['book']);
        $this->assertNull($array['book_auth']);
        $this->assertSame(['a' => 1], $array['caratula']);
        $this->assertNull($bag->jsonSerialize()['book']);
    }

    // Carga.

    public function testLoadWithDataThatCannotBeNormalizedIsReportedAsBookException(): void
    {
        $bag = new BookBag(
            tipo: TipoLibro::COMPRAS,
            caratula: ['PeriodoTributario' => '2024-01'],
            detalle: [['TpoDoc' => 33, 'NroDoc' => 5, 'MntNeto' => 'mil pesos']],
            emisor: $this->emisor,
        );

        try {
            $this->loader->load($bag);
            $this->fail('A BookException was expected.');
        } catch (BookException $e) {
            $this->assertStringContainsString('libro_compras', $e->getMessage());
            $this->assertStringContainsString('MntNeto', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
        }
    }

    // Validación.

    public function testValidateSchemaAcceptsABagAnXmlDocumentAndAString(): void
    {
        $bag = $this->ventas();
        $xml = $bag->requireBook()->saveXml();

        $this->assertSame('LibroCompraVenta', $this->validator->validateSchema($bag)->getDomDocument()->documentElement?->localName);
        $this->assertSame('LibroCompraVenta', $this->validator->validateSchema($xml)->getDomDocument()->documentElement?->localName);
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);
        $this->assertSame($xmlDocument, $this->validator->validateSchema($xmlDocument));
    }

    public function testValidateSchemaWithoutSchemaLocationThrows(): void
    {
        $this->expectException(BookException::class);
        $this->validator->validateSchema('<LibroCompraVenta xmlns="http://www.sii.cl/SiiDte"><EnvioLibro ID="x"/></LibroCompraVenta>');
    }

    public function testValidateSchemaRejectsABookThatDoesNotFollowItsSchema(): void
    {
        $xml = str_replace('<TipoOperacion>VENTA</TipoOperacion>', '<TipoOperacion>ARRIENDO</TipoOperacion>', $this->ventas()->requireBook()->saveXml());
        $this->assertStringContainsString('ARRIENDO', $xml);

        $this->expectException(XmlException::class);
        $this->validator->validateSchema($xml);
    }

    public function testValidateSignatureAcceptsABagAndAString(): void
    {
        $bag = $this->ventas();

        $this->assertTrue($this->validator->validateSignature($bag)->isValid());
        $this->assertTrue($this->validator->validateSignature($bag->requireBook()->saveXml())->isValid());
    }

    public function testValidateSignatureDetectsABookAlteredAfterSigning(): void
    {
        $xml = str_replace('<TotMntNeto>1000</TotMntNeto>', '<TotMntNeto>9999</TotMntNeto>', $this->ventas()->requireBook()->saveXml());
        $this->assertStringContainsString('9999', $xml);

        $this->assertFalse($this->validator->validateSignature($xml)->isValid());
    }
}

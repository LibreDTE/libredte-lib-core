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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Book;

use Derafu\Array\ArrayReader;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroBoletas;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroComprasVentas;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\LibroGuias;
use libredte\lib\Core\Package\Billing\Component\Book\Entity\ResumenVentasDiarias;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoOperacion;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractBook::class)]
#[CoversClass(LibroComprasVentas::class)]
#[CoversClass(LibroGuias::class)]
#[CoversClass(LibroBoletas::class)]
#[CoversClass(ResumenVentasDiarias::class)]
#[CoversClass(TipoLibro::class)]
#[CoversClass(TipoOperacion::class)]
#[CoversClass(BookException::class)]
class BookEntitiesTest extends TestCase
{
    private function xml(string $xml): XmlDocument
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return $xmlDocument;
    }

    private function libroComprasVentas(string $operacion = 'VENTA', string $schema = 'LibroCV_v10.xsd', string $resumen = ''): LibroComprasVentas
    {
        return new LibroComprasVentas($this->xml(
            '<LibroCompraVenta xmlns="http://www.sii.cl/SiiDte" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.sii.cl/SiiDte ' . $schema . '" version="1.0">'
            . '<EnvioLibro ID="LibreDTE_LibroVentas"><Caratula><RutEmisorLibro>76192083-9</RutEmisorLibro>'
            . '<TipoOperacion>' . $operacion . '</TipoOperacion></Caratula>'
            . $resumen
            . '<Detalle><TpoDoc>33</TpoDoc><NroDoc>1</NroDoc></Detalle>'
            . '<Detalle><TpoDoc>33</TpoDoc><NroDoc>2</NroDoc></Detalle>'
            . '</EnvioLibro></LibroCompraVenta>'
        ));
    }

    // Tipo de libro y de operación.

    /**
     * @return array<string, array{TipoLibro, string, list<string>, bool, string, string}>
     */
    public static function provideTipoLibro(): array
    {
        return [
            'ventas' => [TipoLibro::VENTAS, '//ResumenPeriodo', ['TpoDoc', 'NroDoc'], true, 'Libro de ventas', 'Ventas'],
            'compras' => [TipoLibro::COMPRAS, '//ResumenPeriodo', ['RUTDoc', 'TpoDoc', 'NroDoc'], false, 'Libro de compras', 'Compras'],
            'boletas' => [TipoLibro::BOLETAS, '//Resumen', [], false, 'Libro de boletas', 'Boletas'],
            'guias' => [TipoLibro::GUIAS, '//ResumenPeriodo', ['Folio'], false, 'Libro de guías', 'Guias'],
            'rvd' => [TipoLibro::RVD, '//Resumen', ['TpoDoc', 'NroDoc'], false, 'Resumen de ventas diarias', 'RVD'],
        ];
    }

    /**
     * @param list<string> $key
     */
    #[DataProvider('provideTipoLibro')]
    public function testTipoLibro(TipoLibro $tipo, string $xpath, array $key, bool $acceptsResumen, string $nombre, string $corto): void
    {
        $this->assertSame($xpath, $tipo->getXpathResumen());
        $this->assertSame($key, $tipo->getDocumentKey());
        $this->assertSame($acceptsResumen, $tipo->acceptsResumen());
        $this->assertSame($tipo->value, $tipo->getCodigo());
        $this->assertSame($nombre, $tipo->getNombre());
        $this->assertSame($corto, $tipo->getNombreCorto());
        $this->assertSame(['codigo' => $tipo->value, 'nombre' => $nombre, 'nombre_corto' => $corto], $tipo->toArray());
    }

    public function testTipoOperacionKnowsItsTipoLibro(): void
    {
        $this->assertSame(TipoLibro::VENTAS, TipoOperacion::VENTA->getTipoLibro());
        $this->assertSame(TipoLibro::COMPRAS, TipoOperacion::COMPRA->getTipoLibro());
        $this->assertSame(TipoOperacion::VENTA, TipoOperacion::tryFrom('VENTA'));
    }

    // Libro de compras y ventas.

    /**
     * @return array<string, array{string, TipoOperacion, TipoLibro}>
     */
    public static function provideOperaciones(): array
    {
        return [
            'venta' => ['VENTA', TipoOperacion::VENTA, TipoLibro::VENTAS],
            'compra' => ['COMPRA', TipoOperacion::COMPRA, TipoLibro::COMPRAS],
            'venta en minusculas' => ['venta', TipoOperacion::VENTA, TipoLibro::VENTAS],
            'compra mixta' => ['Compra', TipoOperacion::COMPRA, TipoLibro::COMPRAS],
        ];
    }

    #[DataProvider('provideOperaciones')]
    public function testLibroComprasVentasTipoDependsOnTheOperation(string $text, TipoOperacion $operacion, TipoLibro $tipo): void
    {
        $libro = $this->libroComprasVentas($text);

        $this->assertSame($operacion, $libro->getTipoOperacion());
        $this->assertSame($tipo, $libro->getTipo());
    }

    public function testLibroComprasVentasWithAnInvalidOperationNamesIt(): void
    {
        $libro = $this->libroComprasVentas('ARRIENDO');

        $this->expectException(BookException::class);
        $this->expectExceptionMessage('ARRIENDO');
        $libro->getTipoOperacion();
    }

    public function testLibroComprasVentasWithoutOperationThrows(): void
    {
        $libro = new LibroComprasVentas($this->xml(
            '<LibroCompraVenta><EnvioLibro ID="x"><Caratula><RutEmisorLibro>76192083-9</RutEmisorLibro></Caratula></EnvioLibro></LibroCompraVenta>'
        ));

        $this->expectException(BookException::class);
        $libro->getTipo();
    }

    public function testLibroComprasVentasIsSimplifiedOnlyWithItsSchema(): void
    {
        $this->assertFalse($this->libroComprasVentas()->isSimplificado());
        $this->assertTrue($this->libroComprasVentas('VENTA', 'LibroCVS_v10.xsd')->isSimplificado());
    }

    public function testLibroComprasVentasTotalesPeriodoAreAlwaysAList(): void
    {
        $one = $this->libroComprasVentas('VENTA', 'LibroCV_v10.xsd', '<ResumenPeriodo><TotalesPeriodo><TpoDoc>33</TpoDoc><TotDoc>2</TotDoc></TotalesPeriodo></ResumenPeriodo>');
        $two = $this->libroComprasVentas('VENTA', 'LibroCV_v10.xsd', '<ResumenPeriodo><TotalesPeriodo><TpoDoc>33</TpoDoc><TotDoc>2</TotDoc></TotalesPeriodo><TotalesPeriodo><TpoDoc>61</TpoDoc><TotDoc>1</TotDoc></TotalesPeriodo></ResumenPeriodo>');
        $none = $this->libroComprasVentas();

        $this->assertSame([['TpoDoc' => '33', 'TotDoc' => '2']], $one->getTotalesPeriodo());
        $this->assertSame(
            [['TpoDoc' => '33', 'TotDoc' => '2'], ['TpoDoc' => '61', 'TotDoc' => '1']],
            $two->getTotalesPeriodo()
        );
        $this->assertSame([], $none->getTotalesPeriodo());
    }

    // Lo común a todos los libros.

    public function testBookCommonAccessors(): void
    {
        $libro = $this->libroComprasVentas(
            'VENTA',
            'LibroCV_v10.xsd',
            '<ResumenPeriodo><TotalesPeriodo><TpoDoc>33</TpoDoc></TotalesPeriodo></ResumenPeriodo>'
        );

        $this->assertSame('LibreDTE_LibroVentas', $libro->getId());
        $this->assertSame('76192083-9', $libro->getCaratula()['RutEmisorLibro']);
        $this->assertSame('LibroCV_v10.xsd', $libro->getSchema());
        $this->assertSame(2, $libro->countDetalle());
        $this->assertSame('2', ArrayReader::fromArray($libro->getDetalle())->requireAsString('1.NroDoc'));
        $this->assertCount(1, $libro->getResumen());
        $this->assertNull($libro->getSignatureNamespace());
    }

    public function testBookWithoutIdCaratulaNorDetalleHasEmptyValues(): void
    {
        $libro = new LibroGuias($this->xml('<LibroGuia><EnvioLibro/></LibroGuia>'));

        $this->assertSame('', $libro->getId());
        $this->assertSame([], $libro->getCaratula());
        $this->assertSame([], $libro->getDetalle());
        $this->assertSame(0, $libro->countDetalle());
        $this->assertSame([], $libro->getResumen());
    }

    public function testBookWithoutSchemaLocationThrowsWhenAskedForItsSchema(): void
    {
        $libro = new LibroGuias($this->xml('<LibroGuia><EnvioLibro ID="x"/></LibroGuia>'));

        $this->expectException(BookException::class);
        $libro->getSchema();
    }

    public function testBookXmlAndSerialization(): void
    {
        $libro = $this->libroComprasVentas();

        $this->assertStringStartsWith('<?xml', $libro->saveXml());
        $this->assertStringStartsWith('<LibroCompraVenta', $libro->getXml());
        $this->assertStringNotContainsString('<?xml', $libro->getXml());
        $this->assertSame($libro->saveXml(), ArrayReader::fromArray($libro->toArray())->requireString('xml'));

        $json = ArrayReader::fromValue($libro->jsonSerialize());
        $this->assertSame($libro->saveXml(), base64_decode($json->requireString('xml'), true));
        $this->assertJson((string) json_encode($libro));
        $this->assertFalse($libro->isSimplificado());
    }

    public function testAbstractBookWithoutTipoThrows(): void
    {
        $libro = new class ($this->xml('<Libro/>')) extends AbstractBook {
        };

        $this->expectException(TranslatableLogicException::class);
        $libro->getTipo();
    }

    public function testBookTypesOfTheEntities(): void
    {
        $xml = $this->xml('<Libro/>');

        $this->assertSame(TipoLibro::GUIAS, (new LibroGuias($xml))->getTipo());
        $this->assertSame(TipoLibro::BOLETAS, (new LibroBoletas($xml))->getTipo());
        $this->assertSame(TipoLibro::RVD, (new ResumenVentasDiarias($xml))->getTipo());
    }

    public function testLibroBoletasSignsWithTheSiiNamespace(): void
    {
        $this->assertSame('http://www.sii.cl/SiiDte', (new LibroBoletas($this->xml('<Libro/>')))->getSignatureNamespace());
    }

    public function testLibroGuiasFolioNotificacion(): void
    {
        $with = new LibroGuias($this->xml('<LibroGuia><EnvioLibro><Caratula><FolioNotificacion>1234</FolioNotificacion></Caratula></EnvioLibro></LibroGuia>'));
        $without = new LibroGuias($this->xml('<LibroGuia><EnvioLibro><Caratula/></EnvioLibro></LibroGuia>'));

        $this->assertSame(1234, $with->getFolioNotificacion());
        $this->assertNull($without->getFolioNotificacion());
    }

    public function testResumenVentasDiariasAccessors(): void
    {
        $rvd = new ResumenVentasDiarias($this->xml(
            '<ConsumoFolios><DocumentoConsumoFolios ID="LibreDTE_ConsumoFolio"><Caratula>'
            . '<FchInicio>2024-01-01</FchInicio><FchFinal>2024-01-31</FchFinal><SecEnvio>3</SecEnvio>'
            . '</Caratula></DocumentoConsumoFolios></ConsumoFolios>'
        ));

        $this->assertSame('LibreDTE_ConsumoFolio', $rvd->getId());
        $this->assertSame('2024-01-01', $rvd->getFechaInicial());
        $this->assertSame('2024-01-31', $rvd->getFechaFinal());
        $this->assertSame(3, $rvd->getSecuencia());
    }

    public function testResumenVentasDiariasDefaults(): void
    {
        $rvd = new ResumenVentasDiarias($this->xml('<ConsumoFolios><DocumentoConsumoFolios><Caratula/></DocumentoConsumoFolios></ConsumoFolios>'));

        $this->assertSame('', $rvd->getId());
        $this->assertSame('', $rvd->getFechaInicial());
        $this->assertSame('', $rvd->getFechaFinal());
        $this->assertSame(1, $rvd->getSecuencia());
    }
}

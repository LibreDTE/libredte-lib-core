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

use Derafu\Date\Service\DateFormatter;
use Derafu\Enum\Currency;
use Derafu\Renderer\Exception\FormatterException;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaModalidadVenta;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaMoneda;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TagXml;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataFormatter;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(RepositorySourceProvider::class)]
#[CoversClass(TemplateDataFormatter::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(TagXml::class)]
#[CoversClass(AduanaModalidadVenta::class)]
#[CoversClass(AduanaMoneda::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(CodigoDocumento::class)]
class TemplateDataFormatterTest extends TestCase
{
    private TemplateDataFormatter $formatter;

    private RepositoryManagerInterface $repositories;

    protected function setUp(): void
    {
        $this->repositories = ValueCaster::requireObject(
            Application::getInstance()->getService(RepositoryManagerInterface::class),
            RepositoryManagerInterface::class
        );
        $this->formatter = new TemplateDataFormatter($this->repositories, new DateFormatter());
    }

    /**
     * @return array<string, array{int|float|string, string}>
     */
    public static function provideNumbers(): array
    {
        return [
            'integer' => [999, '999'],
            'thousands' => [1000, '1.000'],
            'millions' => ['2500000', '2.500.000'],
            'zero' => [0, '0'],
            'rounds half up' => [1234567.5, '1.234.568'],
            'rounds down' => [1999.4, '1.999'],
            'negative' => [-1500.4, '-1.500'],
            'text with decimals' => ['1000.6', '1.001'],
        ];
    }

    #[DataProvider('provideNumbers')]
    public function testNumberFormat(int|float|string $value, string $expected): void
    {
        $this->assertSame($expected, $this->formatter->handle($value, 'Number'));
        $this->assertSame($expected, $this->formatter->handle($value, 'TotItems'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideRutFormats(): array
    {
        return [
            'emisor' => ['RUTEmisor'],
            'receptor' => ['RUTRecep'],
            'solicita' => ['RUTSolicita'],
            'transportista' => ['RUTTrans'],
            'chofer' => ['RUTChofer'],
        ];
    }

    #[DataProvider('provideRutFormats')]
    public function testRutFormats(string $format): void
    {
        $this->assertSame('76.192.083-9', $this->formatter->handle('76192083-9', $format));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideShortDateFormats(): array
    {
        return [
            'desde' => ['PeriodoDesde'],
            'hasta' => ['PeriodoHasta'],
            'pago' => ['FchPago'],
            'salida' => ['FchSalida'],
            'llegada' => ['FchLlegada'],
        ];
    }

    #[DataProvider('provideShortDateFormats')]
    public function testShortDateFormats(string $format): void
    {
        $this->assertSame('05/03/2024', $this->formatter->handle('2024-03-05', $format));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideLongDateFormats(): array
    {
        return [
            'emision' => ['FchEmis'],
            'referencia' => ['FchRef'],
            'vencimiento' => ['FchVenc'],
            'cancelacion' => ['FchCancel'],
        ];
    }

    #[DataProvider('provideLongDateFormats')]
    public function testLongDateFormatsWriteTheYearAndTheDay(string $format): void
    {
        $text = $this->formatter->handle('2024-03-05', $format);

        $this->assertStringContainsString('2024', $text);
        $this->assertStringContainsString('5', $text);
        $this->assertNotSame('2024-03-05', $text);
    }

    public function testResolutionDateOnlyShowsTheYear(): void
    {
        $this->assertSame('2014', $this->formatter->handle('2014-08-22', 'FchResol'));
    }

    public function testCommuneHandlersUseTheCommuneRepository(): void
    {
        $this->assertSame('ARICA', $this->formatter->handle('Arica', 'CdgSIISucur'));
        $this->assertSame('Santiago', $this->formatter->handle('HUECHURABA', 'CiudadOrigen'));
        $this->assertSame('Santiago', $this->formatter->handle('LAS CONDES', 'CiudadRecep'));
    }

    public function testAduanaTagWithAnEntityShowsItsNameAndTheDescriptionOfTheCode(): void
    {
        $name = $this->repositories->getRepository(TagXml::class)->find('CodModVenta')?->getGlosa();
        $description = (string) $this->repositories->getRepository(AduanaModalidadVenta::class)->find('1');

        $this->assertSame('Mod. venta', $name);
        $this->assertSame('A firme', $description);
        $this->assertSame('Mod. venta: A firme', $this->formatter->handle('CodModVenta:1', 'Aduana'));
    }

    public function testAduanaTagWithFreeValueShowsItsNameAndTheValue(): void
    {
        $this->assertSame('Nomb. trans.: ACME Transportes', $this->formatter->handle('NombreTransp:ACME Transportes', 'Aduana'));
        $this->assertSame('Total claú.: 5', $this->formatter->handle('TotClauVenta:5', 'Aduana'));
    }

    public function testAduanaFreeValueKeepsTheColonsThatItHas(): void
    {
        $this->assertSame(
            'Nomb. trans.: Av. Siempre Viva 742: piso 2',
            $this->formatter->handle('NombreTransp:Av. Siempre Viva 742: piso 2', 'Aduana')
        );
    }

    public function testAduanaUnknownTagIsOmitted(): void
    {
        $this->assertSame('', $this->formatter->handle('NoExiste:1', 'Aduana'));
    }

    public function testAmountsAreFormattedWithTheCurrencyOfTheirName(): void
    {
        $this->assertSame(Currency::ARS->format(1500.5), $this->formatter->handle('PESO AR:1500.5', 'MontoMoneda'));
        $this->assertSame(Currency::XXX->format(10.0), $this->formatter->handle('MONEDA INEXISTENTE:10', 'MontoMoneda'));
    }

    public function testRepositoryHandlersTranslateTheCodeToItsDescription(): void
    {
        $this->assertSame('Factura electrónica', $this->formatter->handle(33, 'TipoDTE'));
        $this->assertSame('Factura electrónica', $this->formatter->handle(33, 'TpoDocRef'));
    }

    public function testUnknownFormatThrows(): void
    {
        $this->expectException(FormatterException::class);
        $this->expectExceptionMessage('NoExiste');
        $this->formatter->handle('x', 'NoExiste');
    }

    public function testSupportedFormatsIncludeTheHandlersOfTheTemplates(): void
    {
        $formats = $this->formatter->getSupportedFormats();

        foreach (['TipoDTE', 'RUTEmisor', 'FchEmis', 'Aduana', 'Number', 'MontoMoneda', 'TED'] as $format) {
            $this->assertContains($format, $formats);
        }
    }
}

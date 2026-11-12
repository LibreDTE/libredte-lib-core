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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Document\Worker\Parser\Strategy\Form;

use Derafu\Array\ArrayReader;
use Derafu\Array\Exception\TypeMismatchException as ArrayTypeMismatchException;
use Derafu\Value\Exception\TypeMismatchException as ValueTypeMismatchException;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Form\EstandarParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * Test for the "form.estandar" strategy with edge cases.
 *
 * Each case is a YAML file in `parsers_edge/form/estandar` with data as a form
 * would submit it (every scalar value is a string) and, under the `Test` key,
 * the expected parser output values, including data type (`ExpectedValues`) or
 * the exception it must throw (`Exception`, class name within the document
 * exception namespace).
 */
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(EstandarParserStrategy::class)]
#[UsesClass(RepositorySourceProvider::class)]
class EstandarParserStrategyTest extends TestCase
{
    private EstandarParserStrategy $strategy;

    protected function setUp(): void
    {
        $strategy = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getParserWorker()
            ->getStrategy('form.estandar')
        ;
        assert($strategy instanceof EstandarParserStrategy);

        $this->strategy = $strategy;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideEdgeCases(): array
    {
        $files = ValueType::listOf(ValueType::string())->parse(glob(
            self::getFixturesPath() . '/parsers_edge/form/estandar/*.yaml'
        ));

        $edgeCases = [];
        foreach ($files as $file) {
            $edgeCases[basename($file)] = [$file];
        }

        return $edgeCases;
    }

    #[DataProvider('provideEdgeCases')]
    public function testEdgeCase(string $file): void
    {
        $caseName = basename($file);
        $data = ValueType::map()->parse(
            Yaml::parse(ValueCaster::requireString(file_get_contents($file))),
            '',
            $caseName
        );
        $test = ValueType::map()->parse($data['Test'] ?? null, 'Test', $caseName);
        unset($data['Test']);

        if (isset($test['Exception'])) {
            $class = 'libredte\lib\Core\Package\Billing\Component\Document\Exception\\'
                . ValueType::string()->parse($test['Exception'], 'Test.Exception', $caseName);
            $this->assertTrue(is_subclass_of($class, Throwable::class), $class);
            $this->expectException($class);
            $this->strategy->parse($data);

            return;
        }

        $result = $this->strategy->parse($data);

        $this->assertExpectedValues(
            ValueType::array()->parse($test['ExpectedValues'] ?? null, 'Test.ExpectedValues', $caseName),
            $result,
            $caseName
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, string, class-string<\Throwable>}>
     */
    public static function provideFieldsWithAWrongType(): array
    {
        $payments = ['FmaPago' => '2', 'FchPago' => ['2025-02-01']];

        return [
            'activity code that is not a number' => [['Acteco' => 'abc'], 'Acteco', ArrayTypeMismatchException::class],
            'items that are not a list' => [['NmbItem' => 'solo texto'], 'NmbItem', ArrayTypeMismatchException::class],
            'payment dates that are not a list' => [['FmaPago' => '2', 'FchPago' => '2025-02-01'], 'FchPago', ArrayTypeMismatchException::class],
            'payment amount that is not a number' => [$payments + ['MntPago' => ['abc']], 'MntPago', ValueTypeMismatchException::class],
            'exchange rate that is not a number' => [['TpoDoc' => '110', 'TpoCambio' => 'abc', 'FmaPago' => ''], 'TpoCambio', ArrayTypeMismatchException::class],
            'additional tax rate that is not a number' => [[
                'CodImpAdic' => ['24'],
                'impuesto_adicional_tasa_24' => 'abc',
                'NmbItem' => ['A'],
                'PrcItem' => ['10'],
                'QtyItem' => ['1'],
                'IndExe' => [''],
                'FmaPago' => '',
            ], 'impuesto_adicional_tasa_24', ArrayTypeMismatchException::class],
            'item discount that is not a number' => [['ValorDR' => ['abc', ''], 'TpoValor' => ['$', ''], 'FmaPago' => ''], 'ValorDR', ValueTypeMismatchException::class],
            'global discount that is not a number' => [['ValorDR_global' => 'abc', 'TpoValor_global' => '$', 'FmaPago' => ''], 'ValorDR_global', ArrayTypeMismatchException::class],
        ];
    }

    /**
     * @param array<string, mixed> $change
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('provideFieldsWithAWrongType')]
    public function testFieldWithAWrongTypeIsReportedWithItsName(array $change, string $field, string $exception): void
    {
        $data = ValueType::map()->parse(Yaml::parseFile(
            self::getFixturesPath() . '/parsers_edge/form/estandar/033_pago_credito_impuesto_tipos_datos.yaml'
        ));
        unset($data['Test']);

        $this->expectException($exception);
        $this->expectExceptionMessage('"' . $field . '"');

        $this->strategy->parse(array_merge($data, $change));
    }

    public function testEmptyYamlThrowsParserException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Direct access to the preview is not allowed.');

        $this->strategy->parse('');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideYamlThatIsNotAMapping(): array
    {
        return [
            'text' => ['hola'],
            'number' => ['5'],
            'boolean' => ['true'],
        ];
    }

    #[DataProvider('provideYamlThatIsNotAMapping')]
    public function testYamlThatIsNotAMappingThrowsParserException(string $yaml): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('The form input data must be a mapping with string keys');

        $this->strategy->parse($yaml);
    }

    /**
     * Data of a valid case, as a form would submit it.
     *
     * @return array<string, mixed>
     */
    private function validFormData(string $file = '033_pago_credito_impuesto_tipos_datos.yaml'): array
    {
        $data = ValueType::map()->parse(Yaml::parseFile(
            self::getFixturesPath() . '/parsers_edge/form/estandar/' . $file
        ));
        unset($data['Test']);

        return $data;
    }

    public function testInvalidYamlThrowsParserException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('The provided YAML is invalid');

        $this->strategy->parse("clave: [sin cerrar\notra: valor");
    }

    public function testWithoutDocumentTypeThrowsParserException(): void
    {
        $data = $this->validFormData();
        unset($data['TpoDoc']);

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Must indicate the document type to issue.');

        $this->strategy->parse($data);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideRequiredFields(): array
    {
        $fields = ['FchEmis', 'GiroEmis', 'Acteco', 'RUTRecep', 'RznSocRecep', 'DirRecep', 'NmbItem', 'GiroRecep', 'CmnaRecep'];

        return array_combine($fields, array_map(fn (string $field) => [$field], $fields));
    }

    #[DataProvider('provideRequiredFields')]
    public function testMissingRequiredFieldIsNamed(string $field): void
    {
        $data = $this->validFormData();
        $data[$field] = '';

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Must indicate the field ' . $field . '.');

        $this->strategy->parse($data);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function provideInvalidDates(): array
    {
        return [
            'emission date that does not exist' => [['FchEmis' => '2025-02-30'], 'FchEmis', '2025-02-30'],
            'due date in another format' => [['FchVenc' => '31/12/2025'], 'FchVenc', '31/12/2025'],
            'period start without zeros' => [['PeriodoDesde' => '2025-1-5'], 'PeriodoDesde', '2025-1-5'],
            'period end that is not a date' => [['PeriodoHasta' => 'enero'], 'PeriodoHasta', 'enero'],
            'departure date with a wrong month' => [['FchSalida' => '2025-13-01'], 'FchSalida', '2025-13-01'],
            'arrival date with a wrong day' => [['FchLlegada' => '2025-12-32'], 'FchLlegada', '2025-12-32'],
            'payment date that is not a date' => [['FmaPago' => '2', 'FchPago' => ['2025-03-10', 'pronto']], 'FchPago.1', 'pronto'],
            'reference date in another format' => [['FchRef' => ['10-03-2025']], 'FchRef.0', '10-03-2025'],
        ];
    }

    /**
     * @param array<string, mixed> $change
     */
    #[DataProvider('provideInvalidDates')]
    public function testDateThatIsNotYyyyMmDdIsReportedWithItsField(array $change, string $field, string $date): void
    {
        $data = array_merge($this->validFormData(), $change);

        try {
            $this->strategy->parse($data);
            $this->fail('A ParserException was expected.');
        } catch (ParserException $e) {
            $this->assertStringContainsString('must be in YYYY-MM-DD format', $e->getMessage());
            $this->assertStringContainsString($field, $e->getMessage());
            $this->assertStringContainsString($date, $e->getMessage());
        }
    }

    public function testEmptyDatesAreOptional(): void
    {
        $data = array_merge($this->validFormData(), ['FchVenc' => '', 'PeriodoDesde' => '', 'PeriodoHasta' => '', 'FchSalida' => '', 'FchLlegada' => '']);

        $result = $this->strategy->parse($data);

        $this->assertArrayHasKey('Encabezado', $result);
    }

    public function testBoletaItemWithAdditionalTaxIsNotSupported(): void
    {
        $data = $this->validFormData('039_boleta_descuentos_monto.yaml');
        $items = count(ValueType::list()->parse($data['NmbItem']));
        $data['IndExe'] = array_fill(0, $items, '0');
        $data['CodImpAdic'] = array_fill(0, $items, '24');

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('It is not possible to generate a receipt with additional taxes');

        $this->strategy->parse($data);
    }

    public function testDocumentTypeThatDoesNotExistIsReported(): void
    {
        $data = $this->validFormData();
        $data['TpoDoc'] = '999';

        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Could not retrieve VAT for document type 999');

        $this->strategy->parse($data);
    }

    public function testIssuerNameIsTakenFromTheEmisorField(): void
    {
        $data = array_merge($this->validFormData(), [
            'RznSoc' => null, 'RznSocEmisor' => 'Emisora SpA',
        ]);

        $reader = ArrayReader::fromArray($this->strategy->parse($data));

        $this->assertSame('Emisora SpA', $reader->requireString('Encabezado.Emisor.RznSoc'));
    }

    public function testWithoutTransportDataTheDocumentHasNoTransport(): void
    {
        $data = array_merge($this->validFormData(), [
            'Patente' => '', 'RUTTrans' => '', 'RUTChofer' => '', 'NombreChofer' => '', 'DirDest' => '', 'CmnaDest' => '',
        ]);

        $reader = ArrayReader::fromArray($this->strategy->parse($data));

        $this->assertNull($reader->get('Encabezado.Transporte', ValueType::mixed()));
    }

    public function testItemCodeIsInternalWhenItHasNoType(): void
    {
        $data = array_merge($this->validFormData(), ['VlrCodigo' => ['A-1'], 'TpoCodigo' => []]);

        $reader = ArrayReader::fromArray($this->strategy->parse($data));

        $this->assertSame('INT1', $reader->requireString('Detalle.0.CdgItem.TpoCodigo'));
        $this->assertSame('A-1', $reader->requireString('Detalle.0.CdgItem.VlrCodigo'));
    }

    public function testItemCodeKeepsItsType(): void
    {
        $data = array_merge($this->validFormData(), ['VlrCodigo' => ['7801234567890'], 'TpoCodigo' => ['EAN13']]);

        $reader = ArrayReader::fromArray($this->strategy->parse($data));

        $this->assertSame('EAN13', $reader->requireString('Detalle.0.CdgItem.TpoCodigo'));
    }

    /**
     * @return array<string, array{string, string, string|null}>
     */
    public static function provideServiceIndicators(): array
    {
        return [
            'factura con indicador válido' => ['033_pago_credito_impuesto_tipos_datos.yaml', '2', '2'],
            'factura con indicador que no corresponde' => ['033_pago_credito_impuesto_tipos_datos.yaml', '5', null],
            'boleta con el valor invertido (1 pasa a 2)' => ['039_boleta_descuentos_monto.yaml', '1', '2'],
            'boleta con el valor invertido (2 pasa a 1)' => ['039_boleta_descuentos_monto.yaml', '2', '1'],
            'boleta con indicador que no corresponde' => ['039_boleta_descuentos_monto.yaml', '9', null],
        ];
    }

    #[DataProvider('provideServiceIndicators')]
    public function testServiceIndicatorDependsOnTheDocumentType(string $file, string $given, ?string $expected): void
    {
        $data = array_merge($this->validFormData($file), ['IndServicio' => $given]);

        $reader = ArrayReader::fromArray($this->strategy->parse($data));

        $actual = $reader->get('Encabezado.IdDoc.IndServicio', ValueType::mixed());
        $this->assertSame($expected, is_scalar($actual) ? (string) $actual : null);
    }

    /**
     * Recursively validates expected values in the parser result,
     * including data type.
     * @param array<array-key, mixed> $expected
     * @param array<array-key, mixed> $actual
     */
    private function assertExpectedValues(
        array $expected,
        array $actual,
        string $caseName,
        string $parentKey = ''
    ): void {
        foreach ($expected as $key => $expectedValue) {
            $fullKey = $parentKey ? $parentKey . '.' . $key : (string) $key;

            $this->assertArrayHasKey($key, $actual, sprintf(
                'Case %s is missing field %s.',
                $caseName,
                $fullKey
            ));

            if (is_array($expectedValue)) {
                $this->assertIsArray($actual[$key]);
                $this->assertExpectedValues(
                    $expectedValue,
                    $actual[$key],
                    $caseName,
                    $fullKey
                );
            } else {
                $this->assertSame($expectedValue, $actual[$key], sprintf(
                    'Case %s: value of %s does not match expected.',
                    $caseName,
                    $fullKey
                ));
            }
        }
    }
}

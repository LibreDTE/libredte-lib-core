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

use Derafu\Array\Exception\TypeMismatchException as ArrayTypeMismatchException;
use Derafu\Value\Exception\TypeMismatchException as ValueTypeMismatchException;
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
        $files = glob(
            self::getFixturesPath() . '/parsers_edge/form/estandar/*.yaml'
        );

        $edgeCases = [];
        foreach ($files as $file) {
            $edgeCases[basename($file)] = [$file];
        }

        return $edgeCases;
    }

    #[DataProvider('provideEdgeCases')]
    public function testEdgeCase(string $file): void
    {
        $data = Yaml::parse(file_get_contents($file));
        $test = $data['Test'];
        unset($data['Test']);

        if (isset($test['Exception'])) {
            $this->expectException(
                'libredte\lib\Core\Package\Billing\Component\Document\Exception\\'
                . $test['Exception']
            );
            $this->strategy->parse($data);

            return;
        }

        $result = $this->strategy->parse($data);

        $this->assertExpectedValues(
            $test['ExpectedValues'],
            $result,
            basename($file)
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
        $data = Yaml::parseFile(
            self::getFixturesPath() . '/parsers_edge/form/estandar/033_pago_credito_impuesto_tipos_datos.yaml'
        );
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
        $this->expectExceptionMessage('must be a YAML mapping or list');

        $this->strategy->parse($yaml);
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

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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Document\Worker\Parser\Strategy\Default;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\XmlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\YamlParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Core\Provider\RepositorySourceProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Tests of the "default.json", "default.yaml" and "default.xml" strategies:
 * the data they accept and what they report when the input is not what the
 * strategy expects.
 */
#[CoversClass(Application::class)]
#[CoversClass(PackageRegistry::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(DocumentException::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(YamlParserStrategy::class)]
#[CoversClass(XmlParserStrategy::class)]
#[UsesClass(RepositorySourceProvider::class)]
class DefaultParserStrategiesTest extends TestCase
{
    private function strategy(string $name): ParserStrategyInterface
    {
        $strategy = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getParserWorker()
            ->getStrategy($name)
        ;
        assert($strategy instanceof ParserStrategyInterface);

        return $strategy;
    }

    public function testJsonObjectIsParsed(): void
    {
        $data = $this->strategy('default.json')->parse('{"Encabezado":{"IdDoc":{"TipoDTE":33}}}');

        $this->assertSame(['Encabezado' => ['IdDoc' => ['TipoDTE' => 33]]], $data);
    }

    public function testJsonThatIsNotValidIsReported(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('not valid JSON');

        $this->strategy('default.json')->parse('{"Encabezado":');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideJsonThatIsNotAnObject(): array
    {
        return [
            'number' => ['5'],
            'text' => ['"texto"'],
            'boolean' => ['true'],
        ];
    }

    #[DataProvider('provideJsonThatIsNotAnObject')]
    public function testJsonThatIsNotAnObjectOrArrayIsReported(string $json): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('must be a JSON object or array');

        $this->strategy('default.json')->parse($json);
    }

    public function testYamlMappingIsParsed(): void
    {
        $data = $this->strategy('default.yaml')->parse("Encabezado:\n  IdDoc:\n    TipoDTE: 33\n");

        $this->assertSame(['Encabezado' => ['IdDoc' => ['TipoDTE' => 33]]], $data);
    }

    public function testYamlThatIsNotValidIsReported(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('The provided YAML is invalid');

        $this->strategy('default.yaml')->parse("a: [1, 2\nb: : :");
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideYamlThatIsNotAMapping(): array
    {
        return [
            'text' => ['texto'],
            'number' => ['5'],
            'empty' => [''],
        ];
    }

    #[DataProvider('provideYamlThatIsNotAMapping')]
    public function testYamlThatIsNotAMappingOrListIsReported(string $yaml): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('must be a YAML mapping or list');

        $this->strategy('default.yaml')->parse($yaml);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function provideXmlDocuments(): array
    {
        return [
            'Documento' => [
                '<DTE version="1.0"><Documento ID="D1"><Encabezado><IdDoc><TipoDTE>33</TipoDTE></IdDoc></Encabezado></Documento></DTE>',
                ['Encabezado' => ['IdDoc' => ['TipoDTE' => '33']]],
            ],
            'Exportaciones' => [
                '<DTE version="1.0"><Exportaciones ID="D1"><Encabezado><IdDoc><TipoDTE>110</TipoDTE></IdDoc></Encabezado></Exportaciones></DTE>',
                ['Encabezado' => ['IdDoc' => ['TipoDTE' => '110']]],
            ],
            'Liquidacion' => [
                '<DTE version="1.0"><Liquidacion ID="D1"><Encabezado><IdDoc><TipoDTE>43</TipoDTE></IdDoc></Encabezado></Liquidacion></DTE>',
                ['Encabezado' => ['IdDoc' => ['TipoDTE' => '43']]],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('provideXmlDocuments')]
    public function testXmlDocumentIsParsedWithoutItsAttributes(string $xml, array $expected): void
    {
        $this->assertSame($expected, $this->strategy('default.xml')->parse($xml));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideXmlWithoutADocument(): array
    {
        return [
            'another root' => ['<Otro><Documento><a>1</a></Documento></Otro>'],
            'no document inside' => ['<DTE version="1.0"><Otro><a>1</a></Otro></DTE>'],
        ];
    }

    #[DataProvider('provideXmlWithoutADocument')]
    public function testXmlWithoutTheDocumentNodeIsReported(string $xml): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('root node must be the "DTE" tag');

        $this->strategy('default.xml')->parse($xml);
    }
}

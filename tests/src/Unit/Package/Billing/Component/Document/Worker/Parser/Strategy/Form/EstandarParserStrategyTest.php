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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Document\Worker\Parser\Strategy\Form;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Form\EstandarParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\PackageRegistry;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;

/**
 * Prueba de la estrategia "form.estandar" con casos de borde.
 *
 * Cada caso es un YAML en `parsers_edge/form/estandar` con los datos tal como
 * los entregaría un formulario (todo valor escalar es un string) y, en la
 * clave `Test`, los valores esperados del resultado del parser, incluyendo su
 * tipo de dato (`ExpectedValues`) o la excepción que debe lanzar (`Exception`,
 * nombre de la clase dentro del namespace de las excepciones del documento).
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
class EstandarParserStrategyTest extends TestCase
{
    private ParserStrategyInterface $strategy;

    protected function setUp(): void
    {
        $strategy = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getParserWorker()
            ->getStrategy('form.estandar')
        ;
        assert($strategy instanceof ParserStrategyInterface);

        $this->strategy = $strategy;
    }

    public static function provideCasosBorde(): array
    {
        $files = glob(
            self::getFixturesPath() . '/parsers_edge/form/estandar/*.yaml'
        );

        $casos = [];
        foreach ($files as $file) {
            $casos[basename($file)] = [$file];
        }

        return $casos;
    }

    #[DataProvider('provideCasosBorde')]
    public function testCasoBorde(string $file): void
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

    public function testYamlVacioLanzaParserException(): void
    {
        $this->expectException(ParserException::class);

        $this->strategy->parse('');
    }

    /**
     * Valida recursivamente los valores esperados en el resultado del parser,
     * incluyendo el tipo de dato.
     */
    private function assertExpectedValues(
        array $expected,
        array $actual,
        string $caso,
        string $parentKey = ''
    ): void {
        foreach ($expected as $key => $expectedValue) {
            $fullKey = $parentKey ? $parentKey . '.' . $key : (string) $key;

            $this->assertArrayHasKey($key, $actual, sprintf(
                'En el caso %s no existe el campo %s.',
                $caso,
                $fullKey
            ));

            if (is_array($expectedValue)) {
                $this->assertIsArray($actual[$key]);
                $this->assertExpectedValues(
                    $expectedValue,
                    $actual[$key],
                    $caso,
                    $fullKey
                );
            } else {
                $this->assertSame($expectedValue, $actual[$key], sprintf(
                    'En el caso %s el valor de %s no cuadra con el esperado.',
                    $caso,
                    $fullKey
                ));
            }
        }
    }
}

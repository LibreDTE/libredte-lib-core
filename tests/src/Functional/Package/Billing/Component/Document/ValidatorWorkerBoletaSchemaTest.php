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

use Derafu\Xml\Exception\XmlException;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;

/**
 * La validación del esquema del XML de una boleta suelta.
 *
 * El SII define el DTE de una boleta solo dentro del sobre `EnvioBOLETA`; el
 * esquema `BOLETA_v11.xsd` de LibreDTE lo declara en la raíz para poder
 * validarlo.
 */
#[CoversClass(ValidatorWorker::class)]
#[UsesNamespace('libredte\lib\Core')]
class ValidatorWorkerBoletaSchemaTest extends TestCase
{
    use BuildsDocumentsTrait;

    private ValidatorWorkerInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getValidatorWorker()
        ;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function boletas(): array
    {
        return [
            'boleta afecta' => ['039_boleta_afecta/039_001_simple'],
            'boleta exenta' => ['041_boleta_exenta/041_001_simple'],
        ];
    }

    #[DataProvider('boletas')]
    public function testValidateSchemaAcceptsABoletaThatMeetsTheSchema(string $exampleId): void
    {
        $xmlDocument = $this->buildDocument($exampleId)->getXmlDocument();

        $validated = $this->validator->validateSchema($xmlDocument->saveXml());

        $this->assertSame(
            $xmlDocument->query('//Encabezado/IdDoc/Folio'),
            $validated->query('//Encabezado/IdDoc/Folio')
        );
    }

    #[DataProvider('boletas')]
    public function testValidateSchemaRejectsABoletaThatDoesNotMeetTheSchema(string $exampleId): void
    {
        $xml = $this->buildDocument($exampleId)->getXmlDocument()->saveXml();
        $this->assertStringContainsString('<RUTRecep>', $xml);
        $invalid = str_replace('<RUTRecep>', '<RUTRecep>X', $xml);

        $this->expectException(XmlException::class);

        $this->validator->validateSchema($invalid);
    }
}

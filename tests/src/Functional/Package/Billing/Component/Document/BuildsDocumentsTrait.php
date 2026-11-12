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

use Derafu\Array\ArrayReader;
use Derafu\Array\ArrayWriter;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;

/**
 * Construye documentos reales (con CAF y certificado falsos) a partir de los
 * ejemplos del catálogo, para probar el comportamiento de los documentos.
 */
trait BuildsDocumentsTrait
{
    /**
     * Datos de un ejemplo, sin la sección de pruebas.
     *
     * @return array<string, mixed>
     */
    protected function exampleData(string $exampleId): array
    {
        return Application::getInstance()
            ->getPackageRegistry()
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getExamplesWorker()
            ->get($exampleId)['example']
        ;
    }

    /**
     * Datos de un ejemplo con valores cambiados y/o claves quitadas.
     *
     * @param array<string, mixed> $set Valores a asignar por ruta (`A.B.0.C`).
     * @param list<string> $remove Rutas a quitar.
     * @return array<string, mixed>
     */
    protected function modifiedExample(string $exampleId, array $set = [], array $remove = []): array
    {
        $data = $this->exampleData($exampleId);
        $writer = ArrayWriter::fromReference($data);

        foreach ($set as $path => $value) {
            $writer->set($path, $value);
        }
        foreach ($remove as $path) {
            $writer->remove($path);
        }

        return ArrayReader::fromArray($data)->toMap();
    }

    /**
     * Construye la bolsa con el documento (timbrado y firmado) de un ejemplo.
     *
     * @param array<string, mixed>|null $data Datos a usar en vez de los del
     * ejemplo (que sigue indicando el CAF y el emisor si no se pasan).
     */
    protected function buildBag(string $exampleId, ?array $data = null): DocumentBag
    {
        $data ??= $this->exampleData($exampleId);
        $registry = Application::getInstance()->getPackageRegistry();

        $reader = ArrayReader::fromArray($data, $exampleId);
        $emisor = new Emisor(
            $reader->requireAsString('Encabezado.Emisor.RUTEmisor'),
            $reader->getAsString('Encabezado.Emisor.RznSoc')
        );

        $caf = $registry->getBillingPackage()->getIdentifierComponent()->getCafFakerWorker()->create(
            $emisor,
            $reader->requireAsInt('Encabezado.IdDoc.TipoDTE'),
            $reader->requireAsInt('Encabezado.IdDoc.Folio')
        );
        $certificate = (new CertificateFaker(new CertificateLoader()))
            ->createFake(id: $emisor->getRut())
        ;

        $bag = new DocumentBag(parsedData: $data, caf: $caf, certificate: $certificate);
        $registry->getBillingPackage()->getDocumentComponent()->getBuilderWorker()->build($bag);

        return $bag;
    }

    /**
     * Construye el documento (timbrado y firmado) de un ejemplo.
     *
     * @param array<string, mixed>|null $data
     */
    protected function buildDocument(string $exampleId, ?array $data = null): DocumentInterface
    {
        return $this->buildBag($exampleId, $data)->requireDocument();
    }
}

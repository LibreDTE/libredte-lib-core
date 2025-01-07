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
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBatch;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessor\Strategy\Spreadsheet\CsvBatchProcessorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BatchProcessorWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(DocumentBatch::class)]
#[CoversClass(BatchProcessorWorker::class)]
#[CoversClass(CsvBatchProcessorStrategy::class)]
class EmisionMasivaTest extends TestCase
{
    public function testCargarDocumentosDesdeArchivoCsv(): void
    {
        $file = self::getFixturesPath('data/emision_masiva.csv');

        $app = Application::getInstance();

        $documents = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBatchProcessorWorker()
            ->process(new DocumentBatch($file))
        ;

        $this->assertIsArray($documents);

        // TODO: Se deben realizar validaciones sobre los datos de los
        // documentos generados. Ejemplo: cantidad de documentos, totales de
        // cada uno, total del lote, receptores, tipos de documentos, etc.
    }
}

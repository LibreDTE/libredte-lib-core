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

use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Verifies that `tests/fixtures/parsers/form/estandar` and
 * `tests/fixtures/yaml/documentos_ok` cover the same cases.
 *
 * Both directories represent the same example case collection in two formats:
 * `parsers/form/estandar` as a flat structure that maps to an HTML `<form>`
 * (see `EstandarParserStrategy`), and `yaml/documentos_ok` in the official
 * nested SII structure (also the real `document.examples` catalog; see
 * `ExamplesWorker`). Each case should exist in both with the same file name.
 * This test does not assert both produce the same DTE (that is covered separately
 * by `DocumentBuilderParsersFixturesTest` and
 * `DocumentOkExamplesEndToEndTest`); it only ensures no full case is
 * missing on either side.
 */
#[CoversNothing]
class EstandarFormDocumentosOkCoverageTest extends TestCase
{
    /**
     * `parsers/form/estandar` cases with no pair in `yaml/documentos_ok` on
     * purpose — they do not represent a complete/issuable document, but a
     * quirk of the `form.estandar` parser itself.
     */
    private const EXCLUDED_FORM_ESTANDAR_ONLY = [
        // Test Folio present in the array but empty
        // (`EstandarParserStrategy::setInitialDTE()`,
        // `array_key_exists('Folio', ...)`) — the resulting document has no
        // folio and is not an "OK" issuable document.
        '033_017_folio_manual_activado_sin_asignar',
    ];

    /**
     * `yaml/documentos_ok` cases with no pair in `parsers/form/estandar` on
     * purpose — `form.estandar` cannot express these documents today
     * (the parser lacks fields; it is not just a missing fixture).
     */
    private const EXCLUDED_DOCUMENTOS_OK_ONLY = [
        // Uses Encabezado.Transporte.Aduana (CodViaTransp/TotBultos/
        // CodPaisRecep), FmaPagExp, CiudadOrigen/CiudadDest, and
        // TipoDespacho — none supported by
        // EstandarParserStrategy::addExportData()/setInitialDTE() today.
        '110_003_transporte_terrestre_internacional',
    ];

    public function testAllFormEstandarCasesExistInDocumentosOk(): void
    {
        $missing = array_values(array_diff(
            $this->formEstandarCaseNames(),
            $this->documentosOkCaseNames(),
            self::EXCLUDED_FORM_ESTANDAR_ONLY,
        ));

        $this->assertSame([], $missing, sprintf(
            "Cases in 'parsers/form/estandar' with no match in " .
            "'yaml/documentos_ok': %s. If the case can be represented in " .
            "YAML (usually it can), add it there with the same " .
            "file name. If it genuinely does not apply (tests a " .
            "parser quirk, not a complete document), " .
            "add it to self::EXCLUDED_FORM_ESTANDAR_ONLY with a reason.",
            implode(', ', $missing)
        ));
    }

    public function testAllDocumentosOkCasesExistInFormEstandar(): void
    {
        $missing = array_values(array_diff(
            $this->documentosOkCaseNames(),
            $this->formEstandarCaseNames(),
            self::EXCLUDED_DOCUMENTOS_OK_ONLY,
        ));

        $this->assertSame([], $missing, sprintf(
            "Cases in 'yaml/documentos_ok' with no match in " .
            "'parsers/form/estandar': %s. Check whether form.estandar can " .
            "represent them and add them there. If it cannot (missing parser " .
            "fields), add them to self::EXCLUDED_DOCUMENTOS_OK_ONLY with " .
            "a reason.",
            implode(', ', $missing)
        ));
    }

    /**
     * @return string[] File names (without extension) in `form/estandar`.
     */
    private function formEstandarCaseNames(): array
    {
        $files = glob(self::getFixturesPath() . '/parsers/form/estandar/*.yaml');

        return array_map(
            fn (string $file): string => basename($file, '.yaml'),
            $files
        );
    }

    /**
     * @return string[] File names (without extension) in `documentos_ok`.
     */
    private function documentosOkCaseNames(): array
    {
        $files = glob(self::getFixturesPath() . '/yaml/documentos_ok/*/*.yaml');

        return array_map(
            fn (string $file): string => basename($file, '.yaml'),
            $files
        );
    }
}

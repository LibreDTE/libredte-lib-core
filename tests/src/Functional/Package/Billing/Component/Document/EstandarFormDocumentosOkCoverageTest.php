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
 * `EmitirIndividualmenteDocumentosOkTest`); it only ensures no full case is
 * missing on either side.
 */
#[CoversNothing]
class FormEstandarDocumentosOkCoverageTest extends TestCase
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

    public function testTodosLosCasosDeFormEstandarExistenEnDocumentosOk(): void
    {
        $faltantes = array_values(array_diff(
            $this->casosFormEstandar(),
            $this->casosDocumentosOk(),
            self::EXCLUDED_FORM_ESTANDAR_ONLY,
        ));

        $this->assertSame([], $faltantes, sprintf(
            "Hay casos en 'parsers/form/estandar' sin equivalente en " .
            "'yaml/documentos_ok': %s. Si el caso se puede representar en " .
            "YAML (casi siempre se puede), agrégalo ahí con el mismo " .
            "nombre de archivo. Si genuinamente no corresponde (prueba una " .
            "particularidad del parser, no un documento completo), " .
            "agrégalo a self::EXCLUDED_FORM_ESTANDAR_ONLY con el motivo.",
            implode(', ', $faltantes)
        ));
    }

    public function testTodosLosCasosDeDocumentosOkExistenEnFormEstandar(): void
    {
        $faltantes = array_values(array_diff(
            $this->casosDocumentosOk(),
            $this->casosFormEstandar(),
            self::EXCLUDED_DOCUMENTOS_OK_ONLY,
        ));

        $this->assertSame([], $faltantes, sprintf(
            "Hay casos en 'yaml/documentos_ok' sin equivalente en " .
            "'parsers/form/estandar': %s. Revisa si form.estandar puede " .
            "representarlo y agrégalo ahí. Si no puede (le faltan campos " .
            "al parser), agrégalo a self::EXCLUDED_DOCUMENTOS_OK_ONLY con " .
            "el motivo.",
            implode(', ', $faltantes)
        ));
    }

    /**
     * @return string[] File names (without extension) in `form/estandar`.
     */
    private function casosFormEstandar(): array
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
    private function casosDocumentosOk(): array
    {
        $files = glob(self::getFixturesPath() . '/yaml/documentos_ok/*/*.yaml');

        return array_map(
            fn (string $file): string => basename($file, '.yaml'),
            $files
        );
    }
}

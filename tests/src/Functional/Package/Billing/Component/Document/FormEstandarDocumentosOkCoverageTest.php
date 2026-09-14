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

use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Verifica que `tests/fixtures/parsers/form/estandar` y
 * `tests/fixtures/yaml/documentos_ok` cubran los mismos casos.
 *
 * Ambos directorios representan, en dos formatos distintos, la misma
 * colección de casos de ejemplo — `parsers/form/estandar` en el formato
 * plano que arma un `<form>` HTML (ver `EstandarParserStrategy`),
 * `yaml/documentos_ok` en la estructura anidada oficial del SII (además,
 * este segundo es el catálogo real de `document.examples`, ver
 * `ExamplesWorker`). Cada caso debería existir en ambos con el mismo
 * nombre de archivo — este test no verifica que ambos produzcan el mismo
 * DTE (eso ya lo hacen, cada uno por su lado,
 * `DocumentBuilderParsersFixturesTest`/`EmitirIndividualmenteDocumentosOkTest`),
 * solo que no falte un caso completo de un lado.
 */
#[CoversNothing]
class FormEstandarDocumentosOkCoverageTest extends TestCase
{
    /**
     * Casos de `parsers/form/estandar` sin par en `yaml/documentos_ok` a
     * propósito — no representan un documento completo/emitible, sino una
     * particularidad del parser `form.estandar` en sí.
     */
    private const EXCLUDED_FORM_ESTANDAR_ONLY = [
        // Prueba Folio presente en el arreglo pero vacío
        // (`EstandarParserStrategy::setInitialDTE()`,
        // `array_key_exists('Folio', ...)`) — el documento resultante no
        // tiene folio, no es un documento "OK" para emitir.
        '033_017_folio_manual_activado_sin_asignar',
    ];

    /**
     * Casos de `yaml/documentos_ok` sin par en `parsers/form/estandar` a
     * propósito — `form.estandar` no puede expresar estos documentos hoy
     * (le faltan campos al parser, no es solo que falte el fixture).
     */
    private const EXCLUDED_DOCUMENTOS_OK_ONLY = [
        // Usa Encabezado.Transporte.Aduana (CodViaTransp/TotBultos/
        // CodPaisRecep), FmaPagExp, CiudadOrigen/CiudadDest y
        // TipoDespacho — ninguno soportado por
        // EstandarParserStrategy::addExportData()/setInitialDTE() hoy.
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
     * @return string[] Nombres de archivo (sin extensión) en `form/estandar`.
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
     * @return string[] Nombres de archivo (sin extensión) en `documentos_ok`.
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

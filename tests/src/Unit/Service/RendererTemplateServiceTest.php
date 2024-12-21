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

namespace libredte\lib\Tests\Unit\Service;

use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Service\RendererTemplateService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathManager::class)]
#[CoversClass(RendererTemplateService::class)]
class RendererTemplateServiceTest extends TestCase
{
    private RendererTemplateService $rendererService;

    public function setUp(): void
    {
        $this->rendererService = new RendererTemplateService();
    }

    private function getDteData(): array
    {
        return [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 33,
                    'Folio' => 1,
                    'FchEmis' => '2024-12-21',
                ],
                'Emisor' => [
                    'RUTEmisor' => '76192083-9',
                    'RznSoc' => 'SASCO SpA',
                    'GiroEmis' => 'Tecnología, Informática y Telecomunicaciones',
                    'DirOrigen' => 'Santiago',
                    'CmnaOrigen' => 'Santiago',
                ],
                'Receptor' => [
                    'RUTRecep' => '60803000-K',
                    'RznSocRecep' => 'Servicio de Impuestos Internos',
                    'GiroRecep' => 'Gobierno',
                    'DirRecep' => 'Santiago',
                    'CmnaRecep' => 'Santiago',
                ],
                'Totales' => [
                    'MntNeto' => 480000,
                    'TasaIVA' => 19,
                    'IVA' => 91200,
                    'MntTotal' => 571200,
                ],
            ],
            'Detalle' => [
                [
                    'NroLinDet' => 1,
                    'NmbItem' => 'Servicio Plus de LibreDTE',
                    'QtyItem' => 12,
                    'PrcItem' => 40000,
                ],
            ],
        ];
    }

    public function testRenderTemplateInDefaultDirectory()
    {
        $template = 'dte/documento/estandar';
        $data = [
            'dte' => $this->getDteData(),
        ];
        $pdf = $this->rendererService->render($template, $data);

        $this->assertIsString($pdf);
    }

    public function testRenderCustomTemplate()
    {
        $testsPath = PathManager::getTestsPath();
        $template = $testsPath . '/resources/templates/custom_template';
        $data = [
            'title' => 'LibreDTE',
            'content' => 'I Love LibreDTE <3',
        ];
        $pdf = $this->rendererService->render($template, $data);

        $this->assertIsString($pdf);
    }
}

<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Service;

use libredte\lib\Core\Service\PathManager;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Servicio de renderización de plantillas.
 *
 * Permite renderizar plantillas Twig a HTML y PDF.
 */
class RendererTemplateService
{
    /**
     * Formato por defecto que se debe utilizar.
     *
     * @var string
     */
    private string $defaultFormat = 'pdf';

    /**
     * Rutas donde están las plantillas.
     *
     * @var array
     */
    private array $paths = [];

    /**
     * Cargador de plantillas mediante el sistema de archivos para Twig.
     */
    private FilesystemLoader $filesystemLoader;

    /**
     * Renderizador de plantillas HTML con Twig.
     *
     * @var Environment
     */
    private Environment $twig;

    /**
     * Constructor de la clase.
     */
    public function __construct()
    {
        // Ubicación por defecto de las plantillas Twig.
        $this->paths[] = PathManager::getTemplatesPath();

        // Agregar directorio de plantillas Pro.
        $this->addTemplatesPathPro();

        // Crear renderizador de HTML.
        $this->filesystemLoader = new FilesystemLoader($this->paths);
        $this->twig = new Environment($this->filesystemLoader);
    }

    /**
     * Renderiza una plantilla Twig.
     *
     * @param string $template Plantilla Twig a renderizar.
     * @param array $data Datos que se pasarán a la plantilla Twig.
     * @return string Código HTML con el renderizado de la plantilla Twig.
     */
    public function render(string $template, array $data): string
    {
        // Formato en el que se renderizará la plantilla.
        $format = $data['options']['format'] ?? $this->defaultFormat;

        // Renderizar HTML de la plantilla Twig.
        $html = $this->renderHtml($template, $data);

        // Si el formato solicitado es HTML se retorna directamente.
        if ($format === 'html') {
            return $html;
        }

        // Renderizar el PDF a partir del HTML.
        $configPdf = $data['options']['config']['pdf'] ?? [];
        $pdf = $this->renderPdf($html, $configPdf);

        // Entregar el contenido del PDF renderizado.
        return $pdf;
    }

    /**
     * Renderiza una plantilla Twig en HTML.
     *
     * @param string $template Plantilla Twig a renderizar.
     * @param array $data Datos que se pasarán a la plantilla Twig.
     * @return string Código HTML con el renderizado de la plantilla Twig.
     */
    private function renderHtml(string $template, array $data): string
    {
        // Resolver plantilla.
        $template = $this->resolveTemplate($template);

        // Renderizar la plantilla.
        return $this->twig->render($template, $data);
    }

    /**
     * Renderiza un HTML en un documento PDF.
     *
     * El renderizado se realiza a partir de un HTML previamente renderizado que
     * será pasado a PDF.
     */
    private function renderPdf(string $html, array $config): string
    {
        // Generar el PDF con mPDF.
        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);

        // Obtener el contenido del PDF.
        $pdf = $mpdf->Output('', Destination::STRING_RETURN);

        // Entregar el contenido del PDF.
        return $pdf;
    }

    /**
     * Resuelve la plantilla que se está solicitando.
     *
     * Se encarga de:
     *
     *   - Agregar la extensión a la plantilla.
     *   - Agregar el directorio si se pasó una ruta absoluta de la plantilla.
     *
     * @param string $template
     * @return string
     */
    private function resolveTemplate(string $template): string
    {
        // Agregar extensión.
        $template .= '.html.twig';

        // Agregar el directorio si se pasó una ruta absoluta.
        if ($template[0] === '/') {
            $dir = dirname($template);
            $this->filesystemLoader->addPath($dir);
            $template = basename($template);
        }

        // Entregar nombre de la plantilla.
        return $template;
    }

    /**
     * Agrega el directorio de plantillas Pro si existe.
     *
     * @return void
     */
    private function addTemplatesPathPro(): void
    {
        // Si existe lib-pro se omite agregar.
        if (!class_exists('\libredte\lib\Pro\LibPro')) {
            return;
        }

        // Agregar directorio.
        $this->paths[] = realpath(dirname(__DIR__, 5) . '/resources/templates');
    }
}

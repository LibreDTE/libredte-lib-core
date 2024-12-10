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

namespace libredte\lib\Core\Sii\Dte\Documento\Renderer;

use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use LogicException;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Clase abstracta (base) para los renderizadores de documentos tributarios
 * electrónicos (DTE).
 */
abstract class AbstractRenderer
{
    /**
     * Plantilla por defecto que se debe utilizar al renderizar el DTE.
     *
     * @var string
     */
    protected $defaultTemplate;

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase.
     *
     * @param ?DataProviderInterface $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Renderiza el documento en el formato solicitado (HTML o PDF).
     *
     * Por defecto se renderiza en formato PDF si no se especifica uno.
     *
     * @param AbstractDocumento $documento Documento a renderizar.
     * @param array $options Opciones para el renderizado.
     * @return string Datos del documento renderizado.
     */
    public function render(
        AbstractDocumento $documento,
        array $options = []
    ): string {
        // Opciones por defecto.
        $options = array_merge([
            'format' => 'pdf',
        ], $options);

        // Renderizar HTML del DTE.
        $html = $this->renderHtml($documento, $options);

        // Si el formato solicitado es HTML se retorna directamente.
        if ($options['format'] === 'html') {
            return $html;
        }

        // Renderizar el PDF a partir del HTML.
        $pdf = $this->renderPdf($html, $options);

        // Entregar el contenido del PDF renderizado.
        return $pdf;
    }

    /**
     * Entrega la plantilla por defecto asociada al renderizador del DTE.
     *
     * @return string Nombre de la plantilla por defecto.
     * @throws LogicException Si no existe una plantilla por defecto
     * asignada en el renderizador.
     */
    protected function getDefaultTemplate(): string
    {
        if (!isset($this->defaultTemplate)) {
            throw new LogicException(
                'No se ha asignado una plantilla por defecto para el renderizado del DTE.'
            );
        }

        return $this->defaultTemplate;
    }

    /**
     * Renderiza el documento en formato HTML.
     *
     * El renderizado se realiza mediante una plantilla twig.
     *
     * @param AbstractDocumento $documento Documento a renderizar.
     * @param array $options Opciones para el renderizado en HTML.
     * @return string Código HTML del documento renderizado.
     */
    protected function renderHtml(
        AbstractDocumento $documento,
        array $options = []
    ): string {
        // Opciones por defecto.
        $options = array_merge([
            'template' => $this->getDefaultTemplate(),
        ], $options);

        // Armar nombre de la plantilla twig.
        $template = $options['template'] . '.html.twig';

        // Preparar datos que se usarán para renderizar.
        $data = [
            'dte' => $documento->getData(),
        ];

        // Renderizar el HTML usando twig.
        $html = $this->renderHtmlWithTwig($template, $data);

        // Entregar el HTML renderizado.
        return $html;
    }

    /**
     * Renderiza una plantilla twig con ciertos datos.
     *
     * @param string $template Plantilla twig a renderizar.
     * @param array $data Datos que se pasarán a la plantilla twig.
     * @return string Código HTML con el renderizado de la plantilla twig.
     */
    protected function renderHtmlWithTwig(string $template, array $data): string
    {
        // Ubicación de las plantillas twig para los documentos tributarios.
        $templatesDir = PathManager::getTemplatesPath() . '/dte/documento';

        // Configurar Twig.
        $loader = new FilesystemLoader($templatesDir);
        $twig = new Environment($loader);

        // Renderizar la plantilla.
        return $twig->render($template, $data);
    }

    /**
     * Renderiza el documento en formato PDF.
     *
     * El renderizado se realiza a partir de un HTML previamente renderizado que
     * será pasado a PDF.
     */
    protected function renderPdf(string $html, array $options): string
    {
        // Generar el PDF con mPDF.
        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);

        // Obtener el contenido del PDF.
        $pdf = $mpdf->Output('', Destination::STRING_RETURN);

        // Entregar el contenido del PDF.
        return $pdf;
    }
}

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

use Illuminate\Support\Str;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use libredte\lib\Core\Sii\Dte\Documento\Builder\DocumentoFactory;

/**
 * Renderizador de la representación gráfica de un DTE.
 */
class DocumentoRenderer
{
    /**
     * Renderizador por defecto que se debe utilizar.
     *
     * @var string
     */
    private string $defaultRenderer = EstandarRenderer::class;

    /**
     * Listado de renderizadores disponibles (ya cargados).
     *
     * @var array
     */
    private array $renderers = [];

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
     * Renderiza el documento a partir del DTE oficial en XML.
     *
     * Este método utiliza un XML completo y real de un DTE.
     *
     * @param string $data Datos en formato XML del DTE.
     * @param array $options Opciones para el renderizado.
     * @return string
     */
    public function renderFromXml(
        string $data,
        array $options = []
    ): string {
        $factory = new DocumentoFactory($this->dataProvider);
        $documento = $factory->loadFromXml($data);

        return $this->renderFromDocumento($documento, $options);
    }

    /**
     * Renderiza el documento a partir de la instancia del documento.
     *
     * @param AbstractDocumento $documento Instancia del DTE.
     * @param array $options Opciones para el renderizado.
     * @return string
     */
    public function renderFromDocumento(
        AbstractDocumento $documento,
        array $options = []
    ): string {
        // Nnormalizar las opciones.
        $options = $this->normalizeOptions($options);

        // Crear el renderizador de los datos del DTE.
        $renderer = $this->getRendererInstance($options['renderer']);

        // Renderizar el documento.
        $data = $renderer->render($documento, $options);

        // Entregar los datos renderizados.
        return $data;
    }

    /**
     * Normaliza las opciones para el renderizado del documento.
     *
     * Este método:
     *
     *   - Asigna el renderizador por defecto.
     *   - Determina el nombre de la clase si se pasó como código de renderizado.
     *
     * @param array $options Opciones sin normalizar.
     * @return array Opciones normalizadas.
     */
    private function normalizeOptions(array $options): array
    {
        // Opciones por defecto para el renderizado.
        $options = array_merge([
            'renderer' => $this->defaultRenderer,
        ], $options);

        // Si el renderizador tiene "." es el código del renderizador con el
        // formato que el renderizador debe utilizar.
        if (str_contains($options['renderer'], '.')) {
            $aux = explode('.', $options['renderer']);
            $options['renderer'] = $this->getRendererClass($aux[0]);
            $options['format'] = $aux[1];
        }

        // Entregar las opciones que se normalizaron.
        return $options;
    }

    /**
     * Obtener el objeto que se encarga de la renderización del documento.
     *
     * @param string $render Clase del renderizador que se debe utilizar.
     * @return AbstractRenderer
     */
    private function getRendererInstance(string $renderer): AbstractRenderer
    {
        // Si no existe el renderizador se crea.
        if (!isset($this->renderers[$renderer])) {
            $this->renderers[$renderer] = new $renderer($this->dataProvider);
        }

        // Entregar el renderizador solicitado.
        return $this->renderers[$renderer];
    }

    /**
     * Determina la clase del renderer que se está solicitando.
     *
     * @param string $renderer Nombre del renderer solicitado.
     * @return string FQCN de la clase del renderer solicitado.
     */
    private function getRendererClass(string $renderer): string
    {
        // Determinar nombre del archivo PHP y de la clase.
        $class = Str::studly($renderer) . 'Renderer';
        $file = __DIR__ . '/' . $class . '.php';

        // La clase existe en el namespace actual.
        if (file_exists($file)) {
            $class = __NAMESPACE__ . '\\' . $class;
        }
        // La clase podría existir en lib-pro.
        else {
            $class = str_replace('\\Core\\', '\\Pro\\', __NAMESPACE__)
                . '\\' . $class
            ;
        }

        // Entregar el FQCN de la clase del renderer buscado.
        return $class;
    }
}

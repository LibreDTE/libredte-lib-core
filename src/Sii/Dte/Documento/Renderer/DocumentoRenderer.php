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
        // Opciones por defecto para el renderizado.
        $options = array_merge([
            'renderer' => $this->defaultRenderer,
        ], $options);

        // Crear el renderizador de los datos del DTE.
        $renderer = $this->getRenderer($options['renderer']);

        // Renderizar el documento.
        $data = $renderer->render($documento, $options);

        // Entregar los datos renderizados.
        return $data;
    }

    /**
     * Obtener el objeto que se encarga de la renderización del documento.
     *
     * @param string $render Clase del renderizador que se debe utilizar.
     * @return AbstractRenderer
     */
    private function getRenderer(string $renderer): AbstractRenderer
    {
        // Si no existe el renderizador se crea.
        if (!isset($this->renderers[$renderer])) {
            $this->renderers[$renderer] = new $renderer($this->dataProvider);
        }

        // Entregar el renderizador solicitado.
        return $this->renderers[$renderer];
    }
}

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
use libredte\lib\Core\Service\RendererTemplateService;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use LogicException;

/**
 * Clase abstracta (base) para los renderizadores de documentos tributarios
 * electrónicos (DTE).
 */
abstract class AbstractRenderer implements RendererInterface
{
    /**
     * Plantilla por defecto que se debe utilizar al renderizar el DTE.
     *
     * @var string
     */
    protected string $defaultTemplate;

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Servicio de renderizado de plantillas.
     *
     * @var RendererTemplateService
     */
    private RendererTemplateService $rendererService;

    /**
     * Constructor de la clase.
     *
     * @param ?DataProviderInterface $dataProvider Proveedor de datos.
     * @param ?RendererTemplateService $rendererService Proveedor de datos.
     */
    public function __construct(
        ?DataProviderInterface $dataProvider = null,
        ?RendererTemplateService $rendererService = null
    ) {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
        $this->rendererService = $rendererService ?? new RendererTemplateService();
    }

    /**
     * Renderiza el documento en el formato solicitado (HTML o PDF).
     *
     * @param AbstractDocumento $documento Documento a renderizar.
     * @param array $options Opciones para el renderizado.
     * @return string Datos del documento renderizado.
     */
    public function render(
        AbstractDocumento $documento,
        array $options = []
    ): string {
        // Plantilla que se renderizará.
        $template = $options['template'] ?? $this->getDefaultTemplate();
        if ($template[0] !== '/') {
            $template = 'dte/documento/' . $template;
        }

        // Datos que se usarán para renderizar.
        $data = $this->createData($documento, $options);

        // Renderizar el documento.
        $rendered = $this->rendererService->render($template, $data);

        // Entregar el contenido renderizado del documento.
        return $rendered;
    }

    /**
     * Crea los datos que se pasarán a la plantilla que se renderizará.
     *
     * @param AbstractDocumento $documento Documento a renderizar.
     * @param array $options Opciones para el renderizado.
     * @return array Datos que se pasarán a la plantilla al renderizar.
     */
    protected function createData(
        AbstractDocumento $documento,
        array $options = []
    ): array {
        // Preparar datos que se usarán para renderizar.
        $data = [
            'dte' => $documento->getData(),
            'options' => [
                'format' => $options['format'] ?? null,
                'config' => [
                    'html' => $options['html'] ?? null,
                    'pdf' => $options['pdf'] ?? null,
                ],
            ]
        ];

        // Entregar los datos que se pasarán a la plantilla.
        return $data;
    }

    /**
     * Entrega la plantilla por defecto asociada al renderizador del DTE.
     *
     * @return string Nombre de la plantilla por defecto.
     * @throws LogicException Si no existe una plantilla por defecto asignada en
     * el renderizador.
     */
    private function getDefaultTemplate(): string
    {
        if (!isset($this->defaultTemplate)) {
            throw new LogicException(
                'No se ha asignado una plantilla por defecto para el renderizado del DTE.'
            );
        }

        return $this->defaultTemplate;
    }
}

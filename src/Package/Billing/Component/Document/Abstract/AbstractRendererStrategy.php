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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Renderer\Contract\RendererInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererStrategyInterface;

/**
 * Clase abstracta (base) para las estrategias de renderizado de documentos
 * tributarios utilizando plantillas.
 */
abstract class AbstractRendererStrategy extends AbstractStrategy implements RendererStrategyInterface
{
    public function __construct(
        private RendererInterface $renderer
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function render(DocumentBagInterface $bag): string
    {
        [$data, $options] = $this->createDataAndOptions($bag);

        return $this->renderer->render(
            $options['filepath'],
            $data,
            $options
        );
    }

    /**
     * Crea los datos que se pasarán a la plantilla que se renderizará.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos del documento a
     * renderizar.
     * @return array Datos que se pasarán a la plantilla al renderizar.
     */
    protected function createDataAndOptions(DocumentBagInterface $bag): array
    {
        $options = $this->resolveOptions($bag->getRendererOptions());

        // Preparar datos que se usarán para renderizar.
        $data = [
            'document' => $bag->getDocumentData(),
            'document_extra' => $bag->getDocumentExtra(),
            'document_stamp' => $bag->getDocumentStamp(),
            'document_auth' => $bag->getDocumentAuth(),
        ];

        $options = [
            'template' => $options->get('template'),
            'filepath' => null,
            'format' => $options->get('format'),
            'config' => [
                'html' => $options->get('html', [])->all(),
                'pdf' => $options->get('pdf', [])->all(),
            ],
        ];

        // Asignar la ubicación de la plantilla.
        if ($options['template'][0] === '/') {
            $options['filepath'] = $options['template'];
            $options['template'] = basename($options['template']);
        } else {
            $base = 'billing/document/renderer/';
            $options['filepath'] = $base . $options['template'];
        }

        // Entregar los datos que se pasarán a la plantilla y las opciones.
        return [$data, $options];
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Config\Contract\OptionsInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TipoPresentacion;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;
use libredte\lib\Core\Package\System\Component\Rendering\Contract\RenderResultInterface;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderedDocument;
use libredte\lib\Core\Package\System\Component\Rendering\Support\RenderResult;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * Clase para los renderizadores.
 */
#[Worker(name: 'renderer', component: 'document', package: 'billing')]
class RendererWorker extends AbstractWorker implements RendererWorkerInterface
{
    use StrategiesAwareTrait;

    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        iterable $strategies = []
    ) {
        $this->setStrategies($strategies);
    }

    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        '__allowUndefinedKeys' => true,
        'strategy' => [
            'types' => 'string',
            'default' => 'template.estandar',
        ],
        'format' => [
            'types' => 'string',
            'default' => 'pdf',
        ],
        'renderings' => [
            'types' => 'array',
            'default' => [
                TipoPresentacion::TRIBUTARIA->value => 1,
            ],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'bag' => [
                'example' => [
                    'xmlDocument' => '',
                    'options' => [
                        'renderer' => [
                            'format' => 'pdf',
                            'renderings' => [
                                'tributaria' => 1,
                                'cedible' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ]
    )]
    public function render(DocumentBagInterface $bag): RenderResultInterface
    {
        $options = $this->resolveOptions($bag->getRendererOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

        assert($strategy instanceof RendererStrategyInterface);

        $bag = $this->documentBagManager->normalize($bag, all: true);

        $format = $options->getReader()->requireString('format');
        $presentaciones = $this->resolvePresentaciones($options, $bag);

        if (empty($presentaciones)) {
            throw new RendererException(
                'Could not generate any rendering with the requested presentations.',
                documentBag: $bag
            );
        }

        $multiple = count($presentaciones) > 1;
        $mimeType = $this->resolveMimeType($format);

        $renderings = [];
        foreach ($presentaciones as $label => $copies) {
            $renderings[] = new RenderedDocument(
                content: $this->renderPresentacion($strategy, $bag, $label),
                mimeType: $mimeType,
                filename: $multiple
                    ? sprintf('%s_%s.%s', $bag->getId(), $label, $format)
                    : sprintf('%s.%s', $bag->getId(), $format),
                label: $label,
                copies: $copies,
            );
        }

        return new RenderResult(...$renderings);
    }

    /**
     * Resuelve qué presentaciones renderizar y cuántas copias de cada una,
     * a partir de la opción `renderer.renderings` de la bolsa.
     *
     * Una presentación `cedible` solicitada para un tipo de documento que no
     * admite acuse de recibo se omite en silencio (no es un error: es una
     * combinación válida que simplemente no genera nada para esa
     * presentación).
     *
     * @param OptionsInterface $options Opciones ya resueltas del worker.
     * @param DocumentBagInterface $bag Bolsa (ya normalizada) del documento.
     * @return array<string,int> Cantidad de copias por presentación
     * (`TipoPresentacion::value`), solo las que sí se van a renderizar.
     * @throws RendererException Si se solicita una presentación que no
     * corresponde a ningún caso de `TipoPresentacion`.
     */
    private function resolvePresentaciones(
        OptionsInterface $options,
        DocumentBagInterface $bag
    ): array {
        $renderings = $options->get('renderings');
        $renderings = $renderings instanceof OptionsInterface
            ? $renderings->all()
            : (array) $renderings;

        $documentType = $bag->getDocumentType();

        $presentaciones = [];
        foreach ($renderings as $key => $copies) {
            $copies = (int) $copies;
            if ($copies < 1) {
                continue;
            }

            $presentacion = TipoPresentacion::tryFrom((string) $key);
            if ($presentacion === null) {
                throw new RendererException([
                    'Rendering presentation "{presentation}" does not exist.',
                    'presentation' => $key,
                ], documentBag: $bag);
            }

            if (
                $presentacion === TipoPresentacion::CEDIBLE
                && !$documentType?->requiresAcuseRecibo()
            ) {
                continue;
            }

            $presentaciones[$presentacion->value] = $copies;
        }

        return $presentaciones;
    }

    /**
     * Renderiza el documento con una presentación específica.
     *
     * Le indica a la estrategia (y, a través de ella, a la plantilla) qué
     * presentación renderizar vía `bag.options.renderer.presentation`,
     * dejando el valor solo mientras dura esta llamada.
     *
     * @param RendererStrategyInterface $strategy
     * @param DocumentBagInterface $bag Bolsa (ya normalizada) del documento.
     * @param string $presentacion Valor de `TipoPresentacion` a renderizar.
     * @return string
     * @throws RendererException Si la estrategia de renderizado falla.
     */
    private function renderPresentacion(
        RendererStrategyInterface $strategy,
        DocumentBagInterface $bag,
        string $presentacion
    ): string {
        $bag->getOptions()->set('renderer.presentation', $presentacion);

        try {
            return $strategy->render($bag);
        } catch (Throwable $e) {
            throw new RendererException(
                message: [
                    'Could not render the document: {detail}',
                    'detail' => $e,
                ],
                documentBag: $bag,
                previous: $e
            );
        } finally {
            $bag->getOptions()->clear('renderer.presentation');
        }
    }

    /**
     * Resuelve el tipo MIME correspondiente al formato de renderizado
     * solicitado (ej. `pdf` => `application/pdf`).
     *
     * @param string $format Formato de renderizado (ej. `pdf`, `html`).
     * @return string
     */
    private function resolveMimeType(string $format): string
    {
        $mimeTypes = MimeTypes::getDefault()->getMimeTypes($format);

        return $mimeTypes[0] ?? 'application/octet-stream';
    }
}

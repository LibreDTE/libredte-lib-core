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
use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Value\ValueCaster;
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

    /**
     * Constructor del worker y sus dependencias.
     *
     * @param DocumentBagManagerWorkerInterface $documentBagManager
     * @param iterable<array-key, StrategyInterface> $strategies
     */
    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        iterable $strategies = []
    ) {
        $this->setStrategies($strategies);
    }

    /**
     * Esquema de las opciones.
     *
     * @var array<string, array<string, mixed>|bool>
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
        name: 'render',
        summary: 'Genera la representación impresa de un documento',
        description: <<<'MD'
            Genera la representación impresa (el PDF) de un documento tributario electrónico a partir de su XML.

            ## Cómo se usa

            Se envía en `bag.xmlDocument` el XML del documento codificado en base64, por ejemplo el que entrega `billing.document.builder::build` en `document_xml`. Por defecto se genera una sola presentación, la `tributaria`. Con `bag.options.renderer.renderings` se piden otras y más de una copia de cada una.

            ## Presentaciones

            | Valor | Descripción |
            |---|---|
            | `tributaria` | Copia tributaria, sin la leyenda "CEDIBLE" ni el bloque de acuse de recibo. |
            | `cedible` | Copia cedible, con la leyenda "CEDIBLE" y el bloque de acuse de recibo (Ley 19.983). Solo para los tipos de documento que lo admiten: si se pide para uno que no lo admite (por ejemplo, una boleta), se omite sin error y no aparece en la respuesta. |

            `renderings` es un objeto con la presentación como clave y la cantidad de copias como valor, por ejemplo `{"tributaria": 1, "cedible": 2}`. Una presentación que no existe hace fallar la operación.

            La respuesta trae un archivo por cada copia solicitada. Cuando se piden varias copias de una presentación, el archivo se repite con `copy_number` desde `1` y el nombre del archivo termina en `_1`, `_2`, etc.
            MD,
        parameters: [
            'bag' => [
                'description' => <<<'MD'
                    Datos del documento y opciones del renderizado.

                    | Campo | Descripción |
                    |---|---|
                    | `xmlDocument` | Obligatorio. XML del documento, codificado en base64. |
                    | `options` | Opciones. `options.renderer.format` es el formato de salida; por defecto `pdf`. `options.renderer.renderings` son las presentaciones y la cantidad de copias de cada una; por defecto `{"tributaria": 1}`. `options.renderer.strategy` es el motor de renderizado; por defecto `template.estandar`. |
                    MD,
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
        ],
        results: [
            'success' => [
                'description' => 'Archivos generados, uno por cada copia solicitada. `content` es el archivo codificado en base64, `mime_type` su tipo, `filename` su nombre sugerido, `label` la presentación, `copies` la cantidad de copias de esa presentación y `copy_number` el número de esta copia.',
                'example' => [
                    'renderings' => [
                        [
                            'content' => 'JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBlIC9QYWdl...',
                            'mime_type' => 'application/pdf',
                            'filename' => 'LibreDTE_76192083-9_T033F000000000001_tributaria.pdf',
                            'label' => 'tributaria',
                            'copies' => 1,
                            'copy_number' => 1,
                        ],
                        [
                            'content' => 'JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBlIC9QYWdl...',
                            'mime_type' => 'application/pdf',
                            'filename' => 'LibreDTE_76192083-9_T033F000000000001_cedible.pdf',
                            'label' => 'cedible',
                            'copies' => 1,
                            'copy_number' => 1,
                        ],
                    ],
                ],
            ],
        ],
    )]
    public function render(DocumentBagInterface $bag): RenderResultInterface
    {
        $options = $this->resolveOptions($bag->getRendererOptions());
        $strategy = $this->getStrategy($options->getReader()->requireString('strategy'));

        $strategy = ValueCaster::requireObject($strategy, RendererStrategyInterface::class);

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
            $copies = ValueCaster::toInt($copies) ?? 0;
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

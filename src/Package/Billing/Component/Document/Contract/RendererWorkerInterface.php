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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Backbone\Contract\StrategiesAwareInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Backbone\Exception\StrategyException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentBagManagerException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;
use libredte\lib\Core\Package\System\Component\Rendering\Contract\RenderResultInterface;

/**
 * Interfaz para los renderizadores.
 */
interface RendererWorkerInterface extends WorkerInterface, StrategiesAwareInterface
{
    /**
     * Realiza el renderizado del documento.
     *
     * Por defecto genera una sola presentación (`tributaria`). Se puede
     * pedir más de una, y más de una copia de cada una, con la opción
     * `bag.options.renderer.renderings` (ej. `['tributaria' => 2, 'cedible'
     * => 1]`). Una copia `cedible` solicitada para un tipo de documento que
     * no admite acuse de recibo (ver
     * `TipoDocumentoInterface::requiresAcuseRecibo()`) se omite en
     * silencio: no es un error, simplemente esa presentación no aparece en
     * el resultado.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos del documento a
     * renderizar.
     * @return RenderResultInterface Resultado del renderizado (el o los
     * archivos generados, ej. el PDF).
     * @throws RendererException Si la estrategia de renderizado falla, si se
     * solicita una presentación de renderizado que no existe, o si ninguna
     * de las presentaciones solicitadas pudo generarse.
     * @throws DocumentBagManagerException Si no se determina un tipo de
     * documento tributario válido para el documento de la bolsa.
     * @throws StrategyException Si no existe una estrategia de renderizado
     * registrada para la solicitada.
     */
    public function render(DocumentBagInterface $bag): RenderResultInterface;
}

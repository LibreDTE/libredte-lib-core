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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Backbone\Contract\StrategiesAwareInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Backbone\Exception\StrategyException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentBagManagerException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\RendererException;

/**
 * Interfaz para los renderizadores.
 */
interface RendererWorkerInterface extends WorkerInterface, StrategiesAwareInterface
{
    /**
     * Realiza el renderizado del documento.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos del documento a
     * renderizar.
     * @return string Datos binarios del renderizado (ej. el PDF).
     * @throws RendererException Si la estrategia de renderizado falla.
     * @throws DocumentBagManagerException Si no se determina un tipo de
     * documento tributario válido para el documento de la bolsa.
     * @throws StrategyException Si no existe una estrategia de renderizado
     * registrada para la solicitada.
     */
    public function render(DocumentBagInterface $bag): string;
}

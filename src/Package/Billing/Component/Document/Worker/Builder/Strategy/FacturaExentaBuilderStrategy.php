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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Builder\Strategy;

use Derafu\Backbone\Attribute\Strategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Builder\Strategy\FacturaExentaBuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Document\FacturaExenta;

/**
 * Constructor ("builder") del documento factura exenta.
 */
#[Strategy(name: 'factura_exenta', worker: 'builder', component: 'document', package: 'billing')]
class FacturaExentaBuilderStrategy extends AbstractBuilderStrategy implements FacturaExentaBuilderStrategyInterface
{
    /**
     * Clase del documento que este "builder" construirá.
     *
     * @var string
     */
    protected string $documentClass = FacturaExenta::class;
}

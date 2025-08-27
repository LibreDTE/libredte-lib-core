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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Handler;

use Derafu\Backbone\Attribute\Handler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;

/**
 * Handler que realiza la recepción de los documentos mediante correo
 * electrónico.
 *
 * La recepción por correo solo se realiza si en la bolsa están los
 * datos/opciones para poder ejecutar una estrategia de recepción de correo.
 */
#[Handler(name: 'email_receiver', worker: 'receiver', component: 'exchange', package: 'billing')]
class EmailReceiverHandler extends AbstractReceiverHandler implements ExchangeHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    protected function hasRequiredData(ExchangeBagInterface $bag): bool
    {
        // Se necesitan los datos de transporte.
        if (!$bag->getOptions()->get('transport')) {
            return false;
        }

        // Nota: Cada estrategia deberá validar "qué" datos de transporte
        // específicos necesita en el método canReceive().

        // En cualquier otro caso indicar que están los datos mínimos.
        return true;
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Handler;

use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeHandlerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;

/**
 * Handler que realiza el envío de los documentos mediante correo electrónico.
 *
 * El envío por correo solo se realiza si corresponde enviar lo que la bolsa con
 * los sobres tiene y solo si están los datos/opciones para hacerlo por correo.
 */
class EmailSenderHandler extends AbstractSenderHandler implements ExchangeHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    protected function shouldProcess(EnvelopeInterface $envelope): bool
    {
        // Revisar que los documentos que están en el sobre sean válidos para
        // los procesos que pueden enviar correo electrónico.
        $processes = [
            ProcessType::QUOTING,
            ProcessType::FULFILLMENT,
            ProcessType::BILLING,
            ProcessType::PAYMENT,
        ];
        $validForProcess = false;
        foreach ($processes as $process) {
            if ($envelope->getDocumentType()->isValidForProcess($process)) {
                $validForProcess = true;
                break;
            }
        }
        if (!$validForProcess) {
            return false;
        }

        // En cualquier otro caso indicar que se puede procesar el sobre.
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function hasRequiredData(EnvelopeInterface $envelope): bool
    {
        // Se necesitan los datos de transporte.
        if (!$envelope->getMetadata()->get('transport')) {
            return false;
        }

        // Nota: Cada estrategia deberá validar "qué" datos de transporte
        // específicos necesita en el método canSend().

        // En cualquier otro caso indicar que están los datos mínimos.
        return true;
    }
}

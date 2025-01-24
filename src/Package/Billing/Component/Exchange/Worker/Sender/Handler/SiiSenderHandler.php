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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Handler que realiza el envío de los documentos al SII.
 *
 * El envío al SII solo se realiza si corresponde enviar al SII lo que la bolsa
 * con los sobres tiene y solo si están los datos/opciones para hacerlo.
 */
class SiiSenderHandler extends AbstractSenderHandler implements ExchangeHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    protected function shouldProcess(EnvelopeInterface $envelope): bool
    {
        // Si el proceso asociado al sobre no es de reporte fiscal no se puede
        // procesar el sobre.
        if ($envelope->getProcess() !== ProcessType::REPORTING) {
            return false;
        }

        // Si los documentos que están en el sobre no son válidos para reportes
        // no se procesa.
        if (!$envelope->getDocumentType()->isValidForProcess(ProcessType::REPORTING)) {
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
        // Si no hay firma electrónica no se podrá hacer el envío al SII.
        if (!$envelope->getMetadata()->get('certificate')) {
            return false;
        }

        // Si el que envía el sobre no está definido o no es una instancia de
        // EmisorInterface no se puede hacer el envío.
        $sender = $envelope->getMetadata()->get('sender');
        if (!$sender || !($sender instanceof EmisorInterface)) {
            return false;
        }

        // Si el que envía el sobre no tiene asignados los datos de autorización
        // de DTE (fecha y número resolución) no se puede hacer el envío.
        if (!$sender->getAutorizacionDte()) {
            return false;
        }

        // En cualquier otro caso indicar que están los datos mínimos.
        return true;
    }
}

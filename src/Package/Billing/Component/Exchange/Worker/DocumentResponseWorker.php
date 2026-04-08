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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\DocumentResponseWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\RespuestaEnvio;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\DocumentResponseException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildEnvioRecibosJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\BuildRespuestaEnvioJob;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job\ValidateJob;
use Throwable;

/**
 * Worker "billing.exchange.document_response".
 *
 * Genera los XML de respuesta al intercambio de DTE:
 *   - `EnvioRecibos`: recibo de mercaderías o servicios (acción ERM).
 *   - `RespuestaDTE`: acuse de recibo del envío o resultado de validación.
 */
#[Worker(name: 'document_response', component: 'exchange', package: 'billing')]
class DocumentResponseWorker extends AbstractWorker implements DocumentResponseWorkerInterface
{
    public function __construct(
        private BuildEnvioRecibosJob $buildEnvioRecibosJob,
        private BuildRespuestaEnvioJob $buildRespuestaEnvioJob,
        private ValidateJob $validateJob,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function buildEnvioRecibos(ExchangeDocumentBag $bag): EnvioRecibos
    {
        try {
            $document = $this->buildEnvioRecibosJob->build($bag);
            $bag->setDocument($document);

            return $document;
        } catch (Throwable $e) {
            throw new DocumentResponseException(
                message: sprintf(
                    'No fue posible construir el EnvioRecibos: %s',
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildRespuestaEnvio(ExchangeDocumentBag $bag): RespuestaEnvio
    {
        try {
            $document = $this->buildRespuestaEnvioJob->build($bag);
            $bag->setDocument($document);

            return $document;
        } catch (Throwable $e) {
            throw new DocumentResponseException(
                message: sprintf(
                    'No fue posible construir el RespuestaDTE: %s',
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate(AbstractExchangeDocument $document): bool
    {
        try {
            return $this->validateJob->validate($document);
        } catch (DocumentResponseException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new DocumentResponseException(
                message: sprintf(
                    'No fue posible validar el documento de respuesta: %s',
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }
}

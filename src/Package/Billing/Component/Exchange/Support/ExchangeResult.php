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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Support;

use Derafu\Lib\Core\Support\Store\Bag;
use Derafu\Lib\Core\Support\Store\Contract\BagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeResultInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeStatusInterface;

/**
 * Clase que representan un resultado de intercambio de sobres.
 *
 * Este resultado está asociado a un único sobre, pero puede tener como
 * resultado múltiples estados. Loanterior ocurre porque un sobre puede haber
 * sido procesado por más de una estrategia y cada una asigna un estado.
 */
class ExchangeResult implements ExchangeResultInterface
{
    /**
     * Sobre al que está asociado el resultado.
     *
     * @var EnvelopeInterface
     */
    private EnvelopeInterface $envelope;

    /**
     * Listado de resultados de las estrategias que procesaron el sobre.
     *
     * @var array<string, ExchangeStatusInterface>
     */
    private array $statuses;

    /**
     * Metadatos del resultado.
     *
     * @var BagInterface
     */
    private BagInterface $metadata;

    /**
     * Constructor del resultado del intercambio de un sobre.
     *
     * @param EnvelopeInterface $envelope
     * @param BagInterface|array $metadata
     */
    public function __construct(
        EnvelopeInterface $envelope,
        BagInterface|array $metadata = []
    ) {
        $this->envelope = $envelope;
        $this->setMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvelope(): EnvelopeInterface
    {
        return $this->envelope;
    }

    /**
     * {@inheritDoc}
     */
    public function getStrategies(): array
    {
        return array_keys($this->statuses);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatuses(): array
    {
        return array_values($this->statuses);
    }

    /**
     * {@inheritDoc}
     */
    public function addStatus(ExchangeStatusInterface $status): static
    {
        $this->statuses[$status->getStrategy()] = $status;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMetadata(BagInterface|array $metadata): static
    {
        $this->metadata = is_array($metadata)
            ? new Bag($metadata)
            : $metadata
        ;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata->set($key, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata(): BagInterface
    {
        return $this->metadata;
    }
}

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
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeStatusInterface;
use Throwable;

/**
 * Clase que representa el estado del resultado de la ejecución de una
 * estrategia de intercambio.
 */
class ExchangeStatus implements ExchangeStatusInterface
{
    /**
     * Código de la estrategia a la que está asociado este estado.
     *
     * @var string
     */
    private string $strategy;

    /**
     * El error que la estrategia generó al ser ejecutada.
     *
     * Por ejemplo: una excepción lanzada.
     *
     * @var Throwable|null
     */
    private ?Throwable $error;

    /**
     * Metadatos del estado de resultado.
     *
     * @var BagInterface
     */
    private BagInterface $metadata;

    /**
     * Constructor del estado de la estrategia.
     *
     * @param string $strategy
     * @param Throwable|null $error
     * @param BagInterface|array $metadata
     */
    public function __construct(
        string $strategy,
        ?Throwable $error = null,
        BagInterface|array $metadata = []
    ) {
        $this->strategy = $strategy;
        $this->error = $error;
        $this->setMetadata($metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * {@inheritDoc}
     */
    public function isOk(): bool
    {
        return !$this->hasError();
    }

    /**
     * {@inheritDoc}
     */
    public function setError(Throwable $error): static
    {
        $this->error = $error;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function hasError(): bool
    {
        return isset($this->error);
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

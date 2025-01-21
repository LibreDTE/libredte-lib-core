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

namespace libredte\lib\Core\Package\Billing\Component\Identifier;

use Derafu\Lib\Core\Foundation\Abstract\AbstractComponent;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafProviderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\IdentifierComponentInterface;

/**
 * Componente "billing.identifier".
 */
class IdentifierComponent extends AbstractComponent implements IdentifierComponentInterface
{
    public function __construct(
        private CafFakerWorkerInterface $cafFaker,
        private CafLoaderWorkerInterface $cafLoader,
        private CafProviderWorkerInterface $cafProvider,
        private CafValidatorWorkerInterface $cafValidator
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'caf_faker' => $this->cafFaker,
            'caf_loader' => $this->cafLoader,
            'caf_provider' => $this->cafProvider,
            'caf_validator' => $this->cafValidator,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCafFakerWorker(): CafFakerWorkerInterface
    {
        return $this->cafFaker;
    }

    /**
     * {@inheritDoc}
     */
    public function getCafLoaderWorker(): CafLoaderWorkerInterface
    {
        return $this->cafLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function getCafProviderWorker(): CafProviderWorkerInterface
    {
        return $this->cafProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getCafValidatorWorker(): CafValidatorWorkerInterface
    {
        return $this->cafValidator;
    }
}

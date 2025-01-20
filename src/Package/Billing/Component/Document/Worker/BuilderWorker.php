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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;

/**
 * Clase para los constructores de documentos.
 */
class BuilderWorker extends AbstractWorker implements BuilderWorkerInterface
{
    public function __construct(
        private DocumentBagManagerWorkerInterface $documentBagManager,
        iterable $jobs = [],
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        parent::__construct(
            jobs: $jobs,
            handlers: $handlers,
            strategies: $strategies
        );
    }

    /**
     * {@inheritDoc}
     */
    public function create(DocumentBagInterface $bag): DocumentInterface
    {
        // Buscar la estrategia para crear el documento tributario.
        $strategy = $this->getStrategy(
            $bag->getTipoDocumento()->getAlias()
        );
        assert($strategy instanceof BuilderStrategyInterface);

        // Construir el documento usando la estrategia.
        $document = $strategy->create($bag->getXmlDocument());

        // Entregar el DTE construído.
        return $document;
    }

    /**
     * {@inheritDoc}
     */
    public function build(DocumentBagInterface $bag): DocumentInterface
    {
        // Normalizar la bolsa con los datos del documento.
        // Acá no se puede normalizar todo, solo lo necesario. El resto de la
        // normalización (todo) se debe hacer al final de este método antes de
        // retornar. Esto es así porque para hacer la normalización completa se
        // requiere que el DTE esté creado.
        $bag = $this->documentBagManager->normalize($bag);

        // Buscar la estrategia para construir el documento tributario.
        $strategy = $this->getStrategy($bag->getAlias());
        assert($strategy instanceof BuilderStrategyInterface);

        // Construir el documento usando la estrategia.
        $document = $strategy->build($bag);

        // Normalizar la bolsa con los datos actualizados del documento.
        $bag = $this->documentBagManager->normalize($bag, all: true);

        // Entregar el DTE construído.
        return $document;
    }
}

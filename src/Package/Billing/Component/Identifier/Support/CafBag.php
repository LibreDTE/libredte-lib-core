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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Support;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafBagInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Contenedor de datos del archivo CAF de un documento tributario electrónico.
 *
 * Permite "mover" un CAF, junto a otros datos asociados, por métodos de
 * manera sencilla y, sobre todo, extensible.
 */
class CafBag implements CafBagInterface
{
    public function __construct(
        private readonly CafInterface $caf,
        private readonly EmisorInterface $emisor,
        private readonly TipoDocumentoInterface $tipoDocumento
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getCaf(): CafInterface
    {
        return $this->caf;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmisor(): EmisorInterface
    {
        return $this->emisor;
    }

    /**
     * {@inheritdoc}
     */
    public function getTipoDocumento(): TipoDocumentoInterface
    {
        return $this->tipoDocumento;
    }
}

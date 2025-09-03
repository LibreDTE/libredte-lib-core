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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Contract;

use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Interfaz para la bolsa con los datos del CAF.
 */
interface CafBagInterface extends JsonSerializable
{
    /**
     * Obtiene el CAF.
     *
     * @return CafInterface
     */
    public function getCaf(): CafInterface;

    /**
     * Obtiene el contribuyente emisor del CAF.
     *
     * @return EmisorInterface
     */
    public function getEmisor(): EmisorInterface;

    /**
     * Obtiene el tipo de documento del CAF.
     *
     * @return TipoDocumentoInterface
     */
    public function getTipoDocumento(): TipoDocumentoInterface;

    /**
     * Asigna el listado de folios disponibles en el CAF.
     *
     * @param array $foliosDisponibles
     * @return static
     */
    public function setFoliosDisponibles(array $foliosDisponibles): static;

    /**
     * Obtiene el listado de folios disponibles del CAF.
     *
     * @return array
     */
    public function getFoliosDisponibles(): array;

    /**
     * Entrega el siguiente folio disponible que se puede utilizar en el CAF.
     *
     * @return int
     */
    public function getSiguienteFolio(): int;

    /**
     * Entrega los datos de la bolsa como un arreglo.
     *
     * @return array
     */
    public function toArray(): array;
}

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

use Derafu\Lib\Core\Foundation\Contract\WorkerInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Interfaz para el worker que crea archivos CAF falsos (de prueba).
 */
interface CafFakerWorkerInterface extends WorkerInterface
{
    /**
     * Genera y devuelve un CAF (Código de Autorización de Folios) ficticio para
     * el mandatario.
     *
     * @param EmisorInterface $emisor Emisor al que se creará el CAF falso.
     * @param int $codigoDocumento Código del tipo de documento.
     * @param int|null $folioDesde Número de folio inicial.
     * @param int|null $folioHasta Número de folio final. Si es `null`, se usa
     * el mismo valor de $folioDesde.
     * @return CafBagInterface CAF ficticio generado para el contribuyente.
     */
    public function create(
        EmisorInterface $emisor,
        int $codigoDocumento,
        ?int $folioDesde = 1,
        ?int $folioHasta = null
    ): CafBagInterface;
}

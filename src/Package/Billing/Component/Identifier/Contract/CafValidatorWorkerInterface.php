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
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafValidatorException;

/**
 * Interfaz para el worker que permite validar los CAF.
 */
interface CafValidatorWorkerInterface extends WorkerInterface
{
    /**
     * Método que valida el código de autorización de folios (CAF).
     *
     * Valida la firma y las claves públicas y privadas asociadas al CAF.
     *
     * @param CafInterface $caf Instancia del CAF a validar.
     * @throws CafValidatorException En caso de algún problema al validar.
     */
    public function validate(CafInterface $caf): void;
}

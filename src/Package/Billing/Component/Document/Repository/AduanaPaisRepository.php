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

namespace libredte\lib\Core\Package\Billing\Component\Document\Repository;

use Derafu\Repository\Repository;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\AduanaPais;

/**
 * Repositorio para trabajar con los países de aduana.
 */
class AduanaPaisRepository extends Repository
{
    /**
     * Entrega el país de aduana que tiene el código ISO 3166-1 alpha-2
     * indicado (el que usa UBL en `Country/IdentificationCode`).
     *
     * El código se compara sin distinguir mayúsculas y minúsculas. Si el país
     * no existe en el SII, o el SII no tiene un código para él, se entrega
     * `null`.
     *
     * @param string $codigoISO Código ISO 3166-1 alpha-2 (por ejemplo, `DE`).
     * @return AduanaPais|null
     */
    public function findOneByCodigoISO(string $codigoISO): ?AduanaPais
    {
        $codigoISO = mb_strtoupper(trim($codigoISO), 'UTF-8');

        if (preg_match('/^[A-Z]{2}$/', $codigoISO) !== 1) {
            return null;
        }

        $pais = $this->findOneBy(['codigo_iso' => $codigoISO]);

        return $pais instanceof AduanaPais ? $pais : null;
    }
}

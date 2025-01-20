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

use Derafu\Lib\Core\Package\Prime\Component\Entity\Repository\Repository;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\ImpuestoAdicionalRetencion;

/**
 * Repositorio para trabajar con los impuestos adicionales y retenciones.
 */
class ImpuestoAdicionalRetencionRepository extends Repository
{
    /**
     * {@inheritDoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null): ImpuestoAdicionalRetencion
    {
        // Buscar la entidad solicitada.
        $entity = parent::find($id, $lockMode, $lockVersion);

        // Si la entidad existe se retorna directamente.
        if ($entity) {
            return $entity;
        }

        // Si no existe se entregará una entidad "falsa" que sirve para mostrar
        // datos de prueba con menor control dónde se llame a find().
        return $this->createEntity([
            'codigo' => $id,
        ]);
    }
}

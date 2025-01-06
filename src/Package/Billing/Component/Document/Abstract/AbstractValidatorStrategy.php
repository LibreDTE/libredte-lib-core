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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use Derafu\Lib\Core\Helper\Rut;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorStrategyInterface;

/**
 * Clase abstracta (base) para las estrategias de validación de
 * documentos tributarios.
 */
abstract class AbstractValidatorStrategy extends AbstractStrategy implements ValidatorStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Validar los RUTs.
        Rut::validate($data['Encabezado']['Emisor']['RUTEmisor']);
        Rut::validate($data['Encabezado']['Receptor']['RUTRecep']);

        // Aplicar validación de la estrategias.
        $this->validateDocument($bag);
    }

    /**
     * Validación personalizada de cada estrategia.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    abstract protected function validateDocument(DocumentBagInterface $bag): void;
}

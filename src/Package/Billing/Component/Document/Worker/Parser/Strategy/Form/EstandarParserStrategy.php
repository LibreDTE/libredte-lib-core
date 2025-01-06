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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Form;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;

/**
 * Estrategia "billing.document.parser.strategy:form.estandar".
 *
 * Transforma los datos recibidos a través de un formulario de la vista estándar
 * de emisión de DTE a un arreglo PHP con la estructura oficial del SII.
 */
class EstandarParserStrategy extends AbstractStrategy implements ParserStrategyInterface
{
    /**
     * Realiza la transformación de los datos del documento.
     *
     * @param string|array $data Datos de entrada del formulario.
     * @return array Arreglo transformado a la estructura oficial del SII.
     */
    public function parse(string|array $data): array
    {
        // Si los datos vienen como JSON se decodifican a arreglo.
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // TODO: Procesar los datos del formulario y crear el arreglo.
        $array = [];

        // Entregar el arreglo con los datos transformados.
        return $array;
    }
}

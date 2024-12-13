<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\Documento\Parser\Sii;

use libredte\lib\Core\Sii\Dte\Documento\Parser\DocumentoParserInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Transforma los datos en formato YAML con la estructura oficial del SII a un
 * arreglo PHP con la estructura oficial del SII.
 */
class YamlParser implements DocumentoParserInterface
{
    /**
     * Realiza la transformación de los datos del documento.
     *
     * @param string $data YAML con los datos de entrada.
     * @return array Arreglo transformado a la estructura oficial del SII.
     */
    public function parse(string $data): array
    {
        $array = Yaml::parse($data);

        return $array;
    }
}

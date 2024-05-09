<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Sii\Dte\Formatos;

/**
 * Clase que permite cargar los datos de un DTE desde un archivo en formato YAML.
 */
class YAML
{

    /**
     * Método que recibe los datos y los entrega como un arreglo PHP en el
     * formato del DTE que usa LibreDTE.
     */
    public static function toArray($data)
    {
        if (!function_exists('\yaml_parse')) {
            throw new \Exception('No hay soporte para YAML en PHP.');
        }
        if (empty($data)) {
            throw new \Exception('No hay datos que procesar en formato YAML.');
        }
        return \yaml_parse($data);
    }

}

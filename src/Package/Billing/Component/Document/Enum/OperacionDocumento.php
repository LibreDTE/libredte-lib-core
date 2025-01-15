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

namespace libredte\lib\Core\Package\Billing\Component\Document\Enum;

/**
 * Enum de la operación de los documentos en libros.
 */
enum OperacionDocumento: string
{
    case SUMA = 'S';
    case RESTA = 'R';

    /**
     * Mapa con los códigos de la operación y su nombre.
     */
    private const NOMBRES = [
        self::SUMA->value => 'Suma',
        self::RESTA->value => 'Resta',
    ];

    /**
     * Entrega el código de la operación.
     *
     * @return string
     */
    public function getCodigo(): string
    {
        return $this->value;
    }

    /**
     * Entrega el nombre de la operación.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return self::NOMBRES[$this->value];
    }
}

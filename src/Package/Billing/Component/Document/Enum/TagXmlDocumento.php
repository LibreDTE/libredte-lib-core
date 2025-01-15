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
 * Enum del Tag XML donde va el documento (tag DTE).
 */
enum TagXmlDocumento: string
{
    case DOCUMENTO = 'Documento';
    case LIQUIDACION = 'Liquidacion';
    case EXPORTACIONES = 'Exportaciones';

    /**
     * Mapa con los códigos del tag XML y su nombre.
     */
    private const DESCRIPCIONES = [
        self::DOCUMENTO->value => 'Documento (todo menos DTE 43, 110, 111 ni 112)',
        self::LIQUIDACION->value => 'Liquidacion (DTE 43)',
        self::EXPORTACIONES->value => 'Exportaciones (DTE 110, 111 y 112)',
    ];

    /**
     * Entrega el nombre del tag XML.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return $this->value;
    }

    /**
     * Entrega la descripción del tag XML.
     *
     * @return string
     */
    public function getDescripcion(): string
    {
        return self::DESCRIPCIONES[$this->value];
    }
}

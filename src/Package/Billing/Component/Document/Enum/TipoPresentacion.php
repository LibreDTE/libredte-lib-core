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
 * Presentación con la que se renderiza un documento.
 *
 * Un mismo documento (mismos datos) puede renderizarse con distintas
 * presentaciones: no cambia su contenido tributario, solo cómo se ve (ej.
 * leyendas, marcas de agua o bloques adicionales impresos). No debe
 * confundirse con la estrategia de renderizado (ej. plantilla vs. TCPDF),
 * que decide *cómo* se genera el archivo, no *qué* se le agrega visualmente.
 */
enum TipoPresentacion: string
{
    /**
     * Copia tributaria: sin leyenda "CEDIBLE" ni bloque de acuse de recibo.
     */
    case TRIBUTARIA = 'tributaria';

    /**
     * Copia cedible: incluye la leyenda "CEDIBLE" y el bloque de acuse de
     * recibo (Ley 19.983), cuando el tipo de documento lo admite.
     *
     * @see \libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface::requiresAcuseRecibo()
     */
    case CEDIBLE = 'cedible';
}

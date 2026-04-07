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

namespace libredte\lib\Core\Package\Billing\Component\Book\Contract;

/**
 * Interfaz para el Resumen de Ventas Diarias (RVD).
 *
 * Anteriormente denominado Consumo de Folios (RCOF). Producido por
 * `ResumenVentasDiariasWorker`.
 *
 * El XML resultante usa el tag raíz `<ConsumoFolios>` por compatibilidad con
 * el esquema `ConsumoFolio_v10.xsd` del SII.
 */
interface ResumenVentasDiariasInterface extends BookInterface
{
    /**
     * Entrega la fecha del primer documento incluido en el registro.
     *
     * Se calcula automáticamente a partir del campo `FchDoc` de los detalles.
     *
     * @return string Fecha en formato `YYYY-MM-DD`.
     */
    public function getFechaInicial(): string;

    /**
     * Entrega la fecha del último documento incluido en el registro.
     *
     * Se calcula automáticamente a partir del campo `FchDoc` de los detalles.
     *
     * @return string Fecha en formato `YYYY-MM-DD`.
     */
    public function getFechaFinal(): string;

    /**
     * Entrega el número de secuencia del envío.
     *
     * Corresponde al campo `SecEnvio` de la carátula.
     *
     * @return int
     */
    public function getSecuencia(): int;
}

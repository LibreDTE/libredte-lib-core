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

namespace libredte\lib\Core\Package\Billing\Component\Book\Entity;

use libredte\lib\Core\Package\Billing\Component\Book\Abstract\AbstractBook;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\ResumenVentasDiariasInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;

/**
 * Entidad que representa el Resumen de Ventas Diarias (RVD).
 *
 * Anteriormente denominado Consumo de Folios (RCOF). El XML usa el tag raíz
 * `<ConsumoFolios>` por compatibilidad con el esquema `ConsumoFolio_v10.xsd`.
 */
class ResumenVentasDiarias extends AbstractBook implements ResumenVentasDiariasInterface
{
    /**
     * Tipo de libro.
     */
    protected TipoLibro $tipo = TipoLibro::RVD;

    /**
     * {@inheritDoc}
     *
     * El RVD usa `DocumentoConsumoFolios` en lugar de `EnvioLibro`.
     */
    public function getId(): string
    {
        return (string) ($this->xmlDocument->query('//DocumentoConsumoFolios/@ID') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaInicial(): string
    {
        return (string) ($this->xmlDocument->query('//Caratula/FchInicio') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaFinal(): string
    {
        return (string) ($this->xmlDocument->query('//Caratula/FchFinal') ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getSecuencia(): int
    {
        return (int) ($this->xmlDocument->query('//Caratula/SecEnvio') ?? 1);
    }
}

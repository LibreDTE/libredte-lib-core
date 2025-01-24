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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Entity;

use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyIdentifierInterface;

/**
 * Entidad para representar el identificador único de un participante.
 */
class PartyIdentifier implements PartyIdentifierInterface
{
    /**
     * Identificador del esquema del valor.
     *
     * @var string
     */
    protected string $schemeId;

    /**
     * Valor del identificador único del participante.
     *
     * @var string
     */
    protected string $value;

    /**
     * Mapa de identificadores de esquema a su nombre.
     *
     * @var array <string, string>
     */
    protected const SCHEME_NAMES = [
        'CL-RUT' => 'Rol Único Tributario (RUT) de Chile',
        'EMAIL' => 'Correo electrónico',
    ];

    /**
     * Mapa de identificadores de esquema a la autoridad que lo administra.
     *
     * @var array <string, string>
     */
    protected const AUTHORITIES = [
        'CL-RUT' => 'CL-SII',
        'EMAIL' => 'INTERNET',
    ];

    /**
     * Constructor del identificador único de un participante.
     *
     * @param string $value
     * @param string $schemeId
     */
    public function __construct(string $value, string $schemeId = 'CL-RUT')
    {
        $this->schemeId = $schemeId;
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->schemeId . ':' . $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemeId(): string
    {
        return $this->schemeId;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemeName(): string
    {
        return self::SCHEME_NAMES[$this->schemeId] ?? $this->schemeId;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthority(): string
    {
        return self::AUTHORITIES[$this->schemeId] ?? $this->schemeId;
    }
}

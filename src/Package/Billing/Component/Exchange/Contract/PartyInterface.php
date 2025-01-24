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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

use Symfony\Component\Mime\Address;

/**
 * Interfaz base para los participantes del intercambio.
 */
interface PartyInterface
{
    /**
     * Identificador único del participante.
     */
    public function getIdentifier(): PartyIdentifierInterface;

    /**
     * Agrega un punto de recepción de documentos al participante.
     *
     * @param PartyEndpointInterface $endpoint
     * @return static
     */
    public function addEndpoint(PartyEndpointInterface $endpoint): static;

    /**
     * Puntos de recepción de documentos soportados por el participante.
     *
     * @return PartyEndpointInterface[]
     */
    public function getEndpoints(): array;

    /**
     * Obtiene las direcciones de correo electrónico registradas como puntos de
     * recepción de documentos del participante.
     *
     * @return Address[]
     */
    public function getEmails(): array;
}

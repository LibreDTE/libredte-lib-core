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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Abstract;

use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyEndpointInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyIdentifierInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\PartyInterface;
use Symfony\Component\Mime\Address;

/**
 * Clase base para los participantes del intercambio de documentos.
 */
abstract class AbstractParty implements PartyInterface
{
    /**
     * Identificador único del participante.
     *
     * @var PartyIdentifierInterface
     */
    private PartyIdentifierInterface $identifier;

    /**
     * Listado de puntos de recepción de documentos del participante.
     *
     * @var PartyEndpointInterface[]
     */
    private array $endpoints;

    /**
     * Constructor del participante.
     *
     * @param PartyIdentifierInterface $identifier
     * @param PartyEndpointInterface[] $endpoints
     */
    public function __construct(
        PartyIdentifierInterface $identifier,
        array $endpoints = []
    ) {
        $this->identifier = $identifier;
        $this->endpoints = $endpoints;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): PartyIdentifierInterface
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function addEndpoint(PartyEndpointInterface $endpoint): static
    {
        $this->endpoints[] = $endpoint;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmails(): array
    {
        $emails = [];

        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->getSchemeId() === 'EMAIL') {
                $aux = explode(':', $endpoint->getValue(), 2);
                $emails[] = new Address($aux[0], $aux[1] ?? '');
            }
        }

        return $emails;
    }
}

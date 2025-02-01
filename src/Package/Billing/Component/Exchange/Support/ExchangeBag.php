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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Support;

use Derafu\Lib\Core\Common\Trait\OptionsAwareTrait;
use Derafu\Lib\Core\Support\Store\Contract\DataContainerInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeResultInterface;

/**
 * Clase que representa una bolsa con sobres con documentos para ser
 * intercambiada.
 *
 * Una bolsa podrá contener sobres de diferentes emisores o receptores, pero los
 * documentos dentro de cada sobre serán del mismo emisor y receptor.
 */
class ExchangeBag implements ExchangeBagInterface
{
    use OptionsAwareTrait;

    /**
     * Reglas de esquema de las opciones del intercambio de documentos.
     *
     * Acá solo se indicarán los índices que deben pueden existir en las
     * opciones. No se define el esquema de cada opción pues cada clase que
     * utilice estas opciones deberá resolver y validar sus propias opciones.
     *
     * @var array
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
        ],
        'transport' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * Listado de sobres que se están intercambiando en este lote.
     *
     * @var array<string, EnvelopeInterface>
     */
    private array $envelopes = [];

    /**
     * Listado con los resultados del intercambio.
     *
     * @var array<string, ExchangeResultInterface>
     */
    private array $results = [];

    /**
     * Constructor de la bolsa de intercambio.
     *
     * @param array $options
     */
    public function __construct(DataContainerInterface|array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function addEnvelope(EnvelopeInterface $envelope): static
    {
        $this->envelopes[$envelope->getBusinessMessageID()] = $envelope;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvelopes(): array
    {
        return array_values($this->envelopes);
    }

    /**
     * {@inheritDoc}
     */
    public function hasEnvelopes(): bool
    {
        return !empty($this->envelopes);
    }

    /**
     * {@inheritDoc}
     */
    public function getResults(): array
    {
        return array_values($this->results);
    }

    /**
     * {@inheritDoc}
     */
    public function addResult(ExchangeResultInterface $result): static
    {
        $envelope = $result->getEnvelope();

        $this->results[$envelope->getBusinessMessageID()] = $result;

        if (!isset($this->envelopes[$envelope->getBusinessMessageID()])) {
            $this->envelopes[$envelope->getBusinessMessageID()] = $envelope;
        }

        return $this;
    }
}

<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;

/**
 * Estrategia "billing.document.parser#strategy:default.xml".
 */
#[Strategy(name: 'default.xml', worker: 'parser', component: 'document', package: 'billing')]
class XmlParserStrategy extends AbstractStrategy implements ParserStrategyInterface
{
    /**
     * Constructor de la estrategia del parser.
     *
     * @param XmlServiceInterface $xmlService
     */
    public function __construct(private XmlServiceInterface $xmlService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function parse(string $data): array
    {
        // Cargar los datos del XML a un arreglo.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($data);
        $array = $this->xmlService->decode($xmlDocument);

        // Obtener los datos del documento a generar.
        $documentoData = $array['DTE']['Documento']
            ?? $array['DTE']['Exportaciones']
            ?? $array['DTE']['Liquidacion']
            ?? null
        ;

        // Si el XML no tiene los tags válidos se lanza una excepción.
        if ($documentoData === null) {
            throw new ParserException(
                'El nodo raíz del XML del documento debe ser el tag "DTE". Dentro de este nodo raíz debe existir un tag "Documento", "Exportaciones" o "Liquidacion". Este segundo nodo es el que debe contener los datos del documento.'
            );
        }

        // Quitar los atributos que tenga el tag encontrado.
        unset($documentoData['@attributes']);

        // Entregar los datos parseados.
        return $documentoData;
    }
}

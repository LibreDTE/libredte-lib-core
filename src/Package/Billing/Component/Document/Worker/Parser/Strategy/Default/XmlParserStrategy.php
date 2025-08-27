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

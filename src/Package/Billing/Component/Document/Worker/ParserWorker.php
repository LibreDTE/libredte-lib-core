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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ParserWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\ParserException;
use Throwable;

/**
 * Clase para los parsers de datos de entrada de los documentos tributarios.
 */
class ParserWorker extends AbstractWorker implements ParserWorkerInterface
{
    /**
     * Esquema de las opciones.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'default' => 'default.json',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function parse(DocumentBagInterface $bag): array
    {
        $options = $this->resolveOptions($bag->getParserOptions());
        $strategy = $this->getStrategy($options->get('strategy'));

        assert($strategy instanceof ParserStrategyInterface);

        try {
            $parsedData = $strategy->parse($bag->getInputData());
        } catch (Throwable $e) {
            throw new ParserException(
                message: $e->getMessage(),
                documentBag: $bag,
                previous: $e
            );
        }

        $bag->setParsedData($parsedData);

        // Se podrían arrastrar opciones resueltas mediante el bag. ¿Necesario?

        return $parsedData;
    }
}

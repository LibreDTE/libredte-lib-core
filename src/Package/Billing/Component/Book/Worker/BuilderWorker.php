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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Signature\Contract\SignatureServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\Book\Exception\BookException;
use Throwable;

/**
 * Worker "billing.book.builder".
 *
 * Construye el XML y la entidad resultante de cualquier tipo de libro
 * tributario a partir del bag con detalles normalizados por el `LoaderWorker`.
 *
 * Selecciona la estrategia usando `BookBagInterface::getTipo()` directamente:
 *   - `libro_ventas`
 *   - `libro_compras`
 *   - `libro_boletas`
 *   - `libro_guias`
 *   - `resumen_ventas_diarias`
 */
#[Worker(name: 'builder', component: 'book', package: 'billing')]
class BuilderWorker extends AbstractWorker implements BuilderWorkerInterface
{
    use StrategiesAwareTrait;

    public function __construct(
        private SignatureServiceInterface $signatureService,
        iterable $strategies = [],
    ) {
        $this->setStrategies($strategies);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        parameters: [
            'bag' => [
                'example' => [
                    'tipo' => TipoLibro::VENTAS,
                    'caratula' => [
                        'RutEmisorLibro' => '76192083-9',
                        'PeriodoTributario' => '2024-01',
                    ],
                    'detalle' => [
                        [
                            'TpoDoc' => 33,
                            'NroDoc' => 1,
                            'TasaImp' => 19,
                            'FchDoc' => '2024-01-10',
                            'RUTDoc' => '12345678-9',
                            'RznSoc' => 'Empresa Compradora SpA',
                            'MntNeto' => 100000,
                            'MntIVA' => 19000,
                            'MntTotal' => 119000,
                        ],
                    ],
                    'certificate' => [
                        'certificate' => '',
                        'privateKey' => '',
                    ],
                    'emisor' => [
                        'rut' => '76192083-9',
                        'razon_social' => 'SASCO SpA',
                    ],
                ],
            ],
        ],
    )]
    public function build(BookBagInterface $bag): BookBagInterface
    {
        $strategyName = $bag->getTipo()->value;
        $strategy = $this->getStrategy($strategyName);

        assert($strategy instanceof BuilderStrategyInterface);

        try {
            $libro = $strategy->build($bag);

            // Firmar el XML si se proporcionó un certificado.
            $certificate = $bag->getCertificate();
            if ($certificate !== null && !$libro->isSimplificado()) {
                $signedXml = $this->signatureService->signXml(
                    $libro->saveXml(),
                    $certificate,
                    $libro->getId(),
                    $libro->getSignatureNamespace()
                );
                $libro->getXmlDocument()->loadXml($signedXml);
            }

            // Guardar el libro en el bag para uso posterior (ej: validación).
            $bag->setBook($libro);

            return $bag;
        } catch (Throwable $e) {
            throw new BookException(
                message: [
                    'Could not build the book "{type}": {detail}',
                    'type' => $bag->getTipo()->value,
                    'detail' => $e,
                ],
                previous: $e
            );
        }
    }
}

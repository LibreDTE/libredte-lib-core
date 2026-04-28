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

namespace libredte\lib\Core\Package\Billing\Component\Book\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Trait\StrategiesAwareTrait;
use Derafu\Signature\Contract\SignatureServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BuilderWorkerInterface;
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
    public function build(BookBagInterface $bag): BookInterface
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
                    $libro->getXml(),
                    $certificate,
                    $libro->getId(),
                    $libro->getSignatureNamespace()
                );
                $libro->getXmlDocument()->loadXml($signedXml);
            }

            // Guardar el libro en el bag para uso posterior (ej: validación).
            $bag->setBook($libro);

            return $libro;
        } catch (Throwable $e) {
            throw new BookException(
                message: sprintf(
                    'No fue posible construir el libro "%s": %s',
                    $bag->getTipo()->value,
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafBagInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafBag;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;

/**
 * Worker que permite cargar archivos CAF.
 */
class CafLoaderWorker extends AbstractWorker implements CafLoaderWorkerInterface
{
    protected string $cafClass = Caf::class;

    public function __construct(
        private EmisorFactoryInterface $emisorFactory,
        private EntityComponentInterface $entityComponent
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function load(string|XmlInterface $xml): CafBagInterface
    {
        $class = $this->cafClass;
        $caf = new $class($xml);

        $emisor = $this->emisorFactory->create($caf->getEmisor());

        $tipoDocumento = $this->getTipoDocumento($caf->getTipoDocumento());

        return new CafBag($caf, $emisor, $tipoDocumento);
    }

    /**
     * Obtiene la instancia del tipo de documento del CAF.
     *
     * @param int $codigoTipoDocumento
     * @return TipoDocumentoInterface
     */
    private function getTipoDocumento(int $codigoTipoDocumento): TipoDocumentoInterface
    {
        // Buscar el tipo de documento tributario que se desea construir.
        $tipoDocumento = $this->entityComponent
            ->getRepository(TipoDocumentoInterface::class)
            ->find($codigoTipoDocumento)
        ;

        // Si el documento no existe error.
        if (!$tipoDocumento) {
            throw new CafLoaderException(sprintf(
                'No se encontró el código de documento %d para procesar el CAF.',
                $codigoTipoDocumento
            ));
        }

        // Entregar el tipo documento a la bolsa.
        return $tipoDocumento;
    }
}

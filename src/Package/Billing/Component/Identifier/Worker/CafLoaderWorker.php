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

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;

/**
 * Worker que permite cargar archivos CAF.
 */
#[Worker(name: 'caf_loader', component: 'identifier', package: 'billing')]
class CafLoaderWorker extends AbstractWorker implements CafLoaderWorkerInterface
{
    protected string $cafClass = Caf::class;

    public function __construct(
        private RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function load(string|XmlDocumentInterface $xml): CafInterface
    {
        $class = $this->cafClass;
        $caf = new $class($xml);

        $this->validateTipoDocumento($caf->getTipoDocumento());

        return $caf;
    }

    /**
     * Valida que el código de tipo de documento del CAF exista en el repositorio.
     *
     * @param int $codigoTipoDocumento
     * @return void
     * @throws CafLoaderException
     */
    private function validateTipoDocumento(int $codigoTipoDocumento): void
    {
        $tipoDocumento = $this->repositoryManager
            ->getRepository(TipoDocumentoInterface::class)
            ->find($codigoTipoDocumento)
        ;

        if (!$tipoDocumento) {
            throw new CafLoaderException(sprintf(
                'No se encontró el código de documento %d para procesar el CAF.',
                $codigoTipoDocumento
            ));
        }
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDescuentosRecargosTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDetalleTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeImpuestoAdicionalRetencionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeIvaMntTotalTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeTransporteTrait;

/**
 * Normalizador del documento factura afecta.
 */
#[Job(name: 'normalize_factura_afecta', worker: 'normalizer', component: 'document', package: 'billing')]
class NormalizeFacturaAfectaJob extends AbstractJob implements JobInterface
{
    // Traits usados por este normalizador.
    use NormalizeDetalleTrait;
    use NormalizeDescuentosRecargosTrait;
    use NormalizeImpuestoAdicionalRetencionTrait;
    use NormalizeIvaMntTotalTrait;
    use NormalizeTransporteTrait;

    public function __construct(
        protected RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Completar con campos por defecto.
        $data = array_replace_recursive([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'RUTMandante' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Transporte' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => false,
                    'TasaIVA' => $bag->getTipoDocumento()->getDefaultTasaIVA(),
                    'IVA' => 0,
                    'ImptoReten' => false,
                    'CredEC' => false,
                    'MntTotal' => 0,
                ],
                'OtraMoneda' => false,
            ],
        ], $data);

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);

        // Normalizar datos.
        $this->normalizeDetalle($bag);
        $this->normalizeDescuentosRecargos($bag);
        $this->normalizeImpuestoAdicionalRetencion($bag);
        $this->normalizeIvaMntTotal($bag);
        $this->normalizeTransporte($bag);
    }
}

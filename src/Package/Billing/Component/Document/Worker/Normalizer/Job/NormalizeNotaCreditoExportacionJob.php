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

use Derafu\Array\ArrayReader;
use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDescuentosRecargosTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDetalleTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeExportacionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeImpuestoAdicionalRetencionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeIvaMntTotalTrait;

/**
 * Normalizador del documento nota de crédito de exportación.
 */
#[Job(name: 'normalize_nota_credito_exportacion', worker: 'normalizer', component: 'document', package: 'billing')]
class NormalizeNotaCreditoExportacionJob extends AbstractJob implements JobInterface
{
    // Traits usados por este normalizador.
    use NormalizeExportacionTrait;
    use NormalizeDetalleTrait;
    use NormalizeDescuentosRecargosTrait;
    use NormalizeImpuestoAdicionalRetencionTrait;
    use NormalizeIvaMntTotalTrait;

    public function __construct(
        protected RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();

        // Completar con nodos por defecto.
        $data = array_replace_recursive([
            'Encabezado' => [
                'IdDoc' => null,
                'Emisor' => null,
                'Receptor' => null,
                'Transporte' => [
                    'Patente' => null,
                    'RUTTrans' => null,
                    'Chofer' => null,
                    'DirDest' => null,
                    'CmnaDest' => null,
                    'CiudadDest' => null,
                    'Aduana' => [
                        'CodModVenta' => null,
                        'CodClauVenta' => null,
                        'TotClauVenta' => null,
                        'CodViaTransp' => null,
                        'NombreTransp' => null,
                        'RUTCiaTransp' => null,
                        'NomCiaTransp' => null,
                        'IdAdicTransp' => null,
                        'Booking' => null,
                        'Operador' => null,
                        'CodPtoEmbarque' => null,
                        'IdAdicPtoEmb' => null,
                        'CodPtoDesemb' => null,
                        'IdAdicPtoDesemb' => null,
                        'Tara' => null,
                        'CodUnidMedTara' => null,
                        'PesoBruto' => null,
                        'CodUnidPesoBruto' => null,
                        'PesoNeto' => null,
                        'CodUnidPesoNeto' => null,
                        'TotItems' => null,
                        'TotBultos' => null,
                        'TipoBultos' => null,
                        'MntFlete' => null,
                        'MntSeguro' => null,
                        'CodPaisRecep' => null,
                        'CodPaisDestin' => null,
                    ],
                ],
                'Totales' => [
                    'TpoMoneda' => null,
                    'MntExe' => 0,
                    'MntTotal' => 0,
                ],
            ],
        ], $data);

        // Actualizar los datos normalizados.
        $bag->setNormalizedData(ArrayReader::fromArray($data, 'document')->toMap());

        // Normalizar datos.
        $this->normalizeDetalle($bag);
        $this->normalizeDescuentosRecargos($bag);
        $this->normalizeImpuestoAdicionalRetencion($bag);
        $this->normalizeIvaMntTotal($bag);
        $this->normalizeExportacion($bag);
    }
}

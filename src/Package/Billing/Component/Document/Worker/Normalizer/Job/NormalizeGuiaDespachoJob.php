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

use Derafu\Array\ArrayWriter;
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
 * Normalizador del documento guía de despacho.
 */
#[Job(name: 'normalize_guia_despacho', worker: 'normalizer', component: 'document', package: 'billing')]
class NormalizeGuiaDespachoJob extends AbstractJob implements JobInterface
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
        $data = $bag->requireNormalizedData();

        // Completar con nodos por defecto.
        $data = array_replace_recursive([
            'Encabezado' => [
                'IdDoc' => null,
                'Emisor' => null,
                'Receptor' => null,
                'RUTSolicita' => null,
                'Transporte' => null,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => null,
                    'TasaIVA' => $bag->requireTipoDocumento()->getDefaultTasaIVA(),
                    'IVA' => 0,
                    'ImptoReten' => null,
                    'CredEC' => null,
                    'MntTotal' => 0,
                ],
            ],
        ], $data);

        $writer = ArrayWriter::fromReference($data, 'document');
        $header = $writer->getReader()->requireReader('Encabezado');
        $idDoc = $header->getMap('IdDoc');

        // Si es traslado interno se copia el emisor en el receptor solo si el
        // receptor no está definido o bien si el receptor tiene RUT diferente
        // al emisor.
        if (($idDoc['IndTraslado'] ?? null) == 5) {
            $recipient = $header->getMap('Receptor');
            $issuer = $header->getMap('Emisor');

            if (
                !$recipient
                || ($recipient['RUTRecep'] ?? null) != ($issuer['RUTEmisor'] ?? null)
            ) {
                $newRecipient = [];
                $cols = [
                    'RUTEmisor' => 'RUTRecep',
                    'RznSoc' => 'RznSocRecep',
                    'GiroEmis' => 'GiroRecep',
                    'Telefono' => 'Contacto',
                    'CorreoEmisor' => 'CorreoRecep',
                    'DirOrigen' => 'DirRecep',
                    'CmnaOrigen' => 'CmnaRecep',
                ];
                foreach ($cols as $source => $target) {
                    $value = $issuer[$source] ?? null;
                    if (!empty($value)) {
                        $newRecipient[$target] = $value;
                    }
                }
                if (!empty($newRecipient['GiroRecep'])) {
                    $newRecipient['GiroRecep'] = mb_substr(
                        $header->requireString('Emisor.GiroEmis'),
                        0,
                        40
                    );
                }

                $writer->set('Encabezado.Receptor', $newRecipient);
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($writer->getReader()->toMap());

        // Normalizar datos.
        $this->normalizeDetalle($bag);
        $this->normalizeDescuentosRecargos($bag);
        $this->normalizeImpuestoAdicionalRetencion($bag);
        $this->normalizeIvaMntTotal($bag);
        $this->normalizeTransporte($bag);
    }
}

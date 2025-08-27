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
        $data = $bag->getNormalizedData();

        // Completar con nodos por defecto.
        $data = array_replace_recursive([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
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
            ],
        ], $data);

        // Si es traslado interno se copia el emisor en el receptor solo si el
        // receptor no está definido o bien si el receptor tiene RUT diferente
        // al emisor.
        if ($data['Encabezado']['IdDoc']['IndTraslado'] == 5) {
            if (
                !$data['Encabezado']['Receptor']
                || $data['Encabezado']['Receptor']['RUTRecep']
                    != $data['Encabezado']['Emisor']['RUTEmisor']
            ) {
                $data['Encabezado']['Receptor'] = [];
                $cols = [
                    'RUTEmisor' => 'RUTRecep',
                    'RznSoc' => 'RznSocRecep',
                    'GiroEmis' => 'GiroRecep',
                    'Telefono' => 'Contacto',
                    'CorreoEmisor' => 'CorreoRecep',
                    'DirOrigen' => 'DirRecep',
                    'CmnaOrigen' => 'CmnaRecep',
                ];
                foreach ($cols as $emisor => $receptor) {
                    if (!empty($data['Encabezado']['Emisor'][$emisor])) {
                        $data['Encabezado']['Receptor'][$receptor] =
                            $data['Encabezado']['Emisor'][$emisor]
                        ;
                    }
                }
                if (!empty($data['Encabezado']['Receptor']['GiroRecep'])) {
                    $data['Encabezado']['Receptor']['GiroRecep'] =
                        mb_substr($data['Encabezado']['Receptor']['GiroRecep'], 0, 40)
                    ;
                }
            }
        }

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

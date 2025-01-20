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

use Derafu\Lib\Core\Foundation\Abstract\AbstractJob;
use Derafu\Lib\Core\Foundation\Contract\JobInterface;
use Derafu\Lib\Core\Helper\Arr;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDescuentosRecargosTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeDetalleTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeExportacionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeImpuestoAdicionalRetencionTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\NormalizeIvaMntTotalTrait;

/**
 * Normalizador del documento nota de débito de exportación.
 */
class NormalizeNotaDebitoExportacionJob extends AbstractJob implements JobInterface
{
    // Traits usados por este normalizador.
    use NormalizeExportacionTrait;
    use NormalizeDetalleTrait;
    use NormalizeDescuentosRecargosTrait;
    use NormalizeImpuestoAdicionalRetencionTrait;
    use NormalizeIvaMntTotalTrait;

    public function __construct(
        protected EntityComponentInterface $entityComponent
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Completar con nodos por defecto.
        $data = Arr::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'Transporte' => [
                    'Patente' => false,
                    'RUTTrans' => false,
                    'Chofer' => false,
                    'DirDest' => false,
                    'CmnaDest' => false,
                    'CiudadDest' => false,
                    'Aduana' => [
                        'CodModVenta' => false,
                        'CodClauVenta' => false,
                        'TotClauVenta' => false,
                        'CodViaTransp' => false,
                        'NombreTransp' => false,
                        'RUTCiaTransp' => false,
                        'NomCiaTransp' => false,
                        'IdAdicTransp' => false,
                        'Booking' => false,
                        'Operador' => false,
                        'CodPtoEmbarque' => false,
                        'IdAdicPtoEmb' => false,
                        'CodPtoDesemb' => false,
                        'IdAdicPtoDesemb' => false,
                        'Tara' => false,
                        'CodUnidMedTara' => false,
                        'PesoBruto' => false,
                        'CodUnidPesoBruto' => false,
                        'PesoNeto' => false,
                        'CodUnidPesoNeto' => false,
                        'TotItems' => false,
                        'TotBultos' => false,
                        'TipoBultos' => false,
                        'MntFlete' => false,
                        'MntSeguro' => false,
                        'CodPaisRecep' => false,
                        'CodPaisDestin' => false,
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
        $bag->setNormalizedData($data);

        // Normalizar datos.
        $this->normalizeDetalle($bag);
        $this->normalizeDescuentosRecargos($bag);
        $this->normalizeImpuestoAdicionalRetencion($bag);
        $this->normalizeIvaMntTotal($bag);
        $this->normalizeExportacion($bag);
    }
}

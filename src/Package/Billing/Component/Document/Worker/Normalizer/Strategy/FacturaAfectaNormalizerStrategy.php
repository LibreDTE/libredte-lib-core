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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy;

use Derafu\Lib\Core\Helper\Arr;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Normalizer\Strategy\FacturaAfectaNormalizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\DescuentosRecargosNormalizerTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\DetalleNormalizerTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\ImpuestoAdicionalRetencionNormalizerTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\IvaMntTotalNormalizerTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\TransporteNormalizerTrait;

/**
 * Normalizador del documento factura afecta.
 */
class FacturaAfectaNormalizerStrategy extends AbstractNormalizerStrategy implements FacturaAfectaNormalizerStrategyInterface
{
    // Traits usados por este normalizador.
    use DetalleNormalizerTrait;
    use DescuentosRecargosNormalizerTrait;
    use ImpuestoAdicionalRetencionNormalizerTrait;
    use IvaMntTotalNormalizerTrait;
    use TransporteNormalizerTrait;

    /**
     * {@inheritdoc}
     */
    protected function normalizeDocument(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Completar con campos por defecto.
        $data = Arr::mergeRecursiveDistinct([
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

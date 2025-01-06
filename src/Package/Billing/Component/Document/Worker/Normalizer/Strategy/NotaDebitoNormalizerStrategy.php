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
use libredte\lib\Core\Package\Billing\Component\Document\Contract\Normalizer\Strategy\NotaDebitoNormalizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\DescuentosRecargosNormalizerTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\DetalleNormalizerTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\ImpuestoAdicionalRetencionNormalizerTrait;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\IvaMntTotalNormalizerTrait;

/**
 * Normalizador del documento nota de débito.
 */
class NotaDebitoNormalizerStrategy extends AbstractNormalizerStrategy implements NotaDebitoNormalizerStrategyInterface
{
    // Traits usados por este normalizador.
    use DetalleNormalizerTrait;
    use DescuentosRecargosNormalizerTrait;
    use ImpuestoAdicionalRetencionNormalizerTrait;
    use IvaMntTotalNormalizerTrait;

    /**
     * {@inheritdoc}
     */
    protected function normalizeDocument(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Completar con nodos por defecto.
        $data = Arr::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => 0,
                    'TasaIVA' => $bag->getTipoDocumento()->getDefaultTasaIVA(),
                    'IVA' => false,
                    'ImptoReten' => false,
                    'IVANoRet' => false,
                    'CredEC' => false,
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

        $data = $bag->getNormalizedData();

        // Corregir monto neto e IVA.
        if (!$data['Encabezado']['Totales']['MntNeto']) {
            $data['Encabezado']['Totales']['MntNeto'] = 0;
            $data['Encabezado']['Totales']['TasaIVA'] = false;
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($data);
    }
}

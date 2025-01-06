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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Contract\EntityComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\NormalizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait\DocumentNormalizerTrait;

/**
 * Clase abstracta (base) para las estrategias de normalización de
 * documentos tributarios.
 */
abstract class AbstractNormalizerStrategy extends AbstractStrategy implements NormalizerStrategyInterface
{
    use DocumentNormalizerTrait;

    public function __construct(
        protected EntityComponentInterface $entityComponent
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(DocumentBagInterface $bag): array
    {
        // Asignar inicialmente como datos normalizados los datos parseados.
        // Esto se hace porque son la base de los datos para normalizar y se van
        // normalizando en la misma variable de la bolsa durante todo el
        // proceso.
        $bag->setNormalizedData($bag->getParsedData());

        // Aplicar normalización inicial del DTE.
        $this->preNormalizeDocument($bag);

        // Aplicar normalización del tipo de DTE.
        $this->normalizeDocument($bag);

        // Aplicar normalización final del DTE.
        $this->postNormalizeDocument($bag);

        // Entregar los datos normalizados.
        return $bag->getNormalizedData();
    }

    /**
     * Normalización personalizada de cada estrategia.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    abstract protected function normalizeDocument(DocumentBagInterface $bag): void;

    /**
     * Redondea valores asociados a un tipo de moneda.
     *
     * Si los valores son en pesos chilenos se redondea sin decimales. Si los
     * valores están en otro tipo de moneda se mantienen los decimales, por
     * defecto se mantienen 4 decimales.
     *
     * @param int|float $amount Valor que se desea redondear.
     * @param string|null|false $currency La moneda en la que está el valor.
     * @param int $decimals Cantidad de decimales a mantener cuando la moneda
     * no es peso chileno.
     * @return int|float Valor redondeado según la moneda y decimales a usar.
     */
    protected function round(
        int|float $amount,
        string|null|false $currency = null,
        int $decimals = 4
    ): int|float {
        return (!$currency || $currency === 'PESO CL')
            ? (int) round($amount)
            : (float) round($amount, $decimals)
        ;
    }

    /**
     * Obtiene el monto neto y el IVA de ese neto a partir de un monto total.
     *
     * NOTE: El IVA obtenido puede no ser el NETO * (TASA / 100). Se calcula el
     * monto neto y luego se obtiene el IVA haciendo la resta entre el total y
     * el neto. Hay casos de borde que generan problemas como:
     *
     *   - BRUTO:   680 => NETO:   571 e IVA:   108 => TOTAL:   679
     *   - BRUTO: 86710 => NETO: 72866 e IVA: 13845 => TOTAL: 86711
     *
     * Estos casos son "normales", pues por aproximaciones "no da".
     *
     * @param int $total Total que representa el monto neto más el IVA.
     * @param int|float|false $tasa Tasa del IVA o `false` si no corresponde.
     * @return array Arreglo con el neto y el IVA en índices 0 y 1.
     */
    protected function calcularNetoIVA($total, int|float|false $tasa): array
    {
        // Si no existe tasa es porque no hay Neto ni IVA (doc exento).
        if ($tasa === 0 || $tasa === false) {
            return [0, 0];
        }

        // Obtener el neto e IVA a partir del total.
        $neto = round($total / (1 + ($tasa / 100)));
        $iva = $total - $neto;

        // Entregar el neto e IVA.
        return [$neto, $iva];
    }
}

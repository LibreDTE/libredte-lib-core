<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

namespace libredte\lib\Core\Repository;

use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;

/**
 * Clase para trabajar con los impuestos adicionales.
 */
class ImpuestosAdicionalesRepository
{
    /**
     * @var DataProviderInterface
     */
    private DataProviderInterface $dataProvider;

    /**
     * Constructor del repositorio.
     *
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Indica si el impuesto es adicional o retención.
     *
     * @param int $codigo Código del impuesto
     * @return string A: adicional, R: retención y =false no se pudo determinar.
     */
    public function getTipo(int $codigo): string|false
    {
        $tipo = $this->dataProvider->getValue(
            'impuestos_adicionales_retenciones',
            $codigo,
            false
        );
        return $tipo['tipo'] ?? false;
    }

    /**
     * Entrega la glosa del impuesto adicional.
     *
     * @param int $codigo Código del impuesto
     * @return string Glosa del impuesto o glosa estándar si no se encontró una.
     */
    public function getGlosa(int $codigo): string
    {
        $glosa = $this->dataProvider->getValue(
            'impuestos_adicionales_retenciones',
            $codigo,
            false
        );
        return $glosa['glosa'] ?? 'Impto. cód. ' . $codigo;
    }

    /**
     * Entrega la tasa del impuesto adicional.
     *
     * @param int $codigo Código del impuesto
     * @return float|false Tasa del impuesto o =false si no se pudo determinar.
     */
    public function getTasa(int $codigo): float|false
    {
        $tasa = $this->dataProvider->getValue(
            'impuestos_adicionales_retenciones',
            $codigo,
            false
        );
        return $tasa['tasa'] ?? false;
    }

    /**
     * Método que entrega el monto de impuesto retenido a partir de la
     * información del tag OtrosImp del DTE.
     *
     * @param array $OtrosImp Arreglo con los datos de OtrosImp
     * @return float Monto retenido
     */
    public function getRetenido(array $OtrosImp): float
    {
        $retenido = 0.0;
        foreach ($OtrosImp as $Imp) {
            if ($this->getTipo((int)$Imp['CodImp']) === 'R') {
                $retenido += (float)$Imp['MntImp'];
            }
        }
        return $retenido;
    }
}

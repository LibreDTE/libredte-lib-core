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

namespace libredte\lib\Core\Repository;

use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use UnexpectedValueException;

/**
 * Repositorio para trabajar con las tablas de la Aduana.
 *
 * Este repositorio se encarga de acceder a los datos de la Aduana almacenados
 * en archivos y proporciona métodos para obtener glosas, valores y códigos
 * relacionados.
 */
class AduanaRepository
{
    /**
     * @var DataProviderInterface
     */
    private DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase AduanaRepository.
     *
     * @param DataProviderInterface|null $dataProvider Proveedor de datos que
     * se encargará de cargar y acceder a los datos de Aduana.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Entrega la glosa para el campo en la tabla.
     *
     * @param string $tag Tag de la tabla.
     * @return string|false Glosa del campo o false si no existe.
     */
    public function getGlosa(string $tag): string|false
    {
        try {
            $data = $this->dataProvider->getData('aduana');
            return $data[$tag]['glosa'] ?? false;
        } catch (UnexpectedValueException $e) {
            return false;
        }
    }

    /**
     * Entrega el valor traducido a partir de la tabla.
     *
     * @param string $tag Tag de la tabla.
     * @param mixed $codigo Código a traducir.
     * @return string Valor traducido o el código si no existe.
     */
    public function getValor(string $tag, $codigo): string
    {
        $glosaData = $this->dataProvider->getData('aduana');

        if (!isset($glosaData[$tag])) {
            return (string) $codigo;
        }

        $datosFile = $glosaData[$tag]['datos'] ?? null;

        if (!$datosFile) {
            return (string) $codigo;
        }

        $valuesData = $this->dataProvider->getData($datosFile);

        if ($tag === 'TipoBultos') {
            $valor = $valuesData[$codigo['CodTpoBultos']] ?? $codigo['CodTpoBultos'];
            $valor = $codigo['CantBultos'] . ' ' . $valor;

            if (!empty($codigo['IdContainer'])) {
                $valor .= ' (' . $codigo['IdContainer'] . ' / ' . $codigo['Sello'] . ' / ' . $codigo['EmisorSello'] . ')';
            } elseif (!empty($codigo['Marcas'])) {
                $valor .= ' (' . $codigo['Marcas'] . ')';
            }

            return $valor;
        }

        return $valuesData[$codigo] ?? (string) $codigo;
    }

    /**
     * Método que entrega a partir de su valor (texto) el código que corresponde.
     *
     * @param string $tag Tag de la tabla.
     * @param string $valor Valor a buscar.
     * @return int|string Código correspondiente o el valor si no se encuentra.
     */
    public function getCodigo(string $tag, string $valor): int|string
    {
        $glosaData = $this->dataProvider->getData('aduana');

        if (!isset($glosaData[$tag])) {
            return $valor;
        }

        $datosFile = $glosaData[$tag]['datos'] ?? null;

        if (!$datosFile) {
            return $valor;
        }

        $valuesData = $this->dataProvider->getData($datosFile);
        $invertido = array_flip($valuesData);

        $valor = strtoupper($valor);
        return $invertido[$valor] ?? $valor;
    }

    /**
     * Método que entrega los datos de las nacionalidades.
     *
     * @return array Nacionalidades.
     */
    public function getNacionalidades(): array
    {
        return $this->dataProvider->getData('aduana_paises');
    }

    /**
     * Método que entrega la glosa de la nacionalidad a partir de su código.
     *
     * @param int|string $codigo Código de la nacionalidad.
     * @return string Glosa de la nacionalidad.
     */
    public function getNacionalidad($codigo): string
    {
        $nacionalidades = $this->getNacionalidades();
        return $nacionalidades[$codigo] ?? (string) $codigo;
    }

    /**
     * Método que entrega los datos de las formas de pago.
     *
     * @return array Formas de pago.
     */
    public function getFormasDePago(): array
    {
        return $this->dataProvider->getData('aduana_formas_de_pago');
    }

    /**
     * Método que entrega los datos de las modalidades de venta.
     *
     * @return array Modalidades de venta.
     */
    public function getModalidadesDeVenta(): array
    {
        return $this->dataProvider->getData('aduana_modalidades_de_venta');
    }

    /**
     * Método que entrega los datos de las clausulas de venta.
     *
     * @return array Clausulas de venta.
     */
    public function getClausulasDeVenta(): array
    {
        return $this->dataProvider->getData('aduana_clausulas_de_venta');
    }

    /**
     * Método que entrega los datos de los tipos de transportes.
     *
     * @return array Tipos de transportes.
     */
    public function getTransportes(): array
    {
        return $this->dataProvider->getData('aduana_transportes');
    }

    /**
     * Método que entrega los datos de los puertos.
     *
     * @return array Puertos.
     */
    public function getPuertos(): array
    {
        return $this->dataProvider->getData('aduana_puertos');
    }

    /**
     * Método que entrega los datos de las unidades.
     *
     * @return array Unidades.
     */
    public function getUnidades(): array
    {
        return $this->dataProvider->getData('aduana_unidades');
    }

    /**
     * Método que entrega los datos de los tipos de bultos.
     *
     * @return array Tipos de bultos.
     */
    public function getBultos(): array
    {
        return $this->dataProvider->getData('aduana_tipos_de_bulto');
    }
}

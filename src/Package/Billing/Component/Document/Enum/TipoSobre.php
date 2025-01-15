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

namespace libredte\lib\Core\Package\Billing\Component\Document\Enum;

/**
 * Enum del Tag XML donde va el documento (tag DTE).
 */
enum TipoSobre: int
{
    /**
     * Caso que representa que el envío es de DTE.
     *
     * Este sobre se usa para todo menos boletas.
     */
    case ENVIO_DTE = 0;

    /**
     * Caso que representa que el envío es de boletas.
     */
    case ENVIO_BOLETA = 1;

    /**
     * Configuración (reglas) para el documento XML del sobre de documentos.
     */
    private const CONFIG = [
        self::ENVIO_DTE->value => [
            // Máxima cantidad de tipos de documentos en el sobre.
            'SubTotDTE_max' => 20,
            // Máxima cantidad de documentos en un sobre.
            'DTE_max' => 2000,
            // Tag XML para el sobre.
            'tag' => 'EnvioDTE',
            // Schema principal del XML del sobre.
            'schema' => 'EnvioDTE_v10.xsd',
        ],
        self::ENVIO_BOLETA->value => [
            // Máxima cantidad de tipos de documentos en el sobre.
            'SubTotDTE_max' => 2,
            // Máxima cantidad de documentos en un sobre.
            'DTE_max' => 1000,
            // Tag XML para el sobre.
            'tag' => 'EnvioBOLETA',
            // Schema principal del XML del sobre.
            'schema' => 'EnvioBOLETA_v11.xsd',
        ],
    ];

    /**
     * Obtiene el nombre del tipo de sobre.
     *
     * @return string
     */
    public function getNombre(): string
    {
        return $this->name;
    }

    /**
     * Obtiene la cantidad máxima de tipos de documentos que el sobre puede
     * tener.
     *
     * @return int
     */
    public function getMaximoTiposDocumentos(): int
    {
        return self::CONFIG[$this->value]['SubTotDTE_max'];
    }

    /**
     * Obtiene la cantidad máximo de documentos que se pueden incluir en el
     * sobre.
     *
     * @return int
     */
    public function getMaximoDocumentos(): int
    {
        return self::CONFIG[$this->value]['DTE_max'];
    }

    /**
     * Obtiene el tag del XML del nodo raíz del sobre.
     *
     * @return string
     */
    public function getTagXml(): string
    {
        return self::CONFIG[$this->value]['tag'];
    }

    /**
     * Obtiene el nombre del esquema que se debe utilizar en el XML del sobre.
     *
     * @return string
     */
    public function getSchema(): string
    {
        return self::CONFIG[$this->value]['schema'];
    }

    /**
     * Indica si el sobre es de boletas.
     *
     * @return bool `true`es sobre de boletas, `false` es de otros DTE.
     */
    public function sonBoletas(): bool
    {
        return $this === self::ENVIO_BOLETA;
    }
}

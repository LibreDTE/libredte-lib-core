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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Backbone\Contract\StrategiesAwareInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Backbone\Exception\StrategyException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BuilderException;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DocumentBagManagerException;

/**
 * Interfaz para los constructores de documentos.
 */
interface BuilderWorkerInterface extends WorkerInterface, StrategiesAwareInterface
{
    /**
     * Construye el documento tributario con los datos pasados.
     *
     * El documento generado dependerá de lo que se haya pasado:
     *
     *   - Borrador: Solo se pasaron datos de entrada.
     *   - Documento timbrado: Se incluyó folio real y CAF.
     *   - Documento timbrado y firmado: Se incluyó CAF y certificado digital.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos del documento a
     * construir.
     * @return DocumentBagInterface La bolsa con el DTE construido (borrador, timbrado
     * o timbrado y firmado, según los datos pasados).
     * @throws BuilderException Si la estrategia de construcción falla.
     * @throws DocumentBagManagerException Si no se determina un tipo de
     * documento tributario válido para el documento de la bolsa.
     * @throws StrategyException Si no existe una estrategia de construcción
     * registrada para el alias del documento.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/formato_dte_202602.pdf Formato de los Documentos Tributarios Electrónicos (DTE) del SII.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/diagrama_dte.zip Diagrama del esquema XML del sobre EnvioDTE.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/boletas_elec_0720_3.pdf Formato de las Boletas Electrónicas del SII.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/diag_boleta_0920.zip Diagrama del esquema XML de la Boleta Electrónica.
     */
    public function build(DocumentBagInterface $bag): DocumentBagInterface;

    /**
     * Crea la instancia del DTE a partir del XmlDocument contenido en la bolsa.
     *
     * @param DocumentBagInterface $bag
     * @return DocumentBagInterface
     */
    public function create(DocumentBagInterface $bag): DocumentBagInterface;
}

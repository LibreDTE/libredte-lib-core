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
     * @return DocumentInterface El documento construido (borrador, timbrado
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
    public function build(DocumentBagInterface $bag): DocumentInterface;

    /**
     * Crea la instancia del DTE a partir del XmlDocument contenido en la bolsa.
     *
     * @param DocumentBagInterface $bag
     * @return DocumentInterface
     */
    public function create(DocumentBagInterface $bag): DocumentInterface;
}

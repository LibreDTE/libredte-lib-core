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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Contract;

use Derafu\Lib\Core\Foundation\Contract\ComponentInterface;

/**
 * Interfaz para `IntegrationComponent`.
 */
interface IntegrationComponentInterface extends ComponentInterface
{
    /**
     * Entrega el worker que permite realizar acciones en el SII.
     *
     * @return SiiLazyWorkerInterface
     */
    public function getSiiLazyWorker(): SiiLazyWorkerInterface;

    /**
     * Entrega el worker que consume servicios web usando WSDL en el SII.
     *
     * @return SiiWsdlConsumerWorkerInterface
     */
    public function getSiiWsdlConsumerWorker(): SiiWsdlConsumerWorkerInterface;

    /**
     * Entrega el worker que administra la vida del token de la sesión en la API
     * del SII.
     *
     * @return SiiTokenManagerWorkerInterface
     */
    public function getSiiTokenManagerWorker(): SiiTokenManagerWorkerInterface;

    /**
     * Entrega el worker que realiza el envío del documento en XML al SII.
     *
     * @return SiiDocumentSenderWorkerInterface
     */
    public function getSiiDocumentSenderWorker(): SiiDocumentSenderWorkerInterface;

    /**
     * Entrega el worker que consulta el estado de un envío de XML al SII.
     *
     * @return SiiDeliveryCheckerWorkerInterface
     */
    public function getSiiDeliveryCheckerWorker(): SiiDeliveryCheckerWorkerInterface;

    /**
     * Entrega el worker que valida documentos tributarios en el SII.
     *
     * @return SiiDocumentValidatorWorkerInterface
     */
    public function getSiiDocumentValidatorWorker(): SiiDocumentValidatorWorkerInterface;
}

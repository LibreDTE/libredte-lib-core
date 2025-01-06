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

use Derafu\Lib\Core\Foundation\Contract\WorkerInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDeliveryCheckerException;
use libredte\lib\Core\Package\Billing\Component\Integration\Response\SiiDocumentRequestSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Response\SiiDocumentSentResponse;

/**
 * Interfaz del worker que valida documentos (XML) enviados (subidos) al SII.
 */
interface SiiDeliveryCheckerWorkerInterface extends WorkerInterface
{
    /**
     * Obtiene el estado actualizado del envío de un documento XML al SII.
     *
     * Este estado podría no ser el final, si no es un estado final se debe
     * reintentar la consulta posteriormente al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf
     *
     * @param CertificateInterface $certificate Certificado digital.
     * @param integer $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del XML que se envió.
     * @return SiiDocumentSentResponse
     * @throws SiiDeliveryCheckerException En caso de error.
     */
    public function checkSentStatus(
        CertificateInterface $certificate,
        int $trackId,
        string $company
    ): SiiDocumentSentResponse;

    /**
     * Solicita al SII que le envíe el estado del DTE mediente correo
     * electrónico.
     *
     * El correo al que se informa el estado del DTE es el que está configurado
     * en el SII, no siendo posible asignarlo mediante el servicio web.
     *
     * La principal ventaja de utilizar este método es que el SII en el correo
     * incluye los detalles de los rechazos, algo que no entrega a través del
     * servicio web de consulta del estado del envío del XML al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/OIFE2005_wsDTECorreo_MDE.pdf
     *
     * @param CertificateInterface $certificate Certificado digital.
     * @param integer $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del documento.
     * @return SiiDocumentRequestSentStatusByEmailResponse
     * @throws SiiDeliveryCheckerException En caso de error.
     */
    public function requestSentStatusByEmail(
        CertificateInterface $certificate,
        int $trackId,
        string $company
    ): SiiDocumentRequestSentStatusByEmailResponse;
}

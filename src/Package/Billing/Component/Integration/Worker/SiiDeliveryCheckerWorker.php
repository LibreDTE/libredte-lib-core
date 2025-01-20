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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use Derafu\Lib\Core\Helper\Rut;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDeliveryCheckerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiTokenManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiWsdlConsumerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDeliveryCheckerException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiWsdlConsumerException;
use libredte\lib\Core\Package\Billing\Component\Integration\Response\SiiDocumentRequestSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Response\SiiDocumentSentResponse;

/**
 * Clase para realizar las consultas de validación de documentos al SII.
 */
class SiiDeliveryCheckerWorker extends AbstractWorker implements SiiDeliveryCheckerWorkerInterface
{
    public function __construct(
        private SiiTokenManagerWorkerInterface $tokenManager,
        private SiiWsdlConsumerWorkerInterface $wsdlConsumer,
        private XmlComponentInterface $xmlComponent
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function checkSentStatus(
        CertificateInterface $certificate,
        int $trackId,
        string $company
    ): SiiDocumentSentResponse {
        // Validar el RUT de la empresa que se utilizará para la consulta del
        // estado de envío al SII.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($certificate);

        // Datos para la consulta.
        $requestData = [
            'Rut' => $rutCompany,
            'Dv' => $dvCompany,
            'TrackId' => $trackId,
            'Token' => $token,
        ];

        // Consultar el estado del documento enviado al SII.
        try {
            $xmlResponse = $this->wsdlConsumer->sendRequest(
                'QueryEstUp',
                'getEstUp',
                $requestData
            );
        } catch (SiiWsdlConsumerException $e) {
            throw new SiiDeliveryCheckerException(sprintf(
                'No fue posible obtener el estado del XML enviado al SII con Track ID %s. %s',
                $trackId,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        $responseData = $this->xmlComponent->getDecoderWorker()->decode(
            $xmlResponse
        );
        return new SiiDocumentSentResponse($responseData, $requestData);
    }

    /**
     * {@inheritDoc}
     */
    public function requestSentStatusByEmail(
        CertificateInterface $certificate,
        int $trackId,
        string $company
    ): SiiDocumentRequestSentStatusByEmailResponse {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($certificate);

        // Datos para la consulta.
        $requestData = [
            'Token' => $token,
            'RutEmpresa' => $rutCompany,
            'DvEmpresa' => $dvCompany,
            'TrackId' => $trackId,
        ];

        // Solicitar al SII que envíe el correo electrónico del estado del DTE.
        try {
            $xmlResponse = $this->wsdlConsumer->sendRequest(
                'wsDTECorreo',
                'reenvioCorreo',
                $requestData
            );
        } catch (SiiWsdlConsumerException $e) {
            throw new SiiDeliveryCheckerException(sprintf(
                'No fue posible solicitar el correo con el estado del envío %d al SII. %s',
                $trackId,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        $responseData = $this->xmlComponent->getDecoderWorker()->decode(
            $xmlResponse
        );
        return new SiiDocumentRequestSentStatusByEmailResponse($responseData, $requestData);
    }
}

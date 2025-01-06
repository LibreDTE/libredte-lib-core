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
use Derafu\Lib\Core\Helper\Date;
use Derafu\Lib\Core\Helper\Rut;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiDocumentValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiTokenManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiWsdlConsumerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDocumentValidatorException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiWsdlConsumerException;
use libredte\lib\Core\Package\Billing\Component\Integration\Response\SiiDocumentValidationResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Response\SiiDocumentValidationSignatureResponse;

/**
 * Clase para el envío de documentos al SII.
 *
 * Principalmente es para el envío y consulta de estado del envío de documentos
 * tributarios electrónicos en formato XML.
 */
class SiiDocumentValidatorWorker extends AbstractWorker implements SiiDocumentValidatorWorkerInterface
{
    public function __construct(
        private SiiTokenManagerWorkerInterface $tokenManager,
        private SiiWsdlConsumerWorkerInterface $wsdlConsumer,
        private XmlComponentInterface $xmlComponent
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function validate(
        CertificateInterface $certificate,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient
    ): SiiDocumentValidationResponse {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        $user = $certificate->getId();
        Rut::validate($user);
        Rut::validate($company);
        Rut::validate($recipient);
        [$rutUser, $dvUser] = Rut::toArray($user);
        [$rutCompany, $dvCompany] = Rut::toArray($company);
        [$rutRecipient, $dvRecipient] = Rut::toArray($recipient);

        // Validar fecha y convertir al formato del SII.
        $dateSii = Date::validateAndConvert($date, 'dmY');
        if ($dateSii === null) {
            throw new SiiDocumentValidatorException(sprintf(
                'La fecha %s del documento no es válida, debe tener formato AAAA-MM-DD.',
                $date
            ));
        }

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($certificate);

        // Datos para la consulta.
        $requestData = [
            'RutConsultante' => $rutUser,
            'DvConsultante' => $dvUser,
            'RutCompania' => $rutCompany,
            'DvCompania' => $dvCompany,
            'RutReceptor' => $rutRecipient,
            'DvReceptor' => $dvRecipient,
            'TipoDte' => $document,
            'FolioDte' => $number,
            'FechaEmisionDte' => $dateSii,
            'MontoDte' => $total,
            'Token' => $token,
        ];

        // Consultar el estado del documento al SII.
        try {
            $xmlResponse = $this->wsdlConsumer->sendRequest(
                'QueryEstDte',
                'getEstDte',
                $requestData
            );
        } catch (SiiWsdlConsumerException $e) {
            throw new SiiDocumentValidatorException(sprintf(
                'No fue posible obtener el estado del documento T%dF%d de %d-%s desde el SII. %s',
                $document,
                $number,
                $rutCompany,
                $dvCompany,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        $responseData = $this->xmlComponent->getDecoderWorker()->decode(
            $xmlResponse
        );
        return new SiiDocumentValidationResponse($responseData, $requestData);
    }

    /**
     * {@inheritdoc}
     */
    public function validateSignature(
        CertificateInterface $certificate,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient,
        string $signature
    ): SiiDocumentValidationSignatureResponse {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        Rut::validate($company);
        Rut::validate($recipient);
        [$rutCompany, $dvCompany] = Rut::toArray($company);
        [$rutRecipient, $dvRecipient] = Rut::toArray($recipient);

        // Validar fecha y convertir al formato del SII.
        $dateSii = Date::validateAndConvert($date, 'dmY');
        if ($dateSii === null) {
            throw new SiiDocumentValidatorException(sprintf(
                'La fecha %s del documento no es válida, debe tener formato AAAA-MM-DD.',
                $date
            ));
        }

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($certificate);

        // Datos para la consulta.
        $requestData = [
            'RutEmpresa' => $rutCompany,
            'DvEmpresa' => $dvCompany,
            'RutReceptor' => $rutRecipient,
            'DvReceptor' => $dvRecipient,
            'TipoDte' => $document,
            'FolioDte' => $number,
            'FechaEmisionDte' => $dateSii,
            'MontoDte' => $total,
            'FirmaDte' => $signature,
            'Token' => $token,
        ];

        // Consultar el estado del documento, incluyendo su firma, al SII.
        try {
            $xmlResponse = $this->wsdlConsumer->sendRequest(
                'QueryEstDteAv',
                'getEstDteAv',
                $requestData
            );
        } catch (SiiWsdlConsumerException $e) {
            throw new SiiDocumentValidatorException(sprintf(
                'No fue posible obtener el estado de la firma del documento T%dF%d de %d-%s desde el SII. %s',
                $document,
                $number,
                $rutCompany,
                $dvCompany,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        $responseData = $this->xmlComponent->getDecoderWorker()->decode(
            $xmlResponse
        );
        return new SiiDocumentValidationSignatureResponse(
            $responseData,
            $requestData
        );
    }
}

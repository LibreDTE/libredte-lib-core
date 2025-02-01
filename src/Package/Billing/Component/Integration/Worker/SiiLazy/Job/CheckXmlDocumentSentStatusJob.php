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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job;

use Derafu\Lib\Core\Foundation\Abstract\AbstractJob;
use Derafu\Lib\Core\Foundation\Contract\JobInterface;
use Derafu\Lib\Core\Helper\Rut;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiCheckXmlDocumentSentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiCheckXmlDocumentSentStatusResponse;

/**
 * Clase para realizar las consultas de validación de documentos al SII.
 */
class CheckXmlDocumentSentStatusJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private AuthenticateJob $authenticateJob,
        private ConsumeWebserviceJob $consumeWebserviceJob,
        private XmlComponentInterface $xmlComponent
    ) {
    }

    /**
     * Obtiene el estado actualizado del envío de un documento XML al SII.
     *
     * Este estado podría no ser el final, si no es un estado final se debe
     * reintentar la consulta posteriormente al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del XML que se envió.
     * @return SiiCheckXmlDocumentSentStatusResponse
     * @throws SiiCheckXmlDocumentSentStatusException En caso de error.
     */
    public function checkSentStatus(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): SiiCheckXmlDocumentSentStatusResponse {
        // Validar el RUT de la empresa que se utilizará para la consulta del
        // estado de envío al SII.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->authenticateJob->authenticate($request);

        // Datos para la consulta.
        $requestData = [
            'Rut' => $rutCompany,
            'Dv' => $dvCompany,
            'TrackId' => $trackId,
            'Token' => $token,
        ];

        // Consultar el estado del documento enviado al SII.
        try {
            $xmlResponse = $this->consumeWebserviceJob->sendRequest(
                $request,
                'QueryEstUp',
                'getEstUp',
                $requestData
            );
        } catch (SiiConsumeWebserviceException $e) {
            throw new SiiCheckXmlDocumentSentStatusException(sprintf(
                'No fue posible obtener el estado del XML enviado al SII con Track ID %s. %s',
                $trackId,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado.
        $responseData = $this->xmlComponent->getDecoderWorker()->decode(
            $xmlResponse
        );

        // Retornar respuesta.
        return new SiiCheckXmlDocumentSentStatusResponse(
            $responseData,
            $requestData
        );
    }
}

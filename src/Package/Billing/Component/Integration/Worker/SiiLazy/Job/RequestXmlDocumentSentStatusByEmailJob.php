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
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRequestXmlDocumentSentStatusByEmailException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRequestXmlDocumentSentStatusByEmailResponse;

/**
 * Clase para realizar las consultas de validación de documentos al SII.
 */
class RequestXmlDocumentSentStatusByEmailJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private AuthenticateJob $authenticateJob,
        private ConsumeWebserviceJob $consumeWebserviceJob,
        private XmlComponentInterface $xmlComponent
    ) {
    }

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
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del documento.
     * @return SiiRequestXmlDocumentSentStatusByEmailResponse
     * @throws SiiRequestXmlDocumentSentStatusByEmailException En caso de error.
     */
    public function requestEmail(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): SiiRequestXmlDocumentSentStatusByEmailResponse {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->authenticateJob->authenticate($request);

        // Datos para la consulta.
        $requestData = [
            'Token' => $token,
            'RutEmpresa' => $rutCompany,
            'DvEmpresa' => $dvCompany,
            'TrackId' => $trackId,
        ];

        // Solicitar al SII que envíe el correo electrónico del estado del DTE.
        try {
            $xmlResponse = $this->consumeWebserviceJob->sendRequest(
                $request,
                'wsDTECorreo',
                'reenvioCorreo',
                $requestData
            );
        } catch (SiiConsumeWebserviceException $e) {
            throw new SiiRequestXmlDocumentSentStatusByEmailException(sprintf(
                'No fue posible solicitar el correo con el estado del envío %d al SII. %s',
                $trackId,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado.
        $responseData = $this->xmlComponent->getDecoderWorker()->decode(
            $xmlResponse
        );

        // Retornar respuesta.
        return new SiiRequestXmlDocumentSentStatusByEmailResponse(
            $responseData,
            $requestData
        );
    }
}

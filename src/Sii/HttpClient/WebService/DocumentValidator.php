<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\HttpClient\WebService;

use libredte\lib\Core\Helper\Date;
use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Sii\HttpClient\SiiClientException;
use libredte\lib\Core\Sii\HttpClient\TokenManager;
use libredte\lib\Core\Sii\HttpClient\WsdlConsumer;

/**
 * Clase para realizar las consultas de validación de documentos al SII.
 */
class DocumentValidator
{
    /**
     * Certificado digital.
     *
     * @var Certificate
     */
    private Certificate $certificate;

    /**
     * Cliente de la API SOAP del SII.
     *
     * @var WsdlConsumer
     */
    private WsdlConsumer $wsdlConsumer;

    /**
     * Administrador de tokens de autenticación del SII.
     *
     * @var TokenManager
     */
    private TokenManager $tokenManager;

    /**
     * Constructor de la clase que consume servicios web mediante WSDL del SII.
     *
     * @param Certificate $certificate
     * @param WsdlConsumer $wsdlConsumer
     * @param TokenManager $tokenManager
     */
    public function __construct(
        Certificate $certificate,
        WsdlConsumer $wsdlConsumer,
        TokenManager $tokenManager,
    )
    {
        $this->certificate = $certificate;
        $this->wsdlConsumer = $wsdlConsumer;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Obtiene el estado actualizado del envío de un documento XML al SII.
     *
     * Este estado podría no ser el final, si no es un estado final se debe
     * reintentar la consulta posteriormente al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf
     *
     * @param integer $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del XML que se envió.
     * @return DocumentUploadStatusResponse
     * @throws SiiClientException En caso de error.
     */
    public function getDocumentUploadStatus(
        int $trackId,
        string $company
    ): DocumentUploadStatusResponse
    {
        // Validar el RUT de la empresa que se utilizará para la consulta del
        // estado de envío al SII.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($this->certificate);

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
        } catch (SiiClientException $e) {
            throw new SiiClientException(sprintf(
                'No fue posible obtener el estado del XML enviado al SII con Track ID %s. %s',
                $trackId,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        return new DocumentUploadStatusResponse($xmlResponse, $requestData);
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
     * @param integer $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del documento.
     * @return DocumentUploadStatusEmailResponse
     * @throws SiiClientException En caso de error.
     */
    public function requestDocumentUploadStatusEmail(
        int $trackId,
        string $company
    ): DocumentUploadStatusEmailResponse
    {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        Rut::validate($company);
        [$rutCompany, $dvCompany] = Rut::toArray($company);

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($this->certificate);

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
        } catch (SiiClientException $e) {
            throw new SiiClientException(sprintf(
                'No fue posible solicitar el correo con el estado del envío %d al SII. %s',
                $trackId,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        return new DocumentUploadStatusEmailResponse($xmlResponse, $requestData);
    }

    /**
     * Obtiene el estado de un documento en el SII.
     *
     * Este estado solo se obtiene si el documento se encuentra aceptado por el
     * SII, ya sea aceptado 100% OK o con reparos.
     *
     * Este servicio valida que el documento exista en SII (esté aceptado) y
     * además que los datos del documento proporcionados coincidan.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/estado_dte.pdf
     *
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @return DocumentStatusResponse
     * @throws SiiClientException En caso de error.
     */
    public function getDocumentStatus(
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient
    ): DocumentStatusResponse
    {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        $user = $this->certificate->getID();
        Rut::validate($user);
        Rut::validate($company);
        Rut::validate($recipient);
        [$rutUser, $dvUser] = Rut::toArray($user);
        [$rutCompany, $dvCompany] = Rut::toArray($company);
        [$rutRecipient, $dvRecipient] = Rut::toArray($recipient);

        // Validar fecha y convertir al formato del SII.
        $dateSii = Date::validateAndConvert($date, 'dmY');
        if ($dateSii === null) {
            throw new SiiClientException(sprintf(
                'La fecha %s del documento no es válida, debe tener formato AAAA-MM-DD.',
                $date
            ));
        }

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($this->certificate);

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
        } catch (SiiClientException $e) {
            throw new SiiClientException(sprintf(
                'No fue posible obtener el estado del documento T%dF%d de %d-%s desde el SII. %s',
                $document,
                $number,
                $rutCompany,
                $dvCompany,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        return new DocumentStatusResponse($xmlResponse, $requestData);
    }

    /**
     * Obtiene el estado avanzado de un documento en el SII.
     *
     * Este estado solo se obtiene si el documento se encuentra aceptado por el
     * SII, ya sea aceptado 100% OK o con reparos.
     *
     * Este servicio valida que el documento exista en SII (esté aceptado), que
     * los datos del documento proporcionados coincidan. Finalmente, valida que
     * la firma electrónica del documento coincida con la enviada al SII.
     *
     * Referencia: https://www.sii.cl/factura_electronica/factura_mercado/OIFE2006_QueryEstDteAv_MDE.pdf
     *
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @param string $signature Tag DTE/Signature/SignatureValue del XML.
     * @return DocumentSignatureStatusResponse
     * @throws SiiClientException En caso de error.
     */
    public function getDocumentSignatureStatus(
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient,
        string $signature
    ): DocumentSignatureStatusResponse
    {
        // Validar los RUT que se utilizarán para la consulta de estado del DTE.
        Rut::validate($company);
        Rut::validate($recipient);
        [$rutCompany, $dvCompany] = Rut::toArray($company);
        [$rutRecipient, $dvRecipient] = Rut::toArray($recipient);

        // Validar fecha y convertir al formato del SII.
        $dateSii = Date::validateAndConvert($date, 'dmY');
        if ($dateSii === null) {
            throw new SiiClientException(sprintf(
                'La fecha %s del documento no es válida, debe tener formato AAAA-MM-DD.',
                $date
            ));
        }

        // Obtener el token asociado al certificado digital.
        $token = $this->tokenManager->getToken($this->certificate);

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
        } catch (SiiClientException $e) {
            throw new SiiClientException(sprintf(
                'No fue posible obtener el estado de la firma del documento T%dF%d de %d-%s desde el SII. %s',
                $document,
                $number,
                $rutCompany,
                $dvCompany,
                $e->getMessage()
            ));
        }

        // Armar estado del XML enviado y retornar.
        return new DocumentSignatureStatusResponse($xmlResponse, $requestData);
    }
}

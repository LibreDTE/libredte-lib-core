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
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiAuthenticateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiCheckXmlDocumentSentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRequestXmlDocumentSentStatusByEmailException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiSendXmlDocumentException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiValidateDocumentException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiValidateDocumentSignatureException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiCheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRequestXmlDocumentSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiValidateDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiValidateDocumentSignatureResponse;
use UnexpectedValueException;

/**
 * Interfaz del lazy worker del SII.
 */
interface SiiLazyWorkerInterface extends WorkerInterface
{
    /**
     * Realiza el envío de un documento XML al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param XmlInterface $doc Documento XML que se desea enviar al SII.
     * @param string $company RUT de la empresa emisora del XML.
     * @param bool $compress Indica si se debe enviar comprimido el XML.
     * @param int|null $retry Intentos que se realizarán como máximo al enviar.
     * @return int Número de seguimiento (Track ID) del envío del XML al SII.
     * @throws UnexpectedValueException Si alguno de los RUT son inválidos.
     * @throws SiiSendXmlDocumentException Si hay algún error al enviar el XML.
     */
    public function sendXmlDocument(
        SiiRequestInterface $request,
        XmlInterface $doc,
        string $company,
        bool $compress = false,
        ?int $retry = null
    ): int;

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
    public function checkXmlDocumentSentStatus(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): SiiCheckXmlDocumentSentStatusResponse;

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
    public function requestXmlDocumentSentStatusByEmail(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): SiiRequestXmlDocumentSentStatusByEmailResponse;

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
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @return SiiValidateDocumentResponse
     * @throws SiiValidateDocumentException En caso de error.
     */
    public function validateDocument(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient
    ): SiiValidateDocumentResponse;

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
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @param string $signature Tag DTE/Signature/SignatureValue del XML.
     * @return SiiValidateDocumentSignatureResponse
     * @throws SiiValidateDocumentSignatureException En caso de error.
     */
    public function validateDocumentSignature(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient,
        string $signature
    ): SiiValidateDocumentSignatureResponse;

    /**
     * Realiza una solicitud a un servicio web del SII mediante el uso de WSDL.
     *
     * Este método prepara y normaliza los datos recibidos y llama al método
     * que realmente hace la consulta al SII: callServiceFunction().
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $service Nombre del servicio del SII que se consumirá.
     * @param string $function Nombre de la función que se ejecutará en el
     * servicio web del SII.
     * @param array|int $args Argumentos que se pasarán al servicio web.
     * @param int|null $retry Intentos que se realizarán como máximo para
     * obtener respuesta.
     * @return XmlInterface Documento XML con la respuesta del servicio web.
     * @throws SiiConsumeWebserviceException En caso de error.
     */
    public function consumeWebservice(
        SiiRequestInterface $request,
        string $service,
        string $function,
        array|int $args = [],
        ?int $retry = null
    ): XmlInterface;

    /**
     * Obtiene un token de autenticación asociado al certificado digital.
     *
     * El token se busca primero en la caché, si existe, se reutilizará, si no
     * existe se solicitará uno nuevo al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return string El token asociado al certificado digital de la solicitud.
     * @throws SiiAuthenticateException Si hubo algún error al obtener el token.
     */
    public function authenticate(SiiRequestInterface $request): string;
}

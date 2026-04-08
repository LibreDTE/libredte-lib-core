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

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\CheckXmlDocumentSentStatusException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\RequestXmlDocumentSentStatusByEmailException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\SendXmlDocumentException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\ValidateDocumentException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDte\ValidateDocumentSignatureException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\CheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\RequestXmlDocumentSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\SendXmlDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentSignatureResponse;
use UnexpectedValueException;

/**
 * Interfaz del worker de DTE del SII.
 */
interface SiiDteWorkerInterface extends WorkerInterface
{
    /**
     * Realiza el envío de un documento XML al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param XmlDocumentInterface $doc Documento XML que se desea enviar al SII.
     * @param string $company RUT de la empresa emisora del XML.
     * @param bool $compress Indica si se debe enviar comprimido el XML.
     * @param int|null $retry Intentos que se realizarán como máximo al enviar.
     * @return SendXmlDocumentResponse Respuesta con el Track ID del envío.
     * @throws UnexpectedValueException Si alguno de los RUT son inválidos.
     * @throws SendXmlDocumentException Si hay algún error al enviar el XML.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/envio.pdf
     */
    public function sendXmlDocument(
        SiiRequestInterface $request,
        XmlDocumentInterface $doc,
        string $company,
        bool $compress = false,
        ?int $retry = null
    ): SendXmlDocumentResponse;

    /**
     * Obtiene el estado actualizado del envío de un documento XML al SII.
     *
     * Este estado podría no ser el final, si no es un estado final se debe
     * reintentar la consulta posteriormente al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del XML que se envió.
     * @return CheckXmlDocumentSentStatusResponse
     * @throws CheckXmlDocumentSentStatusException En caso de error.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/estado_envio.pdf
     */
    public function checkXmlDocumentSentStatus(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): CheckXmlDocumentSentStatusResponse;

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
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param int $trackId Número de seguimiento asignado al envío del XML.
     * @param string $company RUT de la empresa emisora del documento.
     * @return RequestXmlDocumentSentStatusByEmailResponse
     * @throws RequestXmlDocumentSentStatusByEmailException En caso de error.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/OIFE2005_wsDTECorreo_MDE.pdf
     */
    public function requestXmlDocumentSentStatusByEmail(
        SiiRequestInterface $request,
        int $trackId,
        string $company
    ): RequestXmlDocumentSentStatusByEmailResponse;

    /**
     * Obtiene el estado de un documento en el SII.
     *
     * Este estado solo se obtiene si el documento se encuentra aceptado por el
     * SII, ya sea aceptado 100% OK o con reparos.
     *
     * Este servicio valida que el documento exista en SII (esté aceptado) y
     * además que los datos del documento proporcionados coincidan.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @return ValidateDocumentResponse
     * @throws ValidateDocumentException En caso de error.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/estado_dte.pdf
     */
    public function validateDocument(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number,
        string $date,
        int $total,
        string $recipient
    ): ValidateDocumentResponse;

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
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT de la empresa emisora del documento.
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @param string $date Fecha de emisión del documento, formato: AAAA-MM-DD.
     * @param int $total Total del documento.
     * @param string $recipient RUT del receptor del documento.
     * @param string $signature Tag DTE/Signature/SignatureValue del XML.
     * @return ValidateDocumentSignatureResponse
     * @throws ValidateDocumentSignatureException En caso de error.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/OIFE2006_QueryEstDteAv_MDE.pdf
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
    ): ValidateDocumentSignatureResponse;
}

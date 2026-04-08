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
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\AuthenticateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\ConsumeWebserviceException;

/**
 * Interfaz del lazy worker del SII.
 */
interface SiiLazyWorkerInterface extends WorkerInterface
{
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
     * @param string|null $token Token de autenticación. Si se provee, se
     * establece como cookie TOKEN en el cliente SOAP (requerido por el RCV).
     * @return XmlDocumentInterface Documento XML con la respuesta del servicio web.
     * @throws ConsumeWebserviceException En caso de error.
     */
    public function consumeWebservice(
        SiiRequestInterface $request,
        string $service,
        string $function,
        array|int $args = [],
        ?int $retry = null,
        ?string $token = null
    ): XmlDocumentInterface;

    /**
     * Obtiene un token de autenticación asociado al certificado digital.
     *
     * El token se busca primero en la caché, si existe, se reutilizará, si no
     * existe se solicitará uno nuevo al SII.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @return string El token asociado al certificado digital de la solicitud.
     * @throws AuthenticateException Si hubo algún error al obtener el token.
     * @link https://www.sii.cl/factura_electronica/factura_mercado/autenticacion.pdf
     */
    public function authenticate(SiiRequestInterface $request): string;
}

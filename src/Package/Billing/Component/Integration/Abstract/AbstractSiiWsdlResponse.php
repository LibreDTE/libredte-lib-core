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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Abstract;

use InvalidArgumentException;

/**
 * Clase base para respuestas de los servicios web del SII utilizando SOAP.
 */
abstract class AbstractSiiWsdlResponse
{
    /**
     * Cabeceras que vienen en el XML de respuesta de la solicitud al SII.
     *
     * @var array
     */
    protected array $headers;

    /**
     * Cuerpo que viene en en XML de respuesta de la solicitud al SII.
     *
     * @var array
     */
    protected array $body;

    /**
     * Datos de las cabeceras y cuerpo normalizados para fácil manipulación.
     *
     * @var array
     */
    protected array $data;

    /**
     * Datos de la solicitud enviada al SII que generó esta respuesta.
     *
     * NOTE: no es obligatorio asignarlo, pero ayuda a tener el contexto
     * completo de la solicitud y respuesta del estado del documento al SII en
     * un único lugar (instancia).
     *
     * @var array
     */
    protected array $requestData;

    /**
     * Constructor que recibe la respuesta del SII y los datos de la solicitud.
     *
     * @param array $response Datos de la respuesta a la solicitud enviada.
     * @param array $request Datos de la solicitud original enviada.
     */
    public function __construct(array $response, array $request = [])
    {
        $this->headers = $response['SII:RESPUESTA']['SII:RESP_HDR'];
        $this->body = $response['SII:RESPUESTA']['SII:RESP_BODY'] ?? [];
        $this->requestData = $request;
    }

    /**
     * Obtiene los encabezados de la respuesta del SII.
     *
     * @return array Los encabezados de la respuesta.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Obtiene el cuerpo de la respuesta del SII.
     *
     * @return array El cuerpo de la respuesta.
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Obtiene los datos normalizados de la respuesta.
     *
     * @return array Datos normalizados de la respuesta del SII.
     */
    abstract public function getData();

    /**
     * Devuelve el estado de la solicitud realizada al SII.
     *
     * @return string El estado de la solicitud realizada al SII.
     */
    public function getStatus(): string
    {
        $data = $this->getData();

        return $data['status'];
    }

    /**
     * Devuelve la descripción de la solicitud realizada al SII.
     *
     * @return string La descripción de la solicitud realizada al SII.
     */
    public function getDescription(): string
    {
        $data = $this->getData();

        return $data['description'];
    }

    /**
     * Parsea el número de atención y la fecha/hora de un string.
     *
     * @param string $input Cadena con el número de atención y fecha/hora.
     * @return array Arreglo con el número y la fecha/hora en formato ISO.
     */
    protected function parseNumeroAtencion(string $input): array
    {
        // Normalizar espacios.
        $input = preg_replace('/\s+/', ' ', trim($input));

        // Extraer número y la fecha usando una expresión regular.
        if (preg_match(
            '/(\d+)\s+\(\s*(\d{4}\/\d{2}\/\d{2})\s+(\d{2}:\d{2}:\d{2})\s*\)/',
            $input,
            $matches
        )) {
            $number = $matches[1];

            // Formato ISO: YYYY/MM/DDTHH:MM:SS
            $datetime = $matches[2] . 'T' . $matches[3];

            // Convertir la fecha al formato ISO con guiones en vez de barras
            $datetimeISO = str_replace('/', '-', $datetime);

            return [(int) $number, $datetimeISO];
        }

        // Si el formato es incorrecto, lanzar una excepción.
        throw new InvalidArgumentException(sprintf(
            'El formato del número de atención "%s" es incorrecto.',
            $input
        ));
    }
}

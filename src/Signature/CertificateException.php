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

namespace libredte\lib\Core\Signature;

use Exception;
use Throwable;

/**
 * Clase de excepción personalizada para la firma electrónica.
 */
class CertificateException extends Exception
{
    /**
     * Errores específicos de la firma electrónica.
     *
     * @var array
     */
    private array $errors;

    /**
     * Listado de errores que podría entregar OpenSSL traducidos a un mensaje
     * entendible por humanos en español.
     *
     * NOTE: Las traducciones terminan sin punto a propósito pues se
     * concatenará el código de error entre paréntesis al final y ahí se
     * agregará el punto final del error.
     *
     * @var array
     */
    private $defaultOpensslTranslations = [
        '0308010C' => 'Algoritmo o método de cifrado no soportado',
        '11800071' => 'Falló la verificación MAC en PKCS12, el certificado o contraseña es incorrecto',
        '0906D06C' => 'No se pudo cargar el certificado X.509',
        '0B080074' => 'Formato PEM no válido',
        '0A000086' => 'Longitud de clave no permitida',
        '06065064' => 'Error en la clave privada: contraseña incorrecta',
        '14094418' => 'Error en la capa SSL: certificación no válida o CA no conocida',
        '14090086' => 'Error de configuración SSL: problema con el certificado o clave',
        '0907B068' => 'Error en la lectura de un archivo de certificado',
        '1403100E' => 'Error en SSL: protocolo no compatible',
    ];

    /**
     * Constructor de la excepción.
     *
     * @param string $message Mensaje de la excepción.
     * @param array $errors Arreglo con errores con los detalles.
     * @param int $code Código de la excepción (opcional).
     * @param Throwable|null $previous Excepción previa (opcional).
     */
    public function __construct(
        string $message,
        array $errors = [],
        int $code = 0,
        Throwable $previous = null
    )
    {
        if (empty($errors)) {
            while ($error = openssl_error_string()) {
                $errors[] = $error;
            }
        }
        $errors = $this->translateOpensslErrors($errors);

        $message = trim(sprintf(
            '%s %s',
            $message,
            implode(' ', $errors)
        ));

        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Obtiene los errores asociados a la excepción.
     *
     * @return array Arreglo de errores.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Traduce los errores de OpenSSL a mensajes más sencillos para humanos.
     *
     * @param array $errors Arreglo con los errores originales de OpenSSL.
     * @return array Arreglo con los errores traducidos.
     */
    private function translateOpensslErrors(array $errors): array
    {
        // Definir reglas de traducción.
        $translations = $this->defaultOpensslTranslations;

        // Traducir los errores.
        foreach ($errors as &$error) {
            foreach ($translations as $code => $trans) {
                if (str_contains($error, 'error:' . $code)) {
                    $error = sprintf('%s (Error #%s).', $trans, $code);
                }
            }
        }

        // Entregar errores traducidos.
        return $errors;
    }
}

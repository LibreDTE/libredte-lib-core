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

namespace libredte\lib\Core\Xml;

use Exception;
use LibXMLError;
use Throwable;

/**
 * Excepción personalizada para errores asociados a los XML.
 */
class XmlException extends Exception
{
    /**
     * Arreglo con los errores.
     *
     * @var array
     */
    private array $errors;

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
    ) {
        $message = trim(sprintf(
            '%s %s',
            $message,
            implode(' ', $this->libXmlErrorToString($errors))
        ));

        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Obtiene el arreglo con los errores.
     *
     * @return array Arreglo con los errores.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Procesa un arreglo de errores, probablemente de LibXMLError, y los
     * entrega como un arreglo de strings.
     *
     * @param array $errors
     * @return array
     */
    private function libXmlErrorToString(array $errors): array
    {
        return array_map(function ($error) {
            if ($error instanceof LibXMLError) {
                return sprintf(
                    'Error %s: %s en la línea %d, columna %d (Código: %d).',
                    $error->level === LIBXML_ERR_WARNING ? 'Advertencia' :
                    ($error->level === LIBXML_ERR_ERROR ? 'Error' : 'Fatal'),
                    trim($error->message),
                    $error->line,
                    $error->column,
                    $error->code
                );
            }

            return $error;
        }, $errors);
    }
}

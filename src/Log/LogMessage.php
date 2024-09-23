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

namespace libredte\lib\Core\Log;

/**
 * Clase que representa un mensaje del Log.
 */
class LogMessage
{
    /**
     * Código del error.
     *
     * @var int
     */
    public int $code;

    /**
     * Descripción o glosa del error.
     *
     * @var string|null
     */
    public ?string $message;

    /**
     * Archivo donde se llamó al log.
     *
     * @var string|null
     */
    public ?string $file = null;

    /**
     * Línea del archivo donde se llamó al log.
     *
     * @var int|null
     */
    public ?int $line = null;

    /**
     * Método que llamó al log.
     *
     * @var string|null
     */
    public ?string $function = null;

    /**
     * Clase del método que llamó al log.
     *
     * @var string|null
     */
    public ?string $class = null;

    /**
     * Tipo de llamada (estática o de objeto instanciado).
     *
     * @var string|null
     */
    public ?string $type = null;

    /**
     * Contexto adicional del mensaje.
     *
     * @var array|null
     */
    public ?array $context = null;

    /**
     * Constructor del mensaje.
     *
     * @param int $code Código del error.
     * @param string|null $message Mensaje del error.
     */
    public function __construct(int $code, ?string $message = null)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Método mágico para obtener el mensaje como string a partir del objeto.
     *
     * @return string Mensaje formateado como string.
     */
    public function __toString(): string
    {
        $message = $this->message ?: sprintf('Error código %s', $this->code);
        if (!$this->file) {
            return $message;
        }

        return sprintf(
            '%s (in %s on line %d, called by %s%s%s())',
            $message,
            $this->file,
            $this->line,
            $this->class,
            $this->type,
            $this->function
        );
    }
}

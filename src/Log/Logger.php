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
 * Clase para manejar mensajes generados en la aplicación de forma "silenciosa"
 * y luego poder recuperarlos para procesar en la aplicación.
 *
 * Los mensajes estarán disponibles solo durante la ejecución del script PHP.
 * Una vez termina, los mensajes se pierden. Es importante recuperarlos antes
 * de que termine la ejecución de la página si se desea hacer algo con ellos.
 */
class Logger
{
    /**
     * Almacenamiento en memoria para los logs.
     *
     * @var array
     */
    private array $logStorage = [];

    /**
     * Define si se usa o no backtrace.
     *
     * @var bool
     */
    private bool $useBacktrace = false;

    /**
     * Activa o desactiva el backtrace para los mensajes que se escribirán en
     * la bitácora.
     *
     * @param bool $backtrace Define si se activa o no el backtrace.
     * @return void
     */
    public function enableBacktrace(bool $backtrace = true): void
    {
        $this->useBacktrace = $backtrace;
    }

    /**
     * Registra un mensaje en el log.
     *
     * @param int $level Nivel de severidad del log.
     * @param string $message El mensaje que se desea registrar.
     * @param array $context Contexto adicional para el mensaje.
     * @return void
     */
    private function log(int $level, string $message, array $context = []): void
    {
        $code = $context['code'] ?? $level;
        $logMessage = new LogMessage($code, $message);

        if ($this->useBacktrace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $logMessage->file = $trace[1]['file'] ?? null;
            $logMessage->line = $trace[1]['line'] ?? null;
            $logMessage->function = $trace[2]['function'] ?? null;
            $logMessage->class = $trace[2]['class'] ?? null;
            $logMessage->type = $trace[2]['type'] ?? null;
        }

        $logMessage->context = $context;

        $this->logStorage[$level][] = $logMessage;
    }

    /**
     * Registra un mensaje de nivel ERROR en el log.
     *
     * @param string $message El mensaje que se desea registrar.
     * @param array $context Contexto adicional para el mensaje.
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LOG_ERR, $message, $context);
    }

    /**
     * Registra un mensaje de nivel WARNING en el log.
     *
     * @param string $message El mensaje que se desea registrar.
     * @param array $context Contexto adicional para el mensaje.
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LOG_WARNING, $message, $context);
    }

    /**
     * Registra un mensaje de nivel INFO en el log.
     *
     * @param string $message El mensaje que se desea registrar.
     * @param array $context Contexto adicional para el mensaje.
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LOG_INFO, $message, $context);
    }

    /**
     * Registra un mensaje de nivel DEBUG en el log.
     *
     * @param string $message El mensaje que se desea registrar.
     * @param array $context Contexto adicional para el mensaje.
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LOG_DEBUG, $message, $context);
    }

    /**
     * Recupera los logs de un nivel específico.
     *
     * @param int $level Nivel de severidad de los logs que se desean recuperar.
     * @return LogMessage[] Arreglo con los logs del nivel especificado.
     */
    public function getLogs(int $level): array
    {
        return $this->logStorage[$level] ?? [];
    }

    /**
     * Recupera todos los logs almacenados.
     *
     * @param bool $newFirst Indica si los logs deben ordenarse de nuevo primero.
     * @return LogMessage[] Arreglo con todos los logs.
     */
    public function getAllLogs(bool $newFirst = true): array
    {
        $allLogs = [];
        foreach ($this->logStorage as $level => $logs) {
            $allLogs[$level] = $newFirst ? array_reverse($logs) : $logs;
        }
        return $allLogs;
    }

    /**
     * Recupera y limpia los logs de un nivel de severidad específico.
     *
     * Este método permite recuperar todos los mensajes de log de un nivel de
     * severidad determinado y, después de recuperarlos, los elimina de la
     * memoria. Los mensajes se pueden ordenar de modo que los más recientes
     * aparezcan primero.
     *
     * @param int $severity Nivel de severidad de los logs que se desean
     * recuperar. Por defecto es LOG_ERR.
     * @param bool $newFirst Indica si los logs deben ser ordenados con los más
     * nuevos primero. Por defecto es true.
     * @return array Arreglo con los mensajes de log recuperados del nivel de
     * severidad especificado. Si no hay logs, se devuelve un arreglo vacío.
     */
    public function flushLogs(int $severity = LOG_ERR, bool $newFirst = true): array
    {
        if (!isset($this->logStorage[$severity])) {
            return [];
        }

        $logs = $this->logStorage[$severity];

        // Ordenar logs si se requiere que los más nuevos estén primero.
        if ($newFirst) {
            $logs = array_reverse($logs);
        }

        // Limpiar los logs después de leerlos.
        $this->clearLogs($severity);

        return $logs;
    }

    /**
     * Limpia los logs de un nivel específico.
     *
     * @param int $level Nivel de severidad de los logs que se desean limpiar.
     * @return void
     */
    public function clearLogs(int $level): void
    {
        unset($this->logStorage[$level]);
    }

    /**
     * Limpia todos los logs almacenados.
     *
     * @return void
     */
    public function clearAllLogs(): void
    {
        $this->logStorage = [];
    }
}

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

namespace libredte\lib\Tests\Functional\Log;

use libredte\lib\Core\Log\Logger;
use libredte\lib\Core\Log\LogMessage;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Logger::class)]
#[CoversClass(LogMessage::class)]
class LoggerTest extends TestCase
{
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger();
    }

    public function testErrorLogging(): void
    {
        $this->logger->error('Test error message');

        $logs = $this->logger->getLogs(LOG_ERR);
        $this->assertCount(1, $logs);

        $logMessage = $logs[0];
        $this->assertInstanceOf(LogMessage::class, $logMessage);
        $this->assertEquals(LOG_ERR, $logMessage->code);
        $this->assertEquals('Test error message', $logMessage->message);
        $this->assertNull($logMessage->file);
        $this->assertNull($logMessage->line);
    }

    public function testWarningLogging(): void
    {
        $this->logger->warning('Test warning message');

        $logs = $this->logger->getLogs(LOG_WARNING);
        $this->assertCount(1, $logs);

        $logMessage = $logs[0];
        $this->assertInstanceOf(LogMessage::class, $logMessage);
        $this->assertEquals(LOG_WARNING, $logMessage->code);
        $this->assertEquals('Test warning message', $logMessage->message);
        $this->assertNull($logMessage->file);
        $this->assertNull($logMessage->line);
    }

    public function testInfoLogging(): void
    {
        $this->logger->info('Test info message');

        $logs = $this->logger->getLogs(LOG_INFO);
        $this->assertCount(1, $logs);

        $logMessage = $logs[0];
        $this->assertInstanceOf(LogMessage::class, $logMessage);
        $this->assertEquals(LOG_INFO, $logMessage->code);
        $this->assertEquals('Test info message', $logMessage->message);
        $this->assertNull($logMessage->file);
        $this->assertNull($logMessage->line);
    }

    public function testDebugLogging(): void
    {
        $this->logger->debug('Test debug message');

        $logs = $this->logger->getLogs(LOG_DEBUG);
        $this->assertCount(1, $logs);

        $logMessage = $logs[0];
        $this->assertInstanceOf(LogMessage::class, $logMessage);
        $this->assertEquals(LOG_DEBUG, $logMessage->code);
        $this->assertEquals('Test debug message', $logMessage->message);
        $this->assertNull($logMessage->file);
        $this->assertNull($logMessage->line);
    }

    public function testLogWithBacktrace(): void
    {
        $this->logger->enableBacktrace(true);
        $this->logger->error('Backtrace error message');

        $logs = $this->logger->getLogs(LOG_ERR);
        $this->assertCount(1, $logs);

        $logMessage = $logs[0];
        $this->assertInstanceOf(LogMessage::class, $logMessage);
        $this->assertEquals(LOG_ERR, $logMessage->code);
        $this->assertEquals('Backtrace error message', $logMessage->message);
        $this->assertNotNull($logMessage->file);
        $this->assertNotNull($logMessage->line);
        $this->assertEquals('testLogWithBacktrace', $logMessage->function);
    }

    public function testFlushLogs(): void
    {
        $this->logger->error('First error message');
        $this->logger->warning('First warning message');
        $this->logger->error('Second error message');

        $logs = $this->logger->flushLogs(LOG_ERR);

        $this->assertCount(2, $logs);
        $this->assertEquals('Second error message', $logs[0]->message);
        $this->assertEquals('First error message', $logs[1]->message);

        $logsAfterFlush = $this->logger->getLogs(LOG_ERR);
        $this->assertEmpty($logsAfterFlush);
    }

    public function testClearLogs(): void
    {
        $this->logger->error('Error message');
        $this->logger->clearLogs(LOG_ERR);

        $logs = $this->logger->getLogs(LOG_ERR);
        $this->assertEmpty($logs);
    }

    public function testClearAllLogs(): void
    {
        $this->logger->error('Error message');
        $this->logger->warning('Warning message');
        $this->logger->clearAllLogs();

        $allLogs = $this->logger->getAllLogs();
        $this->assertEmpty($allLogs);
    }

    public function testLogWithContext(): void
    {
        $context = ['key' => 'value'];
        $this->logger->info('Info message with context', $context);

        $logs = $this->logger->getLogs(LOG_INFO);
        $this->assertCount(1, $logs);

        $logMessage = $logs[0];
        $this->assertEquals($context, $logMessage->context);
    }

    /**
     * Se verifica que lo que se escriba al log se pueda leer todo de vuelta.
     */
    public function testWriteReadAll(): void
    {
        // Log que se probará.
        $logs = [
            LOG_ERR => [
                'Error N° 1',
                'Ejemplo error dos',
                'Este es el tercer error',
            ],
            LOG_WARNING => [
                'Este es el primer warning',
                'Un segundo warning',
                'El penúltimo warning',
                'El warning final (4to)'
            ],
        ];

        // Se verificará leyendo el log en ambos ordenes (más nuevo a más viejo
        // y más viejo a más nuevo).
        foreach ([true, false] as $new_first) {

            // Escribir al log.
            foreach ($logs as $severity => $mensajes) {
                foreach ($mensajes as $codigo => $mensaje) {
                    if ($severity === LOG_ERR) {
                        $this->logger->error($mensaje, ['code' => $codigo]);
                    } else if ($severity === LOG_WARNING) {
                        $this->logger->warning($mensaje, ['code' => $codigo]);
                    }
                }
            }

            // Revisar lo que se escribió al log.
            foreach ($logs as $severity => $mensajes) {
                $registros = $this->logger->flushLogs($severity, $new_first);
                $this->assertNotEmpty($registros);
                $this->assertCount(count($logs[$severity]), $registros);

                if ($new_first) {
                    krsort($mensajes);
                }

                foreach ($mensajes as $codigo => $mensaje) {
                    $Log = array_shift($registros);
                    $this->assertEquals($codigo, $Log->code);
                    $this->assertEquals($mensaje, $Log->message);
                }
            }
        }
    }
}

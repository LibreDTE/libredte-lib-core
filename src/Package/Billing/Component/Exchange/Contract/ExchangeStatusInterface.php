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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

use Derafu\Lib\Core\Support\Store\Contract\BagInterface;
use Throwable;

/**
 * Interfaz para el estado del resultado de una estrategia de intercambio de un
 * sobre específico.
 */
interface ExchangeStatusInterface
{
    /**
     * Entrega el código de la estrategia que generó este estado de intercambio
     * de un sobre.
     *
     * @return string
     */
    public function getStrategy(): string;

    /**
     * Indica si el estado del resultado de la ejecución de la estrategia al
     * procesar el sobre fue OK (se logró procesar sin problemas).
     *
     * @return bool
     */
    public function isOk(): bool;

    /**
     * Asigna el error que ocurrió al procesar el sobre.
     *
     * @param Throwable $error
     * @return static
     */
    public function setError(Throwable $error): static;

    /**
     * Entrega el error o excepción que se generó al procesar el sobre.
     *
     * @return Throwable|null
     */
    public function getError(): ?Throwable;

    /**
     * Indica si el estado tiene un error.
     *
     * @return bool
     */
    public function hasError(): bool;

    /**
     * Asigna los metadatos del estado del resultado.
     *
     * @param BagInterface|array $metadata
     * @return static
     */
    public function setMetadata(BagInterface|array $metadata): static;

    /**
     * Agrega una clave específica a los metadatos.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addMetadata(string $key, mixed $value): static;

    /**
     * Obtiene los metadatos del estado del resultado.
     *
     * @return BagInterface
     */
    public function getMetadata(): BagInterface;
}

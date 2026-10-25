<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Book\Contract;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsAwareInterface;
use JsonSerializable;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Interfaz para el contenedor de datos de un libro tributario.
 *
 * Transporta el tipo de libro, la carátula, los detalles, el certificado
 * digital y las opciones de procesamiento.
 *
 * El flujo normal con los workers de `billing.book`:
 *   1. El usuario crea un bag con `tipo`, `caratula` y `detalles` crudos.
 *   2. `LoaderWorker` normaliza los detalles según tipo y formato de entrada.
 *   3. `BuilderWorker` genera el XML y retorna la entidad libro resultante.
 */
interface BookBagInterface extends OptionsAwareInterface, JsonSerializable
{
    /**
     * Obtiene el tipo de libro tributario.
     *
     * Determina qué estrategias se usan los workers.
     *
     * @return TipoLibro
     */
    public function getTipo(): TipoLibro;

    /**
     * Asigna los datos de entrada del libro.
     *
     * Si es un arreglo son los datos crudos. Si es un string podrá ser un CSV,
     * XML, JSON, etc, dependiendo del formato de entrada en las opciones del
     * `LoaderWorker`.
     *
     * @param array|string $inputData
     * @return static
     */
    public function setInputData(array|string $inputData): static;

    /**
     * Obtiene los datos de entrada del libro.
     *
     * Si es un arreglo son los datos crudos. Si es un string podrá ser un CSV,
     * XML, JSON, etc, dependiendo del formato de entrada en las opciones del
     * `LoaderWorker`.
     *
     * @return array|string
     */
    public function getInputData(): array|string;

    /**
     * Asigna la carátula del libro.
     *
     * La estructura del arreglo varía según el tipo de libro:
     *
     *   - Compras/Ventas: `RutEmisorLibro`, `PeriodoTributario`,
     *     `TipoOperacion`, `TipoLibro`, `TipoEnvio`, `FchResol`, `NroResol`.
     *   - Boletas/Guías: igual que compras/ventas pero sin `TipoOperacion`.
     *   - RVD: `RutEmisor`, `FchResol`, `NroResol`, `Correlativo`, `SecEnvio`
     *     (sin `PeriodoTributario`; las fechas se calculan desde los detalles).
     *
     * @param array<string, mixed> $caratula
     * @return static
     */
    public function setCaratula(array $caratula): static;

    /**
     * Obtiene la carátula del libro.
     *
     * @return array<string, mixed>
     */
    public function getCaratula(): array;

    /**
     * Asigna los detalles del libro.
     *
     * Es el detalle normalizado con todos los campos en el orden correcto que
     * espera el `BuilderWorker` para construir el XML del libro.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return static
     */
    public function setDetalle(array $detalles): static;

    /**
     * Obtiene los detalles del libro.
     *
     * Es el detalle normalizado con todos los campos en el orden correcto que
     * espera el `BuilderWorker` para construir el XML del libro.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDetalle(): array;

    /**
     * Obtiene las opciones del `LoaderWorker`.
     *
     * La clave `format` determina el formato de entrada (por defecto 'array').
     * El `LoaderWorker` construye el nombre de estrategia como `{tipo}.{format}`.
     *
     * @return array<string, mixed>
     */
    public function getLoaderOptions(): array;

    /**
     * Obtiene las opciones del `BuilderWorker`.
     *
     * @return array<string, mixed>
     */
    public function getBuilderOptions(): array;

    /**
     * Obtiene las opciones del `RendererWorker`.
     *
     * @return array<string, mixed>
     */
    public function getRendererOptions(): array;

    /**
     * Obtiene las opciones del `ValidatorWorker`.
     *
     * @return array<string, mixed>
     */
    public function getValidatorOptions(): array;

    /**
     * Asigna el libro resultante de la construcción.
     *
     * @param BookInterface $book
     * @return static
     */
    public function setBook(BookInterface $book): static;

    /**
     * Obtiene el libro resultante de la construcción.
     *
     * Retorna `null` si el `BuilderWorker` aún no ha completado la construcción.
     *
     * @return BookInterface|null
     */
    public function getBook(): ?BookInterface;

    /**
     * Asigna el certificado digital para firmar el XML del libro.
     *
     * Si no se proporciona certificado, el XML se genera sin firma electrónica.
     *
     * @param CertificateInterface $certificate
     * @return static
     */
    public function setCertificate(CertificateInterface $certificate): static;

    /**
     * Obtiene el certificado digital.
     *
     * @return CertificateInterface|null
     */
    public function getCertificate(): ?CertificateInterface;

    /**
     * Asigna el emisor del libro.
     *
     * @param EmisorInterface $emisor
     * @return static
     */
    public function setEmisor(EmisorInterface $emisor): static;

    /**
     * Obtiene el emisor del libro.
     *
     * @return EmisorInterface|null
     */
    public function getEmisor(): ?EmisorInterface;

    /**
     * Retorna una nueva bolsa con el mismo contenido pero con el certificado
     * reemplazado.
     *
     * @param CertificateInterface $certificate
     * @return static
     */
    public function withCertificate(CertificateInterface $certificate): static;

    /**
     * Obtiene los datos del libro.
     *
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * Obtiene los datos de la autorización del emisor para emitir el libro.
     *
     * @return array|null
     */
    public function getBookAuth(): ?array;

    /**
     * Entrega los datos de la bolsa como un arreglo.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}

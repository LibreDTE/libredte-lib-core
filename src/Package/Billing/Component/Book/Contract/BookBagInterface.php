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
     * Antes de pasar por el `LoaderWorker` los detalles son datos crudos.
     * Después del loader están normalizados con todos los campos en orden.
     *
     * @param array<int, array<string, mixed>> $detalles
     * @return static
     */
    public function setDetalle(array $detalles): static;

    /**
     * Obtiene los detalles del libro.
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
     * @return array
     */
    public function toArray(): array;
}

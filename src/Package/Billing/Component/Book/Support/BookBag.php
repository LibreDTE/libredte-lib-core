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

namespace libredte\lib\Core\Package\Billing\Component\Book\Support;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Trait\OptionsAwareTrait;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookBagInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Contract\BookInterface;
use libredte\lib\Core\Package\Billing\Component\Book\Enum\TipoLibro;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Contenedor de datos para la construcción de un libro tributario.
 *
 * Permite transportar el tipo de libro, la carátula, los detalles, el
 * certificado digital y las opciones de procesamiento entre el código cliente
 * y los workers de `billing.book`.
 *
 * El flujo normal es:
 *   1. El usuario crea un `BookBag` con `tipo`, `caratula` y `detalles` crudos.
 *   2. `LoaderWorker::load()` normaliza los detalles según el tipo y formato.
 *   3. `BuilderWorker::build()` genera el XML y retorna la entidad resultante.
 */
class BookBag implements BookBagInterface
{
    use OptionsAwareTrait;

    /**
     * Esquema de las opciones del contenedor.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $optionsSchema = [
        'loader' => [
            'types' => 'array',
            'default' => [],
        ],
        'builder' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * Tipo de libro tributario.
     *
     * Determina qué estrategias del LoaderWorker y BuilderWorker se utilizan.
     *
     * @var TipoLibro
     */
    private TipoLibro $tipo;

    /**
     * Carátula del libro.
     *
     * @var array<string, mixed>
     */
    private array $caratula;

    /**
     * Detalles del libro.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $detalle;

    /**
     * Certificado digital para la firma del XML.
     *
     * @var CertificateInterface|null
     */
    private ?CertificateInterface $certificate;

    /**
     * Libro resultante tras la construcción por el worker.
     *
     * @var BookInterface|null
     */
    private ?BookInterface $book;

    /**
     * Emisor del libro.
     *
     * @var EmisorInterface|null
     */
    private ?EmisorInterface $emisor;

    /**
     * Constructor del contenedor.
     *
     * @param TipoLibro $tipo Tipo de libro tributario.
     * @param array<string, mixed> $caratula Datos de la carátula del libro.
     * @param array<int, array<string, mixed>> $detalle Filas del libro.
     * @param CertificateInterface|null $certificate Certificado para firmar.
     * @param array<string, mixed>|OptionsInterface|null $options Opciones de
     *   procesamiento. La clave `loader.format` elige el formato de entrada
     *   (por defecto 'array').
     */
    public function __construct(
        TipoLibro $tipo,
        array $caratula = [],
        array $detalle = [],
        array|OptionsInterface|null $options = null,
        ?CertificateInterface $certificate = null,
        BookInterface|null $book = null,
        EmisorInterface|null $emisor = null,
    ) {
        $this->tipo = $tipo;
        $this->caratula = $caratula;
        $this->detalle = $detalle;
        $this->certificate = $certificate;
        $this->book = $book;
        $this->emisor = $emisor;
        $this->setOptions($options ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public function getTipo(): TipoLibro
    {
        return $this->tipo;
    }

    /**
     * {@inheritDoc}
     */
    public function setCaratula(array $caratula): static
    {
        $this->caratula = $caratula;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCaratula(): array
    {
        if (!isset($this->caratula) && isset($this->book)) {
            $this->caratula = $this->book->getCaratula();
        }

        return $this->caratula;
    }

    /**
     * {@inheritDoc}
     */
    public function setDetalle(array $detalles): static
    {
        $this->detalle = $detalles;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDetalle(): array
    {
        if (!isset($this->detalle) && isset($this->book)) {
            $this->detalle = $this->book->getDetalle();
        }

        return $this->detalle;
    }

    /**
     * {@inheritDoc}
     */
    public function getLoaderOptions(): array
    {
        return $this->getOptions()->get('loader')?->all() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getBuilderOptions(): array
    {
        return $this->getOptions()->get('builder')?->all() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getRendererOptions(): array
    {
        return $this->getOptions()->get('renderer')?->all() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getValidatorOptions(): array
    {
        return $this->getOptions()->get('validator')?->all() ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function setBook(BookInterface $book): static
    {
        $this->book = $book;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBook(): ?BookInterface
    {
        return $this->book;
    }

    /**
     * {@inheritDoc}
     */
    public function setCertificate(CertificateInterface $certificate): static
    {
        $this->certificate = $certificate;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificate(): ?CertificateInterface
    {
        return $this->certificate;
    }

    /**
     * {@inheritDoc}
     */
    public function setEmisor(EmisorInterface $emisor): static
    {
        $this->emisor = $emisor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmisor(): ?EmisorInterface
    {
        return $this->emisor;
    }

    /**
     * {@inheritDoc}
     */
    public function withCertificate(CertificateInterface $certificate): static
    {
        $clone = clone $this;
        $clone->certificate = $certificate;

        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): ?array
    {
        return $this->getBook()?->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getBookAuth(): ?array
    {
        return $this->getEmisor()?->getAutorizacionDte()?->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'book' => $this->getData(),
            'book_auth' => $this->getBookAuth(),
            'book_type' => $this->getTipo()->toArray(),
            'options' => $this->getOptions()->all(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

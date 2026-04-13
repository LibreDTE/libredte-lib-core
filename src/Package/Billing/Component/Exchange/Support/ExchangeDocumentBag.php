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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Support;

use Derafu\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\TipoDocumentoRespuesta;

/**
 * Contenedor de datos para la construcción de un documento de respuesta al
 * intercambio de DTE.
 *
 * Permite transportar el tipo de documento, la carátula, los datos, el
 * certificado digital y el documento resultante entre el código cliente y el
 * `DocumentResponseWorker`.
 *
 * Estructura de `datos` según `tipo`:
 *   - `ENVIO_RECIBOS`: lista de recibos, donde cada recibo es un arreglo con
 *     claves `TipoDoc`, `Folio`, `FchEmis`, `RUTEmisor`, `RUTRecep`,
 *     `MntTotal`, `Recinto` y opcionalmente `RutFirma`, `Declaracion`.
 *   - `RESPUESTA_ENVIO`: arreglo asociativo con clave `recepcion_envio`
 *     (lista de `RecepcionEnvio`) o `resultado_dte` (lista de `ResultadoDTE`).
 */
class ExchangeDocumentBag
{
    /**
     * Tipo de documento de respuesta.
     */
    private TipoDocumentoRespuesta $tipo;

    /**
     * Carátula del documento.
     *
     * @var array<string, mixed>
     */
    private array $caratula;

    /**
     * Datos del documento (recibos o respuestas).
     *
     * @var array<mixed>
     */
    private array $data;

    /**
     * Certificado digital para la firma del XML.
     */
    private ?CertificateInterface $certificate;

    /**
     * Documento resultante tras la construcción.
     */
    private ?AbstractExchangeDocument $document;

    /**
     * Constructor del contenedor.
     *
     * @param TipoDocumentoRespuesta $tipo Tipo de documento de respuesta.
     * @param array<string, mixed> $caratula Datos de la carátula.
     * @param array<mixed> $data Recibos o respuestas según el tipo.
     * @param CertificateInterface|null $certificate Certificado para firmar.
     * @param AbstractExchangeDocument|null $document Documento ya construido.
     */
    public function __construct(
        TipoDocumentoRespuesta $tipo,
        array $caratula = [],
        array $data = [],
        ?CertificateInterface $certificate = null,
        ?AbstractExchangeDocument $document = null,
    ) {
        $this->tipo = $tipo;
        $this->caratula = $caratula;
        $this->data = $data;
        $this->certificate = $certificate;
        $this->document = $document;
    }

    /**
     * Entrega el tipo de documento de respuesta.
     *
     * @return TipoDocumentoRespuesta
     */
    public function getTipo(): TipoDocumentoRespuesta
    {
        return $this->tipo;
    }

    /**
     * Entrega la carátula del documento.
     *
     * @return array<string, mixed>
     */
    public function getCaratula(): array
    {
        return $this->caratula;
    }

    /**
     * Asigna la carátula del documento.
     *
     * @param array<string, mixed> $caratula
     * @return static
     */
    public function setCaratula(array $caratula): static
    {
        $this->caratula = $caratula;

        return $this;
    }

    /**
     * Entrega los datos del documento.
     *
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Asigna los datos del documento.
     *
     * @param array<mixed> $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Entrega el certificado digital.
     *
     * @return CertificateInterface|null
     */
    public function getCertificate(): ?CertificateInterface
    {
        return $this->certificate;
    }

    /**
     * Asigna el certificado digital.
     *
     * @param CertificateInterface $certificate
     * @return static
     */
    public function setCertificate(CertificateInterface $certificate): static
    {
        $this->certificate = $certificate;

        return $this;
    }

    /**
     * Entrega el documento resultante.
     *
     * @return AbstractExchangeDocument|null
     */
    public function getDocument(): ?AbstractExchangeDocument
    {
        return $this->document;
    }

    /**
     * Asigna el documento resultante.
     *
     * @param AbstractExchangeDocument $document
     * @return static
     */
    public function setDocument(AbstractExchangeDocument $document): static
    {
        $this->document = $document;

        return $this;
    }
}

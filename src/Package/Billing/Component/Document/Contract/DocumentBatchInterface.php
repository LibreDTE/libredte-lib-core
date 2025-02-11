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

namespace libredte\lib\Core\Package\Billing\Component\Document\Contract;

use Derafu\Lib\Core\Common\Contract\OptionsAwareInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Interfaz para el contenedor de varios documentos que se procesarán en lote.
 */
interface DocumentBatchInterface extends OptionsAwareInterface
{
    /**
     * Entrega la ruta del archivo con documentos que se debe procesar.
     *
     * @return string
     */
    public function getFile(): string;

    /**
     * Asigna el emisor del documento.
     *
     * @param EmisorInterface|null $emisor
     * @return static
     */
    public function setEmisor(?EmisorInterface $emisor): static;

    /**
     * Obtiene el emisor del documento.
     *
     * @return EmisorInterface|null
     */
    public function getEmisor(): ?EmisorInterface;

    /**
     * Asigna el certificado para firmar el documento.
     *
     * @param CertificateInterface|null $certificate
     * @return static
     */
    public function setCertificate(?CertificateInterface $certificate): static;

    /**
     * Obtiene el certificado para firmar el documento.
     *
     * @return CertificateInterface|null
     */
    public function getCertificate(): ?CertificateInterface;

    /**
     * Asigna el listado de bolsas con documentos procesados en lote.
     *
     * @param DocumentBagInterface[] $bags
     * @return static
     */
    public function setDocumentBags(array $bags): static;

    /**
     * Obtiene el listado de bolsas con documentos procesados en lote.
     *
     * @return DocumentBagInterface[]
     */
    public function getDocumentBags(): array;

    /**
     * Obtiene las opciones del procesamiento en lote.
     *
     * @return array Opciones del batch processor.
     */
    public function getBatchProcessorOptions(): array;
}

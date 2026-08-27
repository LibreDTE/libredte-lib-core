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

namespace libredte\lib\Core\Package\Billing\Component\Document\Support;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\RenderedDocumentInterface;

/**
 * Clase para representar un archivo generado por un renderizador de
 * documentos.
 */
class RenderedDocument implements RenderedDocumentInterface
{
    /**
     * Constructor del archivo renderizado.
     *
     * @param string $content Datos binarios (o de texto) del renderizado.
     * @param string $mimeType Tipo MIME del renderizado (ej. `application/pdf`).
     * @param string|null $filename Nombre sugerido para el archivo.
     * @param string|null $label Identificador de la presentación con la que
     * se generó (ej. `tributaria`, `cedible`).
     * @param int $copies Cantidad de copias que representa este archivo.
     */
    public function __construct(
        private readonly string $content,
        private readonly string $mimeType,
        private readonly ?string $filename = null,
        private readonly ?string $label = null,
        private readonly int $copies = 1
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getCopies(): int
    {
        return $this->copies;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'content' => $this->getContent(),
            'mimeType' => $this->getMimeType(),
            'filename' => $this->getFilename(),
            'label' => $this->getLabel(),
            'copies' => $this->getCopies(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['content'] = base64_encode($array['content']);

        return $array;
    }
}

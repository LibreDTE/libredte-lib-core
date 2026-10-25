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

namespace libredte\lib\Core\Package\System\Component\Rendering\Support;

use libredte\lib\Core\Package\System\Component\Rendering\Contract\RenderedDocumentInterface;

/**
 * Clase para representar un archivo generado por un renderizador.
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
     *
     * @return array{
     *     content: string,
     *     mimeType: string,
     *     filename: string|null,
     *     label: string|null,
     *     copies: int
     * }
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
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['content'] = base64_encode($array['content']);

        return $array;
    }
}

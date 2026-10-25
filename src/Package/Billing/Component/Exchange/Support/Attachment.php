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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Support;

use InvalidArgumentException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\AttachmentInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

/**
 * Clase para representar un archivo adjunto en un documento.
 */
class Attachment extends DataPart implements AttachmentInterface
{
    /**
     * Tamaño del archivo.
     *
     * @var int
     */
    private int $size;

    /**
     * Constructor del archivo adjunto.
     *
     * @param string|File $body
     * @param string|null $filename
     * @param string|null $contentType
     * @param string|null $encoding
     * @param int|null $size
     */
    public function __construct(
        string|File $body,
        ?string $filename = null,
        ?string $contentType = null,
        ?string $encoding = null,
        ?int $size = null
    ) {
        if ($contentType === null) {
            $contentType = $this->guessContentType($filename);
        }

        parent::__construct($body, $filename, $contentType, $encoding);

        if ($size !== null) {
            $this->size = $size;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getContentType(): string
    {
        return $this->getMediaType() . '/' . $this->getMediaSubtype();
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): int
    {
        if (!isset($this->size)) {
            $this->size = strlen($this->getBody());
        }

        return $this->size;
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *     data: string,
     *     name: string|null,
     *     type: string,
     *     size: int
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => $this->getBody(),
            'name' => $this->getFilename(),
            'type' => $this->getContentType(),
            'size' => $this->getSize(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *     data: string,
     *     name: string|null,
     *     type: string,
     *     size: int
     * }
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['data'] = base64_encode($array['data']);

        return $array;
    }

    /**
     * Adivina el Content Type del contenido del archivo a partir de la
     * extensión del nombre del archivo.
     *
     * @param string $filename
     * @return string
     */
    private function guessContentType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $mimeTypes = MimeTypes::getDefault()->getMimeTypes($extension);

        if (!isset($mimeTypes[0])) {
            throw new InvalidArgumentException(sprintf(
                'El archivo %s tiene una extensión inválida. Corrige la extensión o especifica el $contentType.',
                $filename
            ));
        }

        return $mimeTypes[0];
    }
}

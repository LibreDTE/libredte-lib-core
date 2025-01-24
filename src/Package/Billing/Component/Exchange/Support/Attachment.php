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
     * @param string $filename
     * @param string|null $contentType
     * @param string|null $encoding
     * @param int|null $size
     */
    public function __construct(
        string|File $body,
        string $filename = null,
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

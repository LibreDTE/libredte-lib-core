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

namespace libredte\lib\Core\Package\Billing\Component\Document\Exception;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Exception\BillingException;
use Throwable;

/**
 * Excepción general del componente "billing.document".
 */
class DocumentException extends BillingException
{
    /**
     * Contenedor del documento que se estaba manipulando cuando se generó la
     * excepción.
     *
     * @var DocumentBagInterface|null
     */
    protected ?DocumentBagInterface $documentBag = null;

    /**
     * Constructor de la excepción.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param DocumentBagInterface|null $documentBag
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?DocumentBagInterface $documentBag = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->documentBag = $documentBag;
    }

    /**
     * Entrega, si está asignado, el contenedor del documento.
     *
     * @return DocumentBagInterface|null
     */
    public function getDocumentBag(): ?DocumentBagInterface
    {
        return $this->documentBag;
    }
}

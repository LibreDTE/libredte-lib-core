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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Contract;

use Derafu\Lib\Core\Foundation\Contract\WorkerInterface;
use Derafu\Lib\Core\Package\Prime\Component\Certificate\Contract\CertificateInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Entity\Xml as XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDocumentSenderException;
use UnexpectedValueException;

/**
 * Interfaz del worker que envía (sube) los documentos (XML) al SII.
 */
interface SiiDocumentSenderWorkerInterface extends WorkerInterface
{
    /**
     * Realiza el envío de un XML al SII.
     *
     * @param CertificateInterface $certificate Certificado digital.
     * @param XmlDocument $doc Documento XML que se desea enviar al SII.
     * @param string $company RUT de la empresa emisora del XML.
     * @param bool $compress Indica si se debe enviar comprimido el XML.
     * @param int|null $retry Intentos que se realizarán como máximo al enviar.
     * @return int Número de seguimiento (Track ID) del envío del XML al SII.
     * @throws UnexpectedValueException Si alguno de los RUT son inválidos.
     * @throws SiiDocumentSenderException Si hay algún error al enviar el XML.
     */
    public function sendXml(
        CertificateInterface $certificate,
        XmlDocument $doc,
        string $company,
        bool $compress = false,
        ?int $retry = null
    ): int;
}

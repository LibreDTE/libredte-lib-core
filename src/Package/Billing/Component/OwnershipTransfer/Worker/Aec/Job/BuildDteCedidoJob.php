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

namespace libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Worker\Aec\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlEncoderInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\DteCedido;

/**
 * Construye el XML `DTECedido` firmado.
 *
 * Embebe el XML del `DocumentInterface` dentro de la estructura
 * `DocumentoDTECedido` usando un placeholder para el nodo `<DTE>` y luego
 * reemplaza con str_replace. Firma el `DocumentoDTECedido` con ID
 * `LibreDTE_DTECedido`.
 */
#[Job(name: 'build_dte_cedido', worker: 'aec', component: 'ownership_transfer', package: 'billing')]
class BuildDteCedidoJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private XmlEncoderInterface $xmlEncoder,
        private SignatureServiceInterface $signatureService
    ) {
    }

    /**
     * Construye y firma el XML `DTECedido`.
     *
     * @param DocumentInterface $dte DTE a ceder, ya construido y firmado.
     * @param CertificateInterface|null $certificate Certificado para firmar.
     * @return DteCedido
     */
    public function build(DocumentInterface $dte, ?CertificateInterface $certificate): DteCedido
    {
        $placeholder = '__DTE_PLACEHOLDER__';

        $xml = $this->xmlEncoder->encode([
            'DTECedido' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte DTECedido_v10.xsd',
                    'version' => '1.0',
                ],
                'DocumentoDTECedido' => [
                    '@attributes' => ['ID' => 'LibreDTE_DTECedido'],
                    'DTE' => $placeholder,
                    'TmstFirma' => date('Y-m-d\TH:i:s'),
                ],
            ],
        ])->setEncoding('ISO-8859-1')->saveXml();

        // Extraer el XML del DTE y eliminar la declaración XML. Se fuerza su
        // encoding a ISO-8859-1 (aunque el documento ya debería tenerlo así)
        // para garantizar que quede en la misma codificación que la plantilla
        // de arriba sin importar el estado previo del XmlDocument del $dte.
        $dteXml = trim(preg_replace(
            '/^<\?xml[^>]*\?>\s*/',
            '',
            $dte->getXmlDocument()->setEncoding('ISO-8859-1')->saveXml()
        ));

        // Reemplazar el placeholder con el XML del DTE.
        $xml = str_replace('<DTE>' . $placeholder . '</DTE>', $dteXml, $xml);

        if ($certificate !== null) {
            $xml = $this->signatureService->signXml(
                $xml,
                $certificate,
                'LibreDTE_DTECedido'
            );
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return new DteCedido($xmlDocument);
    }
}

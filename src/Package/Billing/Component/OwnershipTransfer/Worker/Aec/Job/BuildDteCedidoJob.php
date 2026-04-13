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
        ])->saveXml();

        // Extraer el XML del DTE y eliminar la declaración XML.
        $dteXml = trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $dte->getXmlDocument()->saveXml()));

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

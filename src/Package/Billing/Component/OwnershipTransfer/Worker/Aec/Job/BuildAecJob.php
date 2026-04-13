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
use DOMDocument;
use DOMXPath;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Aec;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Support\AecBag;

/**
 * Construye el XML `AEC` firmado.
 *
 * Soporta dos modos según la fuente del bag:
 *
 *   - Primera cesión (`DocumentInterface`): orquesta `BuildDteCedidoJob` y
 *     `BuildCesionJob`, ensambla el AEC con un DTECedido y una Cesion, y
 *     firma el `DocumentoAEC` con ID `LibreDTE_AEC`.
 *
 *   - Re-cesión (`Aec`): extrae el `DTECedido` y las `Cesion` existentes del
 *     AEC recibido via DOM, construye una nueva `Cesion` con seq = N+1, y
 *     re-ensambla y re-firma el `DocumentoAEC` con todos los elementos.
 */
#[Job(name: 'build_aec', worker: 'aec', component: 'ownership_transfer', package: 'billing')]
class BuildAecJob extends AbstractJob implements JobInterface
{
    private const NS = 'http://www.sii.cl/SiiDte';

    public function __construct(
        private BuildDteCedidoJob $buildDteCedidoJob,
        private BuildCesionJob $buildCesionJob,
        private XmlEncoderInterface $xmlEncoder,
        private SignatureServiceInterface $signatureService,
    ) {
    }

    /**
     * Construye y firma el XML `AEC`.
     *
     * @param AecBag $bag Contenedor con todos los datos necesarios.
     * @return Aec
     */
    public function build(AecBag $bag): Aec
    {
        if ($bag->isRecesion()) {
            return $this->buildRecesion($bag);
        }

        return $this->buildPrimeraCesion($bag);
    }

    /**
     * Construye el AEC para la primera cesión a partir de un `DocumentInterface`.
     */
    private function buildPrimeraCesion(AecBag $bag): Aec
    {
        /** @var DocumentInterface $dte */
        $dte = $bag->getSource();
        $certificate = $bag->getCertificate();
        $seq = $bag->getSeq() ?? 1;

        $dteCedido = $this->buildDteCedidoJob->build($dte, $certificate);
        $cesionDoc = $this->buildCesionJob->build(
            $dte,
            $bag->getCedente(),
            $bag->getCesionario(),
            $bag->getCesion(),
            $certificate,
            $seq
        );

        $dteCedidoXml = $this->stripXmlDeclaration($dteCedido->getXmlDocument()->saveXml());
        $cesionXml = $this->stripXmlDeclaration($cesionDoc->getXmlDocument()->saveXml());

        return $this->assembleAndSign(
            $bag->getCedente(),
            $bag->getCesionario(),
            $dteCedidoXml,
            [$cesionXml],
            $certificate
        );
    }

    /**
     * Construye el AEC para una re-cesión a partir de un `Aec` existente.
     */
    private function buildRecesion(AecBag $bag): Aec
    {
        /** @var Aec $existingAec */
        $existingAec = $bag->getSource();
        $certificate = $bag->getCertificate();

        // Cargar el AEC existente en un DOMDocument para extraer sus nodos.
        $dom = new DOMDocument();
        $dom->loadXML($existingAec->getXmlDocument()->saveXml());
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', self::NS);

        // Extraer el nodo DTECedido completo.
        $dteCedidoNode = $xpath->query('//s:Cesiones/s:DTECedido')->item(0);
        $dteCedidoXml = $dom->saveXML($dteCedidoNode);

        // Extraer todos los nodos Cesion existentes.
        $cesionNodes = $xpath->query('//s:Cesiones/s:Cesion');
        $existingCesionesXml = [];
        foreach ($cesionNodes as $node) {
            $existingCesionesXml[] = $dom->saveXML($node);
        }
        $existingCount = count($existingCesionesXml);
        $seq = $bag->getSeq() ?? $existingCount + 1;

        // Extraer los datos de IdDTE de la primera Cesion para construir la nueva.
        $idDte = [
            'TipoDTE' => (int) $xpath->query('//s:Cesiones/s:Cesion[1]/s:DocumentoCesion/s:IdDTE/s:TipoDTE')->item(0)?->textContent,
            'RUTEmisor' => (string) $xpath->query('//s:Cesiones/s:Cesion[1]/s:DocumentoCesion/s:IdDTE/s:RUTEmisor')->item(0)?->textContent,
            'RUTReceptor' => (string) $xpath->query('//s:Cesiones/s:Cesion[1]/s:DocumentoCesion/s:IdDTE/s:RUTReceptor')->item(0)?->textContent,
            'Folio' => (int) $xpath->query('//s:Cesiones/s:Cesion[1]/s:DocumentoCesion/s:IdDTE/s:Folio')->item(0)?->textContent,
            'FchEmis' => (string) $xpath->query('//s:Cesiones/s:Cesion[1]/s:DocumentoCesion/s:IdDTE/s:FchEmis')->item(0)?->textContent,
            'MntTotal' => (int) $xpath->query('//s:Cesiones/s:Cesion[1]/s:DocumentoCesion/s:IdDTE/s:MntTotal')->item(0)?->textContent,
        ];

        // Construir la nueva Cesion firmada.
        $newCesionDoc = $this->buildCesionJob->build(
            $idDte,
            $bag->getCedente(),
            $bag->getCesionario(),
            $bag->getCesion(),
            $certificate,
            $seq
        );
        $newCesionXml = $this->stripXmlDeclaration($newCesionDoc->getXmlDocument()->saveXml());

        return $this->assembleAndSign(
            $bag->getCedente(),
            $bag->getCesionario(),
            $dteCedidoXml,
            array_merge($existingCesionesXml, [$newCesionXml]),
            $certificate
        );
    }

    /**
     * Ensambla el XML del AEC con los nodos ya firmados y lo firma.
     *
     * @param array<string, mixed> $cedente
     * @param array<string, mixed> $cesionario
     * @param string $dteCedidoXml XML del DTECedido (sin declaración <?xml?>).
     * @param string[] $cesionesXml XMLs de todas las Cesion (sin declaración).
     * @param CertificateInterface|null $certificate
     */
    private function assembleAndSign(
        array $cedente,
        array $cesionario,
        string $dteCedidoXml,
        array $cesionesXml,
        ?CertificateInterface $certificate
    ): Aec {
        $cesionesPlaceholder = '__CESIONES_PLACEHOLDER__';

        $xml = $this->xmlEncoder->encode([
            'AEC' => [
                '@attributes' => [
                    'xmlns' => self::NS,
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => self::NS . ' AEC_v10.xsd',
                    'version' => '1.0',
                ],
                'DocumentoAEC' => [
                    '@attributes' => ['ID' => 'LibreDTE_AEC'],
                    'Caratula' => [
                        '@attributes' => ['version' => '1.0'],
                        'RutCedente' => $cedente['RUT'] ?? false,
                        'RutCesionario' => $cesionario['RUT'] ?? false,
                        'TmstFirmaEnvio' => date('Y-m-d\TH:i:s'),
                    ],
                    'Cesiones' => $cesionesPlaceholder,
                ],
            ],
        ])->saveXml();

        $cesionesContent = $dteCedidoXml . "\n" . implode("\n", $cesionesXml);

        $xml = str_replace(
            '<Cesiones>' . $cesionesPlaceholder . '</Cesiones>',
            '<Cesiones>' . $cesionesContent . '</Cesiones>',
            $xml
        );

        if ($certificate !== null) {
            $xml = $this->signatureService->signXml(
                $xml,
                $certificate,
                'LibreDTE_AEC'
            );
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return new Aec($xmlDocument);
    }

    /**
     * Elimina la declaración `<?xml...?>` de un string XML.
     */
    private function stripXmlDeclaration(string $xml): string
    {
        return trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $xml));
    }
}

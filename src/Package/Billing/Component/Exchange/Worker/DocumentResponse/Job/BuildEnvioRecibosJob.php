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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlEncoderInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;

/**
 * Construye el XML `EnvioRecibos` firmado.
 *
 * El proceso:
 *   1. Se genera la estructura XML principal con placeholder `<Recibo/>`.
 *   2. Para cada recibo se genera y firma su XML individual con el ID
 *      `LibreDTE_T{tipo}F{folio}`.
 *   3. Se reemplazan los placeholders con los recibos firmados.
 *   4. Se firma el `SetRecibos` completo con ID `LibreDTE_SetDteRecibidos`.
 */
#[Job(name: 'build_envio_recibos', worker: 'document_response', component: 'exchange', package: 'billing')]
class BuildEnvioRecibosJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private XmlEncoderInterface $xmlEncoder,
        private SignatureServiceInterface $signatureService
    ) {
    }

    /**
     * Construye y firma el XML `EnvioRecibos`.
     *
     * @param ExchangeDocumentBag $bag Bolsa con carátula y lista de recibos.
     *   Cada recibo debe tener: `TipoDoc`, `Folio`, `FchEmis`, `RUTEmisor`,
     *   `RUTRecep`, `MntTotal`, `Recinto` y opcionalmente `RutFirma`.
     * @return EnvioRecibos
     */
    public function build(ExchangeDocumentBag $bag): EnvioRecibos
    {
        $caratula = $this->normalizeCaratula($bag->getCaratula());
        $recibosData = $bag->getData();
        $certificate = $bag->getCertificate();

        // Generar la estructura principal con un marcador de posición para los
        // recibos. Se usa un string único para poder reemplazarlo después.
        $placeholder = '__RECIBO_PLACEHOLDER__';
        $mainXml = $this->xmlEncoder->encode([
            'EnvioRecibos' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte EnvioRecibos_v10.xsd',
                    'version' => '1.0',
                ],
                'SetRecibos' => [
                    '@attributes' => [
                        'ID' => 'LibreDTE_SetDteRecibidos',
                    ],
                    'Caratula' => $caratula,
                    'Recibo' => $placeholder,
                ],
            ],
        ])->saveXml();

        // Generar y firmar cada recibo individualmente.
        $signedRecibos = [];
        foreach ($recibosData as $recibo) {
            $tipo = (int) ($recibo['TipoDoc'] ?? 0);
            $folio = (int) ($recibo['Folio'] ?? 0);
            $id = sprintf('LibreDTE_T%dF%d', $tipo, $folio);

            $reciboData = $this->normalizeRecibo($recibo, $id);

            $reciboXml = $this->xmlEncoder->encode(['Recibo' => $reciboData])->saveXml();

            if ($certificate !== null) {
                $reciboXml = $this->signatureService->signXml(
                    $reciboXml,
                    $certificate,
                    $id
                );
            }

            // Quitar la declaración XML para embeber en el documento principal.
            $signedRecibos[] = trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $reciboXml));
        }

        // Insertar recibos firmados en el XML principal reemplazando el placeholder.
        $mainXml = str_replace(
            '<Recibo>' . $placeholder . '</Recibo>',
            implode("\n", $signedRecibos),
            $mainXml
        );

        // Firmar el SetRecibos completo.
        if ($certificate !== null) {
            $mainXml = $this->signatureService->signXml(
                $mainXml,
                $certificate,
                'LibreDTE_SetDteRecibidos'
            );
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($mainXml);

        return new EnvioRecibos($xmlDocument);
    }

    /**
     * Normaliza la carátula con los campos requeridos y sus valores por defecto.
     *
     * @param array<string, mixed> $caratula
     * @return array<string, mixed>
     */
    private function normalizeCaratula(array $caratula): array
    {
        return array_merge([
            '@attributes' => ['version' => '1.0'],
            'RutResponde' => false,
            'RutRecibe' => false,
            'NmbContacto' => false,
            'FonoContacto' => false,
            'MailContacto' => false,
            'TmstFirmaEnv' => date('Y-m-d\TH:i:s'),
        ], $caratula);
    }

    /**
     * Normaliza un recibo con los campos requeridos y sus valores por defecto.
     *
     * @param array<string, mixed> $recibo
     * @param string $id ID del DocumentoRecibo.
     * @return array<string, mixed>
     */
    private function normalizeRecibo(array $recibo, string $id): array
    {
        return [
            '@attributes' => ['version' => '1.0'],
            'DocumentoRecibo' => array_merge([
                '@attributes' => ['ID' => $id],
                'TipoDoc' => false,
                'Folio' => false,
                'FchEmis' => false,
                'RUTEmisor' => false,
                'RUTRecep' => false,
                'MntTotal' => false,
                'Recinto' => false,
                'RutFirma' => false,
                'Declaracion' => 'El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4, y la letra c) del Art. 5 de la Ley 19.983, acredita que la entrega de mercaderias o servicio(s) prestado(s) ha(n) sido recibido(s).',
                'TmstFirmaRecibo' => date('Y-m-d\TH:i:s'),
            ], $recibo),
        ];
    }
}

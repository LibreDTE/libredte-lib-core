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
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\OwnershipTransfer\Entity\Cesion;

/**
 * Construye el XML `Cesion` firmado.
 *
 * Acepta dos tipos de fuente para los datos de cabecera del DTE:
 *   - `DocumentInterface`: extrae los datos vía sus métodos (`getCodigo()`,
 *     `getFolio()`, `getFechaEmision()`, `getMontoTotal()`, `getRutEmisor()`,
 *     `getRutReceptor()`). Usado en la primera cesión.
 *   - `array`: recibe los datos directamente con las claves `TipoDTE`,
 *     `RUTEmisor`, `RUTReceptor`, `Folio`, `FchEmis`, `MntTotal`. Usado en
 *     re-cesiones donde el DTE original no está disponible directamente.
 *
 * Firma con ID `LibreDTE_Cesion_{seq}`.
 */
#[Job(name: 'build_cesion', worker: 'aec', component: 'ownership_transfer', package: 'billing')]
class BuildCesionJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private SignatureServiceInterface $signatureService
    ) {
    }

    /**
     * Construye y firma el XML `Cesion`.
     *
     * @param DocumentInterface|array<string, mixed> $dte DTE cedido como
     *   `DocumentInterface`, o arreglo con claves `TipoDTE`, `RUTEmisor`,
     *   `RUTReceptor`, `Folio`, `FchEmis`, `MntTotal`.
     * @param array<string, mixed> $cedente Datos del cedente.
     * @param array<string, mixed> $cesionario Datos del cesionario.
     * @param array<string, mixed> $cesion Datos de la cesión.
     * @param CertificateInterface|null $certificate Certificado para firmar.
     * @param int $seq Número de secuencia de la cesión.
     * @return Cesion
     */
    public function build(
        DocumentInterface|array $dte,
        array $cedente,
        array $cesionario,
        array $cesion,
        ?CertificateInterface $certificate,
        int $seq
    ): Cesion {
        if ($dte instanceof DocumentInterface) {
            $tipoDte = $dte->getCodigo();
            $rutEmisor = $dte->getRutEmisor();
            $rutReceptor = $dte->getRutReceptor();
            $folio = $dte->getFolio();
            $fchEmis = $dte->getFechaEmision();
            $mntTotal = $dte->getMontoTotal();
        } else {
            $tipoDte = $dte['TipoDTE'];
            $rutEmisor = $dte['RUTEmisor'];
            $rutReceptor = $dte['RUTReceptor'];
            $folio = $dte['Folio'];
            $fchEmis = $dte['FchEmis'];
            $mntTotal = $dte['MntTotal'];
        }

        $id = sprintf('LibreDTE_Cesion_%d', $seq);

        $encoder = new XmlEncoder();
        $xml = $encoder->encode([
            'Cesion' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte Cesion_v10.xsd',
                    'version' => '1.0',
                ],
                'DocumentoCesion' => [
                    '@attributes' => ['ID' => $id],
                    'SeqCesion' => $seq,
                    'IdDTE' => [
                        'TipoDTE' => $tipoDte,
                        'RUTEmisor' => $rutEmisor,
                        'RUTReceptor' => $rutReceptor,
                        'Folio' => $folio,
                        'FchEmis' => $fchEmis,
                        'MntTotal' => $mntTotal,
                    ],
                    'Cedente' => array_merge([
                        'RUT' => false,
                        'RazonSocial' => false,
                        'Direccion' => false,
                        'eMail' => false,
                        'RUTAutorizado' => false,
                    ], $cedente),
                    'Cesionario' => array_merge([
                        'RUT' => false,
                        'RazonSocial' => false,
                        'Direccion' => false,
                        'eMail' => false,
                    ], $cesionario),
                    'MontoCesion' => $cesion['MontoCesion'] ?? $mntTotal,
                    'UltimoVencimiento' => $cesion['UltimoVencimiento'] ?? false,
                    'TmstCesion' => date('Y-m-d\TH:i:s'),
                ],
            ],
        ])->saveXml();

        if ($certificate !== null) {
            $xml = $this->signatureService->signXml(
                $xml,
                $certificate,
                $id
            );
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return new Cesion($xmlDocument);
    }
}

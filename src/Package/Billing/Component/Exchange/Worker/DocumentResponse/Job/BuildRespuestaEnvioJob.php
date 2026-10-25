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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlEncoderInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\RespuestaEnvio;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;

/**
 * Construye el XML `RespuestaDTE` firmado.
 *
 * Los datos del bag deben tener una de las dos claves:
 *   - `recepcion_envio`: lista de arreglos con los datos de `RecepcionEnvio`.
 *   - `resultado_dte`: lista de arreglos con los datos de `ResultadoDTE`.
 *
 * El nodo `Resultado` se firma con ID `LibreDTE_ResultadoEnvio`.
 */
#[Job(name: 'build_respuesta_envio', worker: 'document_response', component: 'exchange', package: 'billing')]
class BuildRespuestaEnvioJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private XmlEncoderInterface $xmlEncoder,
        private SignatureServiceInterface $signatureService
    ) {
    }

    /**
     * Construye y firma el XML `RespuestaDTE`.
     *
     * @param ExchangeDocumentBag $bag Bolsa con carátula y respuestas.
     * @return RespuestaEnvio
     */
    public function build(ExchangeDocumentBag $bag): RespuestaEnvio
    {
        $data = $bag->getData();
        $certificate = $bag->getCertificate();

        $recepcionEnvio = $data['recepcion_envio'] ?? null;
        $resultadoDte = $data['resultado_dte'] ?? null;

        $nroDetalles = $recepcionEnvio !== null
            ? count((array) $recepcionEnvio)
            : count((array) $resultadoDte);

        $caratula = $this->normalizeCaratula($bag->getCaratula(), $nroDetalles);

        $resultado = [
            '@attributes' => ['ID' => 'LibreDTE_ResultadoEnvio'],
            'Caratula' => $caratula,
        ];

        if ($recepcionEnvio !== null) {
            $resultado['RecepcionEnvio'] = $this->normalizeRecepcionEnvio(
                (array) $recepcionEnvio
            );
        } else {
            $resultado['ResultadoDTE'] = $this->normalizeResultadoDte(
                (array) $resultadoDte
            );
        }

        $xml = $this->xmlEncoder->encode([
            'RespuestaDTE' => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte RespuestaEnvioDTE_v10.xsd',
                    'version' => '1.0',
                ],
                'Resultado' => $resultado,
            ],
        ])->saveXml();

        if ($certificate !== null) {
            $xml = $this->signatureService->signXml(
                $xml,
                $certificate,
                'LibreDTE_ResultadoEnvio'
            );
        }

        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xml);

        return new RespuestaEnvio($xmlDocument);
    }

    /**
     * Normaliza la carátula con los campos requeridos y sus valores por defecto.
     *
     * @param array<string, mixed> $caratula
     * @param int $nroDetalles
     * @return array<string, mixed>
     */
    private function normalizeCaratula(array $caratula, int $nroDetalles): array
    {
        return array_merge([
            '@attributes' => ['version' => '1.0'],
            'RutResponde' => false,
            'RutRecibe' => false,
            'IdRespuesta' => 0,
            'NroDetalles' => $nroDetalles,
            'NmbContacto' => false,
            'FonoContacto' => false,
            'MailContacto' => false,
            'TmstFirmaResp' => date('Y-m-d\TH:i:s'),
        ], $caratula);
    }

    /**
     * Normaliza la lista de recepciones de envío.
     *
     * @param array<int, array<string, mixed>> $recepcionEnvio
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRecepcionEnvio(array $recepcionEnvio): array
    {
        $normalized = [];
        foreach ($recepcionEnvio as $recepcion) {
            $normalized[] = array_merge([
                'NmbEnvio' => false,
                'FchRecep' => date('Y-m-d\TH:i:s'),
                'CodEnvio' => 0,
                'EnvioDTEID' => false,
                'Digest' => false,
                'RutEmisor' => false,
                'RutReceptor' => false,
                'EstadoRecepEnv' => false,
                'RecepEnvGlosa' => false,
                'NroDTE' => false,
                'RecepcionDTE' => false,
            ], $recepcion);
        }

        return $normalized;
    }

    /**
     * Normaliza la lista de resultados de DTE.
     *
     * @param array<int, array<string, mixed>> $resultadoDte
     * @return array<int, array<string, mixed>>
     */
    private function normalizeResultadoDte(array $resultadoDte): array
    {
        $normalized = [];
        foreach ($resultadoDte as $resultado) {
            $normalized[] = array_merge([
                'TipoDTE' => false,
                'Folio' => false,
                'FchEmis' => false,
                'RUTEmisor' => false,
                'RUTRecep' => false,
                'MntTotal' => false,
                'CodEnvio' => false,
                'EstadoDTE' => false,
                'EstadoDTEGlosa' => false,
                'CodRchDsc' => false,
            ], $resultado);
        }

        return $normalized;
    }
}

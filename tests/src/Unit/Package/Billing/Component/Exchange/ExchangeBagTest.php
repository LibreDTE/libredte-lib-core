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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Exchange;

use DateTimeImmutable;
use Exception;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\ProcessType;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test unitario para la serialización (`toArray()`/`jsonSerialize()`) de
 * `ExchangeBag` y todo el árbol de entidades que agrupa.
 *
 * Es el único lugar donde se prueba esta serialización directamente: hoy
 * `ExchangeBag` no se usa desde ningún otro punto de la librería (las
 * operaciones `exchange.receiver::receive`/`exchange.sender::send` reciben
 * un `ExchangeBagInterface` ya construido por quien las invoca), así que no
 * hay un test funcional que la ejercite de punta a punta.
 */
#[CoversClass(ExchangeBag::class)]
#[UsesClass(Envelope::class)]
#[UsesClass(AbstractParty::class)]
#[UsesClass(Sender::class)]
#[UsesClass(Receiver::class)]
#[UsesClass(PartyIdentifier::class)]
#[UsesClass(Document::class)]
#[UsesClass(Attachment::class)]
#[UsesClass(ExchangeResult::class)]
#[UsesClass(ExchangeStatus::class)]
#[UsesClass(DocumentType::class)]
#[UsesClass(ProcessType::class)]
class ExchangeBagTest extends TestCase
{
    private function buildBag(): ExchangeBag
    {
        $sender = new Sender(new PartyIdentifier('76192083-9', 'CL-RUT'));

        $receiver = new Receiver(new PartyIdentifier('66666666-6', 'CL-RUT'));

        $attachment = new Attachment(
            body: '<DTE>...</DTE>',
            filename: 'factura.xml',
            contentType: 'application/xml',
        );

        $document = new Document(
            content: '<EnvioDTE>...</EnvioDTE>',
            attachments: [$attachment],
            type: DocumentType::INVOICE,
            metadata: ['origin' => 'test'],
        );

        $envelope = new Envelope(
            sender: $sender,
            receiver: $receiver,
            documentType: DocumentType::INVOICE,
            process: ProcessType::BILLING,
            businessMessageID: 'msg-1',
            originalBusinessMessageID: null,
            creationDateAndTime: new DateTimeImmutable('2026-01-15T10:30:00+00:00'),
            documents: [$document],
            metadata: ['channel' => 'test'],
        );

        $bag = new ExchangeBag(['strategy' => 'email.smtp']);
        $bag->addEnvelope($envelope);

        $status = new ExchangeStatus(
            strategy: 'email.smtp',
            error: new Exception('Fallo simulado de envío.'),
        );
        $result = new ExchangeResult($envelope);
        $result->addStatus($status);
        $bag->addResult($result);

        return $bag;
    }

    public function testToArray(): void
    {
        $bag = $this->buildBag();

        $expected = [
            'options' => [
                'transport' => [],
                'strategy' => 'email.smtp',
            ],
            'envelopes' => [
                [
                    'sender' => [
                        'identifier' => [
                            'id' => 'CL-RUT:76192083-9',
                            'value' => '76192083-9',
                            'schemeId' => 'CL-RUT',
                            'schemeName' => 'Rol Único Tributario (RUT) de Chile',
                            'authority' => 'CL-SII',
                        ],
                        'endpoints' => [],
                    ],
                    'receiver' => [
                        'identifier' => [
                            'id' => 'CL-RUT:66666666-6',
                            'value' => '66666666-6',
                            'schemeId' => 'CL-RUT',
                            'schemeName' => 'Rol Único Tributario (RUT) de Chile',
                            'authority' => 'CL-SII',
                        ],
                        'endpoints' => [],
                    ],
                    'documentType' => 'urn:fdc:libredte.cl:2025:doc:invoice',
                    'process' => 'urn:fdc:libredte.cl:2025:poacc:billing',
                    'businessMessageID' => 'msg-1',
                    'originalBusinessMessageID' => null,
                    'creationDateAndTime' => '2026-01-15T10:30:00+00:00',
                    'documents' => [
                        [
                            'id' => 'test-uuid-should-not-be-asserted',
                            'type' => 'urn:fdc:libredte.cl:2025:doc:invoice',
                            'content' => '<EnvioDTE>...</EnvioDTE>',
                            'attachments' => [
                                [
                                    'data' => '<DTE>...</DTE>',
                                    'name' => 'factura.xml',
                                    'type' => 'application/xml',
                                    'size' => strlen('<DTE>...</DTE>'),
                                ],
                            ],
                            'metadata' => ['origin' => 'test', 'id' => 'test-uuid-should-not-be-asserted'],
                        ],
                    ],
                    'metadata' => ['channel' => 'test'],
                ],
            ],
            'results' => [
                [
                    'envelope' => 'msg-1',
                    'statuses' => [
                        [
                            'strategy' => 'email.smtp',
                            'error' => ['message' => 'Fallo simulado de envío.'],
                            'metadata' => [],
                        ],
                    ],
                    'metadata' => [],
                ],
            ],
        ];

        $actual = $bag->toArray();

        // El ID del documento es un UUID autogenerado (ver Document::getID()),
        // no determinístico — se verifica que exista y tenga forma de UUID,
        // y luego se normaliza para poder comparar el resto del array.
        $documentId = $actual['envelopes'][0]['documents'][0]['id'];
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $documentId,
        );
        $this->assertSame(
            $documentId,
            $actual['envelopes'][0]['documents'][0]['metadata']['id'],
        );
        $actual['envelopes'][0]['documents'][0]['id'] = 'test-uuid-should-not-be-asserted';
        $actual['envelopes'][0]['documents'][0]['metadata']['id'] = 'test-uuid-should-not-be-asserted';

        $this->assertSame($expected, $actual);
    }

    public function testJsonSerialize(): void
    {
        $bag = $this->buildBag();

        $array = $bag->toArray();
        $json = $bag->jsonSerialize();

        // El contenido del documento y los datos del adjunto deben quedar en
        // base64 en jsonSerialize(), a diferencia de toArray() (que los deja
        // crudos) — mismo patrón que DocumentEnvelope::jsonSerialize().
        $this->assertSame(
            base64_encode($array['envelopes'][0]['documents'][0]['content']),
            $json['envelopes'][0]['documents'][0]['content'],
        );
        $this->assertSame(
            base64_encode($array['envelopes'][0]['documents'][0]['attachments'][0]['data']),
            $json['envelopes'][0]['documents'][0]['attachments'][0]['data'],
        );

        // El resto del array debe ser idéntico entre toArray() y
        // jsonSerialize().
        $json['envelopes'][0]['documents'][0]['content'] = $array['envelopes'][0]['documents'][0]['content'];
        $json['envelopes'][0]['documents'][0]['attachments'][0]['data'] = $array['envelopes'][0]['documents'][0]['attachments'][0]['data'];
        $this->assertSame($array, $json);
    }
}

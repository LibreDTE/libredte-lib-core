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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Strategy\Email;

use DateTimeImmutable;
use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use Derafu\Lib\Core\Helper\Str;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Contract\EnvelopeInterface as MailEnvelopeInterface;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Contract\MailComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Contract\MessageInterface as MailMessageInterface;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Support\Postman as MailPostman;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ReceiverStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use Symfony\Component\Mailer\Envelope as SymfonyEnvelope;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Recepción de documentos usando la estrategia IMAP de correo electrónico.
 */
class ImapReceiverStrategy extends AbstractStrategy implements ReceiverStrategyInterface
{
    /**
     * Constructor y sus dependencias.
     *
     * @param MailComponentInterface $mailComponent
     */
    public function __construct(private MailComponentInterface $mailComponent)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function receive(ExchangeBagInterface $bag): array
    {
        // Crear el cartero con las opciones.
        $postman = new MailPostman([
            'strategy' => 'imap',
            'transport' => $this->resolveTransportOptions($bag),
        ]);

        // Obtener los sobres con los correos electrónicos.
        $mailEnvelopes = $this->mailComponent->receive($postman);

        // Iterar los sobres para armar los sobres de intercambio con los
        // documentos que se hayan encontrado en los mensajes.
        foreach ($mailEnvelopes as $mailEnvelope) {
            assert($mailEnvelope instanceof SymfonyEnvelope);

            // Iterar cada mensaje del sobre. La implementación de MailComponent
            // que viene por defecto en Derafu entrega solo un mensaje por cada
            // sobre. Sin embargo, cada mensaje podría tener múltiples adjuntos,
            // pues eso lo decide quien envía el correo.
            foreach ($mailEnvelope->getMessages() as $message) {
                assert($message instanceof SymfonyEmail);

                // Revisar cada adjunto del mensaje y agregarlo al listado si es
                // un XML, pues los XML se deberán considerar como documentos
                // del proceso de intercambio.
                $attachments = $this->extractXmlAttachments($message);
                if (empty($attachments)) {
                    continue;
                }

                // Si hay archivos adjuntos encontrados en el mensaje se crea el
                // sobre de intercambio (diferente al de email) y se agregan los
                // documentos encontrados.
                $envelope = $this->createEnvelope(
                    $mailEnvelope,
                    $message,
                    $attachments
                );

                // Agregar el sobre a los resultados de la bolsa.
                $bag->addResult(new ExchangeResult($envelope));
            }
        }

        // Entregar los resultados de la recepción de documentos.
        return $bag->getResults();
    }

    /**
     * Crea el sobre de intercambio usando un sobre de correo, con un mensaje y
     * los adjuntos en XML que se encontraron en ese mensaje.
     *
     * @param MailEnvelopeInterface $mailEnvelope
     * @param MailMessageInterface $message
     * @param Attachment[] $attachments
     * @return EnvelopeInterface
     */
    private function createEnvelope(
        MailEnvelopeInterface $mailEnvelope,
        MailMessageInterface $message,
        array $attachments
    ): EnvelopeInterface {
        assert($mailEnvelope instanceof SymfonyEnvelope);
        assert($message instanceof SymfonyEmail);

        // Asignar el correo electrónico del remitente del mensaje.
        $sender = new Sender(new PartyIdentifier(
            ($message->getReplyTo()[0] ?? null)?->getAddress()
                ?? ($message->getFrom()[0] ?? null)?->getAddress()
                ?? $mailEnvelope->getSender()->getAddress(),
            'EMAIL'
        ));

        // Asignar el correo electrónico del receptor del mensaje.
        $receiver = new Receiver(new PartyIdentifier(
            ($message->getTo()[0] ?? null)?->getAddress()
                ?? ($message->getCc()[0] ?? null)?->getAddress()
                ?? ($message->getBcc()[0] ?? null)?->getAddress()
                ?? ($mailEnvelope->getRecipients()[0] ?? null)?->getAddress()
                ?? 'no-email',
            'EMAIL'
        ));

        // Crear el sobre de intercambio.
        $envelope = new Envelope(
            sender: $sender,
            receiver: $receiver,
            businessMessageID: (string) ($message->getId() ?: Str::uuid4()),
            creationDateAndTime: $message->getDate()
        );

        // Crear un documento por cada XML recibido y agregarlos al sobre.
        foreach ($attachments as $attachment) {
            $document = new Document();
            $document->addAttachment($attachment);
            $envelope->addDocument($document);
        }

        // Entregar el sobre con todos los documentos que venían adjuntos.
        return $envelope;
    }

    /**
     * Extrae de un mensaje de correo electrónico los archivos adjuntos que son
     * archivos XML.
     *
     * @param SymfonyEmail $message
     * @return Attachment[]
     */
    private function extractXmlAttachments(SymfonyEmail $message): array
    {
        // Si el mensaje no tiene adjuntos se entrega un arreglo vacio..
        $mailAttachments = $message->getAttachments();
        if (empty($mailAttachments)) {
            return [];
        }

        // Si el mensaje tiene adjuntos se buscan los que sean XML.
        $attachments = [];
        foreach ($mailAttachments as $mailAttachment) {
            if ($mailAttachment->getMediaSubtype() === 'xml') {
                $attachments[] = new Attachment(
                    $mailAttachment->getBody(),
                    $mailAttachment->getFilename(),
                    $mailAttachment->getMediaType() . '/'
                        . $mailAttachment->getMediaSubtype()
                );
            }
        }

        // Entregar archivos adjuntos encontrados, si es que se encontraron.
        return $attachments;
    }

    /**
     * {@inheritDoc}
     */
    public function canReceive(ExchangeBagInterface $bag): void
    {
        $this->resolveTransportOptions($bag);
    }

    /**
     * Resuelve y entrega los datos de transporte.
     *
     * @param ExchangeBagInterface $bag
     * @return array
     * @throws ExchangeException Si no se encuentran los datos de transporte.
     */
    private function resolveTransportOptions(ExchangeBagInterface $bag): array
    {
        // Buscar opciones de transporte en la bolsa del intercambio.
        $options = $bag->getOptions()->get('transport', []);

        // Validar que esté el nombre de usuario y contraseña.
        if (empty($options['username']) || empty($options['password'])) {
            throw new ExchangeException(
                'Se debe especificar el usuario y contraseña de IMAP.'
            );
        }

        // Determinar desde cuándo se debe realizar la búsqueda de correos.
        if (($options['search']['criteria'] ?? null) === null) {
            $daysAgo = $options['search']['daysAgo'] ?? 7;
            $since = (new DateTimeImmutable())->modify("-$daysAgo days")->format('Y-m-d');
            $options['search']['criteria'] = 'UNSEEN SINCE ' . $since;
            unset($options['search']['daysAgo']);
        }

        // Si no se indicó lo contrario los correos serán marcados como leídos
        // después de ser procesados.
        if (!isset($options['search']['markAsSeen'])) {
            $options['search']['markAsSeen'] = true;
        }

        // Obtiene exclusivamente el cuerpo del correo (texto o html) y archivos
        // XML que vengan como adjuntos.
        $options['search']['attachmentFilters'] = [
            'subtype' => ['PLAIN', 'HTML', 'XML'],
            'extension' => ['xml'],
        ];

        // Entregar las opciones resueltas.
        return $options;
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Strategy\Email;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Contract\MailComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Support\Envelope as MailEnvelope;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Support\Message as MailMessage;
use Derafu\Lib\Core\Package\Prime\Component\Mail\Support\Postman as MailPostman;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\EnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeBagInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeResultInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\SenderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\ExchangeException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;
use Symfony\Component\Mime\Address;

/**
 * Envío de documentos usando la estrategia SMTP de correo electrónico.
 */
class SmtpSenderStrategy extends AbstractStrategy implements SenderStrategyInterface
{
    /**
     * Constructor de la estrategia y sus dependencias.
     *
     * @param MailComponentInterface $mailComponentInterface
     */
    public function __construct(
        private MailComponentInterface $mailComponentInterface
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function send(ExchangeBagInterface $bag): array
    {
        // Procesar cada sobre por separado.
        foreach ($bag->getEnvelopes() as $envelope) {
            $result = $this->sendEnvelope($envelope);
            $bag->addResult($result);
        }

        // Entregar los resultados de la recepción de documentos.
        return $bag->getResults();
    }

    /**
     * Envía los documentos de un sobre por correo.
     *
     * @param EnvelopeInterface $envelope Sobre con documentos a enviar.
     * @return ExchangeResultInterface Resultado del envío del sobre.
     */
    private function sendEnvelope(
        EnvelopeInterface $envelope
    ): ExchangeResultInterface {
        // Crear sobre con los correos del remitente y los destinatarios.
        $sender = $this->resolveSender($envelope);
        $recipients = $this->resolveRecipients($envelope);
        $mailEnvelope = new MailEnvelope($sender, $recipients);

        // Crear el mensaje que se enviará en el sobre del correo.
        $message = new MailMessage();

        // Agregar destinatarios principales del correo (TO).
        foreach ($recipients as $to) {
            $message->addTo($to);
        }

        // Agregar otros destinatarios si existen en la bolsa.
        foreach (['to', 'cc', 'bcc'] as $dest) {
            $emails = $envelope->getMetadata()->get($dest, []);
            foreach ($emails as $email) {
                match ($dest) {
                    'to' => $message->addTo($email),
                    'cc' => $message->addCc($email),
                    'bcc' => $message->addBcc($email),
                };
            }
        }

        // Agregar asunto al mensaje.
        $subject = $envelope->getMetadata()->get('subject')
            ?? sprintf(
                'Envío de documentos electrónicos de %s',
                $envelope->getSender()->getIdentifier()->getId()
            )
        ;
        $message->subject($subject);

        // Agregar mensaje renderizado como texto.
        $text = $envelope->getMetadata()->get('text');
        if ($text !== null) {
            $message->text($text);
        }

        // Agregar mensaje renderizado como HTML.
        $html = $envelope->getMetadata()->get('html');
        if ($html !== null) {
            $message->html($html);
        }

        // Procesar cada documento para incluirlo en el mensaje de correo.
        foreach ($envelope->getDocuments() as $document) {
            // Agregar el contenido del documento como TXT si se indicó.
            if ($document->getContent()) {
                $message->attach(
                    $document->getContent(),
                    $document->getID() . '.txt'
                );
            }

            // Agregar los archivos adjuntos de cada documento.
            foreach ($document->getAttachments() as $attachment) {
                $message->attach(
                    $attachment->getBody(),
                    $attachment->getFilename(),
                    $attachment->getContentType()
                );
            }
        }

        // Agregar el mensaje al sobre.
        $mailEnvelope->addMessage($message);

        // Crear el cartero, pasarle el sobre y enviar el correo.
        $postman = new MailPostman([
            'strategy' => 'smtp',
            'transport' => $this->resolveTransportOptions($envelope),
        ]);
        $postman->addEnvelope($mailEnvelope);
        $this->mailComponentInterface->send($postman);

        // Crear resultado del envío del sobre y agregar el error del envío por
        // correo si existe (si no existe se agrega `null`).
        $result = new ExchangeResult($envelope);
        $result->addStatus(new ExchangeStatus(
            'email.smtp',
            $message->getError()
        ));

        // Entregar resultado del envío.
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function canSend(ExchangeBagInterface|EnvelopeInterface $what): void
    {
        // Armar listado de sobres a revisar.
        if ($what instanceof ExchangeBagInterface) {
            $envelopes = $what;
        } else {
            $envelopes = [$what];
        }

        // Revisar cada sobre y validar que tiene los datos necesario
        // resolviendolos a sus valores.
        foreach ($envelopes as $envelope) {
            $this->resolveTransportOptions($envelope);
            $this->resolveSender($envelope);
            $this->resolveRecipients($envelope);
        }
    }

    /**
     * Resuelve y entrega los datos de transporte.
     *
     * @param EnvelopeInterface $envelope
     * @return array
     * @throws ExchangeException Si no se encuentran los datos de transporte.
     */
    private function resolveTransportOptions(EnvelopeInterface $envelope): array
    {
        $options = $envelope->getMetadata()->get('transport', []);

        if (empty($options['username']) || empty($options['password'])) {
            throw new ExchangeException(
                'Se debe especificar el usuario y contraseña de SMTP.'
            );
        }

        return $options;
    }

    /**
     * Resuelve y entrega los datos del remitente.
     *
     * @param EnvelopeInterface $envelope
     * @return Address
     * @throws ExchangeException Si no se encuentran los datos del remitente.
     */
    private function resolveSender(EnvelopeInterface $envelope): Address
    {
        $sender = $envelope->getSender()->getEmails()[0] ?? null;

        if ($sender === null) {
            throw new ExchangeException(
                'Se debe especificar el identificador con esquema EMAIL en el remitente.'
            );
        }

        return $sender;
    }

    /**
     * Resuelve y entrega los datos de los receptores.
     *
     * @param EnvelopeInterface $envelope
     * @return array
     * @throws ExchangeException Si no se encuentra al menos un receptor.
     */
    private function resolveRecipients(EnvelopeInterface $envelope): array
    {
        $recipients = $envelope->getReceiver()->getEmails();

        if (empty($recipients)) {
            throw new ExchangeException(
                'Se debe especificar al menos un identificador con esquema EMAIL en el destinatario.'
            );
        }

        return $recipients;
    }
}

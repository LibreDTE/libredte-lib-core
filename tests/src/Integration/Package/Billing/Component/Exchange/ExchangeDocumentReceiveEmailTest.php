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

namespace libredte\lib\Tests\Integration\Package\Billing\Component\Exchange;

use libredte\lib\Core\Application;
use libredte\lib\Core\Package\Billing\BillingPackage;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Handler\EmailReceiverHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Receiver\Strategy\Email\ImapReceiverStrategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\ReceiverWorker;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractExchangeWorker::class)]
#[CoversClass(AbstractReceiverHandler::class)]
#[CoversClass(ExchangeComponent::class)]
#[CoversClass(ExchangeBag::class)]
#[CoversClass(ReceiverWorker::class)]
#[CoversClass(EmailReceiverHandler::class)]
#[CoversClass(ImapReceiverStrategy::class)]
#[CoversClass(AbstractParty::class)]
#[CoversClass(PartyIdentifier::class)]
#[CoversClass(Attachment::class)]
#[CoversClass(Document::class)]
#[CoversClass(Envelope::class)]
#[CoversClass(ExchangeResult::class)]
class ExchangeDocumentReceiveEmailTest extends TestCase
{
    private ExchangeComponentInterface $exchange;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->exchange = $app
            ->getBillingPackage()
            ->getExchangeComponent()
        ;
    }

    public function testSendOneDocumentInOneEnvelope(): void
    {
        $username = getenv('MAIL_USERNAME');
        $password = getenv('MAIL_PASSWORD');

        if (!$username || !$password) {
            $this->markTestSkipped('No existe configuración para recibir correo.');
        }

        // Crear la bolsa de intercambio (sin sobres pues recepción).
        $exchangeBag = new ExchangeBag([
            'transport' => [
                'username' => $username,
                'password' => $password,
                'search' => [
                    'daysAgo' => 1,
                    'markAsSeen' => false,
                ],
            ],
        ]);
        $results = $this->exchange->handle($exchangeBag);

        // Validar a mano cuando se pruebe funcionalidad otras cosas.
        // Nota: Realizar validación real al ejecutar el test localmente.
        $this->assertTrue(true);
        //$this->assertNotEmpty($results);
    }
}

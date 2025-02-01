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
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractBuilderStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractRendererStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Abstract\AbstractValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\RendererWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\DocumentComponent;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\Comuna;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\TipoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\TagXmlDocumento;
use libredte\lib\Core\Package\Billing\Component\Document\Repository\ComunaRepository;
use libredte\lib\Core\Package\Billing\Component\Document\Service\TemplateDataHandler;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentBag;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\BuilderWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\DocumentBagManagerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Helper\Utils as NormalizationUtils;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPostDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeDataPreDocumentNormalizationJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Job\NormalizeFacturaAfectaJob;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Strategy\FacturaAfectaNormalizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\NormalizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Parser\Strategy\Default\JsonParserStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ParserWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\RendererWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Sanitizer\Strategy\FacturaAfectaSanitizerStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\SanitizerWorker;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\Validator\Strategy\FacturaAfectaValidatorStrategy;
use libredte\lib\Core\Package\Billing\Component\Document\Worker\ValidatorWorker;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractParty;
use libredte\lib\Core\Package\Billing\Component\Exchange\Contract\ExchangeComponentInterface;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyEndpoint;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\PartyIdentifier;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Receiver;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\Sender;
use libredte\lib\Core\Package\Billing\Component\Exchange\Enum\DocumentType;
use libredte\lib\Core\Package\Billing\Component\Exchange\ExchangeComponent;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Attachment;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Document;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\Envelope;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeBag;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeResult;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeStatus;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Handler\EmailSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Handler\SiiSenderHandler;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\Sender\Strategy\Email\SmtpSenderStrategy;
use libredte\lib\Core\Package\Billing\Component\Exchange\Worker\SenderWorker;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Contribuyente;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\EmisorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Factory\ReceptorFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeEmisorProvider;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Service\FakeReceptorProvider;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(Application::class)]
#[CoversClass(BillingPackage::class)]
#[CoversClass(AbstractBuilderStrategy::class)]
#[CoversClass(AbstractDocument::class)]
#[CoversClass(AbstractNormalizerStrategy::class)]
#[CoversClass(AbstractSanitizerStrategy::class)]
#[CoversClass(AbstractValidatorStrategy::class)]
#[CoversClass(DocumentComponent::class)]
#[CoversClass(CodigoDocumento::class)]
#[CoversClass(TagXmlDocumento::class)]
#[CoversClass(TipoDocumento::class)]
#[CoversClass(DocumentBag::class)]
#[CoversClass(BuilderWorker::class)]
#[CoversClass(DocumentBagManagerWorker::class)]
#[CoversClass(NormalizerWorker::class)]
#[CoversClass(FacturaAfectaNormalizerStrategy::class)]
#[CoversClass(ParserWorker::class)]
#[CoversClass(JsonParserStrategy::class)]
#[CoversClass(SanitizerWorker::class)]
#[CoversClass(FacturaAfectaSanitizerStrategy::class)]
#[CoversClass(ValidatorWorker::class)]
#[CoversClass(FacturaAfectaValidatorStrategy::class)]
#[CoversClass(AbstractContribuyenteFactory::class)]
#[CoversClass(EmisorFactory::class)]
#[CoversClass(ReceptorFactory::class)]
#[CoversClass(NormalizationUtils::class)]
#[CoversClass(NormalizeDataPostDocumentNormalizationJob::class)]
#[CoversClass(NormalizeDataPreDocumentNormalizationJob::class)]
#[CoversClass(NormalizeFacturaAfectaJob::class)]
#[CoversClass(Contribuyente::class)]
#[CoversClass(Emisor::class)]
#[CoversClass(FakeEmisorProvider::class)]
#[CoversClass(FakeReceptorProvider::class)]
#[CoversClass(ExchangeComponent::class)]
#[CoversClass(Envelope::class)]
#[CoversClass(ExchangeBag::class)]
#[CoversClass(Document::class)]
#[CoversClass(SenderWorker::class)]
#[CoversClass(SmtpSenderStrategy::class)]
#[CoversClass(Attachment::class)]
#[CoversClass(AbstractParty::class)]
#[CoversClass(PartyEndpoint::class)]
#[CoversClass(PartyIdentifier::class)]
#[CoversClass(AbstractRendererStrategy::class)]
#[CoversClass(Comuna::class)]
#[CoversClass(ComunaRepository::class)]
#[CoversClass(TemplateDataHandler::class)]
#[CoversClass(RendererWorker::class)]
#[CoversClass(EmailSenderHandler::class)]
#[CoversClass(SiiSenderHandler::class)]
#[CoversClass(DocumentType::class)]
#[CoversClass(ExchangeResult::class)]
#[CoversClass(ExchangeStatus::class)]
class ExchangeDocumentSendEmailTest extends TestCase
{
    private BuilderWorkerInterface $builder;

    private RendererWorkerInterface $renderer;

    private ExchangeComponentInterface $exchange;

    protected function setUp(): void
    {
        $app = Application::getInstance();

        $this->builder = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getBuilderWorker()
        ;

        $this->renderer = $app
            ->getBillingPackage()
            ->getDocumentComponent()
            ->getRendererWorker()
        ;

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
            $this->markTestSkipped('No existe configuración para enviar correo.');
        }

        // Buscar los archivos con los casos para el test.
        $file = self::getFixturesPath() . '/yaml/documentos_ok/033_factura_afecta/033_001_simple.yaml';

        // Cargar datos del caso de prueba.
        $yaml = file_get_contents($file);
        $data = Yaml::parse($yaml);
        $test = $data['Test'];
        unset($data['Test']);
        $test['caso'] = basename($file);

        // Crear el documento.
        // No se firma ni se timbra pues no es relevante para este envío.
        $documentBag = new DocumentBag(
            inputData: $data,
            options: [
                'renderer' => [
                    'format' => 'pdf',
                ],
            ]
        );
        $this->builder->build($documentBag);
        $xml = $documentBag->getDocument()->saveXml();

        // Renderizar el documento en PDF.
        $pdf = $this->renderer->render($documentBag);

        // Correo de intercambio para el emisor y receptor.
        $documentBag->getEmisor()->setCorreoIntercambioDte($username);
        $documentBag->getReceptor()->setCorreoIntercambioDte($username);

        // Crear remitente y receptor.
        $sender = new Sender(new PartyIdentifier($documentBag->getEmisor()->getRut()));
        $sender->addEndpoint(new PartyEndpoint($documentBag->getEmisor()->getCorreoIntercambioDte()));
        $receiver = new Receiver(new PartyIdentifier($documentBag->getReceptor()->getRut()));
        $receiver->addEndpoint(new PartyEndpoint($documentBag->getReceptor()->getCorreoIntercambioDte()));

        // Crear el sobre con el documento a enviar.
        $envelope = new Envelope(
            sender: $sender,
            receiver: $receiver
        );
        $document = new Document(
            attachments: [
                new Attachment(
                    filename: $documentBag->getDocument()->getId() . '.xml',
                    body: $xml
                ),
                new Attachment(
                    filename: $documentBag->getDocument()->getId() . '.pdf',
                    body: $pdf
                ),
            ]
        );
        $envelope->addDocument($document);

        // Agregar los datos de transporte al envelope.
        $envelope->addMetadata('transport', [
            'username' => $username,
            'password' => $password,
        ]);

        // Enviar el documento.
        $exchangeBag = new ExchangeBag();
        $exchangeBag->addEnvelope($envelope);
        $results = $this->exchange->handle($exchangeBag);

        // Validar el estado del envío del único mensaje del único sobre de los
        // resultados del envío.
        $this->assertTrue($results[0]->getStatuses()[0]->isOk());
    }
}

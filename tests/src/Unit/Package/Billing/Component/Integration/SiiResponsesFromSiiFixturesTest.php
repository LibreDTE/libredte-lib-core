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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Integration;

use Derafu\Enum\Status;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiRtcQueryResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Abstract\AbstractSiiWsdlResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioAec;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\EstadoEnvioSii;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiDteException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\GetDocumentSiiReceptionDateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\CheckXmlDocumentSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\RequestXmlDocumentSentStatusByEmailResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\SendXmlDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiDte\ValidateDocumentSignatureResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\CheckDocumentAssignabilityResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\DocumentEvent;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\GetDocumentSiiReceptionDateResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\ListDocumentEventsResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\SubmitDocumentAcceptanceResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\CheckAecSentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\GetAssignmentStatusResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRtc\SendAecResponse;
use libredte\lib\Core\Package\Billing\Component\Integration\Trait\ReviewsSentStatusTrait;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Las clases de respuesta con el XML que entrega el SII (tests/fixtures/sii).
 */
#[CoversClass(AbstractSiiWsdlResponse::class)]
#[CoversClass(AbstractSiiRtcQueryResponse::class)]
#[CoversClass(ValidateDocumentResponse::class)]
#[CoversClass(ValidateDocumentSignatureResponse::class)]
#[CoversClass(CheckXmlDocumentSentStatusResponse::class)]
#[CoversClass(RequestXmlDocumentSentStatusByEmailResponse::class)]
#[CoversClass(SendXmlDocumentResponse::class)]
#[CoversClass(SendAecResponse::class)]
#[CoversClass(CheckAecSentStatusResponse::class)]
#[CoversClass(GetAssignmentStatusResponse::class)]
#[CoversClass(SubmitDocumentAcceptanceResponse::class)]
#[CoversClass(CheckDocumentAssignabilityResponse::class)]
#[CoversClass(GetDocumentSiiReceptionDateResponse::class)]
#[CoversClass(ListDocumentEventsResponse::class)]
#[CoversClass(DocumentEvent::class)]
#[CoversClass(EstadoEnvioSii::class)]
#[CoversClass(SiiDteException::class)]
#[CoversClass(GetDocumentSiiReceptionDateException::class)]
#[CoversTrait(ReviewsSentStatusTrait::class)]
#[UsesClass(EstadoEnvioAec::class)]
class SiiResponsesFromSiiFixturesTest extends TestCase
{
    /**
     * Decodifica el XML de un fixture como lo hacen los jobs.
     *
     * @return array<int|string, mixed>
     */
    private function fixture(string $name): array
    {
        $document = new XmlDocument();
        $document->loadXml((string) file_get_contents(self::getFixturesPath('sii/' . $name)));

        return (new XmlService(new XmlEncoder(), new XmlDecoder(), new XmlValidator()))->decode($document);
    }

    // Consulta del estado de un documento (getEstDte).

    /**
     * @return array<string, array{string, string, Status}>
     */
    public static function provideDocumentStatuses(): array
    {
        return [
            'recibido y coincide' => ['dok', 'Documento recibido por el SII. Datos coinciden con los registrados.', Status::Success],
            'anulado por nota de crédito' => ['anc', 'Existe nota de crédito que anula documento', Status::Info],
            'montos modificados por nota de crédito' => ['mmc', 'Existe nota de crédito que modifica montos del documento.', Status::Info],
            'texto modificado por nota de crédito' => ['tmc', 'Existe nota de crédito que modifica texto del documento.', Status::Info],
        ];
    }

    #[DataProvider('provideDocumentStatuses')]
    public function testDocumentStatus(string $case, string $description, Status $type): void
    {
        $response = new ValidateDocumentResponse(
            $this->fixture('dte/get_est_dte_' . $case . '.xml'),
            ['RutCompania' => '76192083', 'DvCompania' => '9', 'Token' => 'ABCDEFGHIJKLM']
        );

        $this->assertSame(strtoupper($case), $response->getStatus());
        $this->assertSame($description, $response->getDescription());
        $this->assertSame(strtoupper($case) . ' - ' . $description, $response->getMessage());
        $this->assertSame($type, $response->getStatusType());

        $data = $response->getData();
        $this->assertTrue($data['received']);
        $this->assertSame('76192083-9', $data['company']);
        $this->assertNull($data['recipient']);
        $this->assertSame(1234567890, $data['query_number']);
        $this->assertSame('ABCDEFGHIJKLM', $data['token']);

        $this->assertArrayNotHasKey('token', $response->jsonSerialize());
        $this->assertSame($data, $response->toArray());
        $this->assertSame(strtoupper($case), $response->getHeaders()['ESTADO']);
        $this->assertSame([], $response->getBody());
    }

    public function testDocumentStatusWithTheRecipientAndTheDataOfTheDocument(): void
    {
        $response = new ValidateDocumentResponse($this->fixture('dte/get_est_dte_dok.xml'), [
            'RutCompania' => '76192083', 'DvCompania' => '9',
            'RutReceptor' => '60803000', 'DvReceptor' => 'K',
            'TipoDte' => 33, 'FolioDte' => 12, 'FechaEmisionDte' => '15-01-2024', 'MontoDte' => 11900,
        ]);

        $data = $response->getData();
        $this->assertSame('60803000-K', $data['recipient']);
        $this->assertSame(33, $data['document']);
        $this->assertSame(12, $data['number']);
        $this->assertSame('15-01-2024', $data['date']);
        $this->assertSame(11900, $data['total']);
    }

    public function testDocumentStatusWithoutTheExpectedHeadersIsRejected(): void
    {
        $this->expectException(SiiDteException::class);
        new ValidateDocumentResponse(['SII:RESPUESTA' => ['SII:RESP_BODY' => []]]);
    }

    // Consulta del estado de un documento por su firma (getEstDteAv).

    /**
     * @return array<string, array{string, Status}>
     */
    public static function provideSignatureStatuses(): array
    {
        return [
            'recibido y coincide' => ['dok', Status::Success],
            'recibido pero no coincide' => ['dnk', Status::Danger],
        ];
    }

    #[DataProvider('provideSignatureStatuses')]
    public function testDocumentStatusBySignature(string $case, Status $type): void
    {
        $response = new ValidateDocumentSignatureResponse(
            $this->fixture('dte/get_est_dte_av_' . $case . '.xml'),
            ['RutCompania' => '76192083', 'DvCompania' => '9', 'FirmaDte' => 'firma', 'Token' => 'ABCDEFGHIJKLM']
        );

        $this->assertSame(strtoupper($case), $response->getStatus());
        $this->assertSame($type, $response->getStatusType());
        $data = $response->getData();
        $this->assertTrue($data['received']);
        $this->assertSame('76192083-9', $data['company']);
        $this->assertSame('firma', $data['signature']);
        $this->assertNull($data['query_number']);
        $this->assertArrayNotHasKey('token', $response->jsonSerialize());
    }

    // Consulta del estado de un envío (getEstUp).

    public function testUploadRejectedBecauseOfTheSignature(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse(
            $this->fixture('dte/get_est_up_rechazado_error_firma.xml'),
            ['Rut' => '76192083', 'Dv' => '9', 'Token' => 'ABCDEFGHIJKLM']
        );

        $data = $response->getData();
        $this->assertSame('RFR', $data['status']);
        $this->assertTrue($data['error']);
        $this->assertSame('Rechazado por Error en Firma', $data['description']);
        $this->assertSame(1234567890, $response->getTrackId());
        $this->assertSame(100001, $data['query_number']);
        $this->assertSame('2024-01-15T10:00:00', $data['query_datetime']);
        $this->assertSame('76192083-9', $data['company']);
        $this->assertSame([], $data['documents']);
        $this->assertSame(['reported' => 0, 'accepted' => 0, 'rejected' => 0, 'repairs' => 0], $data['resume']);

        $this->assertSame(EstadoEnvioSii::RECHAZADO, $response->getEstadoEnvioSii());
        $this->assertSame('RFR - Rechazado por Error en Firma', $response->getReviewStatus());
        $this->assertNull($response->getReviewDetail());
        $this->assertArrayNotHasKey('token', $response->jsonSerialize());
    }

    public function testUploadProcessedWithAllTheDocumentsAccepted(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse(
            $this->fixture('dte/get_est_up_envio_procesado.xml'),
            ['Rut' => '76192083', 'Dv' => '9', 'Token' => 'ABCDEFGHIJKLM']
        );

        $data = $response->getData();
        $this->assertSame('EPR', $data['status']);
        $this->assertFalse($data['error']);
        $this->assertSame([
            33 => ['type' => 33, 'reported' => 4, 'accepted' => 4, 'rejected' => 0, 'repairs' => 0],
            56 => ['type' => 56, 'reported' => 1, 'accepted' => 1, 'rejected' => 0, 'repairs' => 0],
            61 => ['type' => 61, 'reported' => 3, 'accepted' => 3, 'rejected' => 0, 'repairs' => 0],
        ], $data['documents']);
        $this->assertSame(['reported' => 8, 'accepted' => 8, 'rejected' => 0, 'repairs' => 0], $data['resume']);

        $this->assertSame(EstadoEnvioSii::ACEPTADO, $response->getEstadoEnvioSii());
        $this->assertSame('EPR - Envío Procesado', $response->getReviewStatus());
        $this->assertSame('DTE aceptado', $response->getReviewDetail());
    }

    public function testUploadQueryOfATrackIdThatDoesNotExistReportsTheErrorOfTheSii(): void
    {
        $response = new CheckXmlDocumentSentStatusResponse(
            $this->fixture('dte/get_est_up_track_id_inexistente.xml'),
            ['Rut' => '76192083', 'Dv' => '9', 'TrackId' => 123]
        );

        $data = $response->getData();
        $this->assertSame('-11', $data['status']);
        $this->assertTrue($data['error']);
        $this->assertSame('Error de Proceso', $data['description']);
        $this->assertSame(123, $response->getTrackId());
        $this->assertSame(EstadoEnvioSii::ENVIADO, $response->getEstadoEnvioSii());
        $this->assertSame('-11 - Error de Proceso', $response->getReviewStatus());
    }

    public function testProcessedUploadWithRejectedOrRepairedDocumentsIsReviewedAsSuch(): void
    {
        $envelope = static fn (string $reported, string $accepted, string $rejected, string $repairs): array => [
            'SII:RESPUESTA' => [
                'SII:RESP_HDR' => ['TRACKID' => '1', 'ESTADO' => 'EPR', 'GLOSA' => 'Envio Procesado'],
                'SII:RESP_BODY' => [
                    'TIPO_DOCTO' => '33',
                    'INFORMADOS' => $reported,
                    'ACEPTADOS' => $accepted,
                    'RECHAZADOS' => $rejected,
                    'REPAROS' => $repairs,
                ],
            ],
        ];

        $rejected = new CheckXmlDocumentSentStatusResponse($envelope('2', '1', '1', '0'));
        $this->assertSame(EstadoEnvioSii::RECHAZADO, $rejected->getEstadoEnvioSii());
        $this->assertSame('RCH - DTE Rechazado', $rejected->getReviewStatus());
        $this->assertNull($rejected->getReviewDetail());

        $repaired = new CheckXmlDocumentSentStatusResponse($envelope('2', '2', '0', '1'));
        $this->assertSame(EstadoEnvioSii::REPARO, $repaired->getEstadoEnvioSii());
        $this->assertSame('RLV - DTE Aceptado con Reparos Leves', $repaired->getReviewStatus());
    }

    // Envío del XML.

    public function testSendXmlResponseKeepsTheTrackId(): void
    {
        $response = new SendXmlDocumentResponse($this->fixture('dte/send_xml_ok.xml'));

        $this->assertSame(1234567890, $response->getTrackId());
        $this->assertSame(['track_id' => 1234567890], $response->toArray());
        $this->assertSame($response->toArray(), $response->jsonSerialize());
    }

    // Reenvío del correo con el estado del envío.

    public function testRequestOfTheStatusByEmail(): void
    {
        $response = new RequestXmlDocumentSentStatusByEmailResponse(
            $this->fixture('dte/reenvio_correo_ok.xml'),
            ['RutEmpresa' => '76192083', 'DvEmpresa' => '9', 'TrackId' => 123, 'Token' => 'ABCDEFGHIJKLM']
        );

        $this->assertSame('0', $response->getStatus());
        $this->assertSame('Requerimiento recibido OK.', $response->getDescription());
        $this->assertSame(Status::Success, $response->getStatusType());
        $this->assertSame('76192083-9', $response->getData()['company']);
        $this->assertSame(123, $response->getData()['track_id']);
        $this->assertSame($response->getData(), $response->toArray());
        $this->assertArrayNotHasKey('token', $response->jsonSerialize());
    }

    // Cesiones (RTC).

    public function testAecUploadOfACompanyThatIsNotAuthorizedHasNoTrackId(): void
    {
        $response = new SendAecResponse($this->fixture('rtc/send_aec_empresa_no_autorizada.xml'));

        $this->assertSame(0, $response->getTrackId());
        $this->assertSame(['track_id' => 0], $response->toArray());
        $this->assertSame($response->toArray(), $response->jsonSerialize());
    }

    public function testAecEnvelopeRejectedBecauseOfTheEnvelopeSignature(): void
    {
        $response = new CheckAecSentStatusResponse($this->fixture('rtc/get_est_envio_rechazado_firma_sobre.xml'));

        $this->assertTrue($response->isRequestOk());
        $this->assertSame(1234567890, $response->getTrackId());
        $this->assertSame('RFS', $response->getStatus());
        $this->assertTrue($response->getData()['error']);
        $this->assertTrue($response->isFinal());
        $this->assertSame(Status::Danger, $response->getStatusType());
    }

    public function testStatusOfADocumentThatWasNotAssigned(): void
    {
        $response = new GetAssignmentStatusResponse($this->fixture('rtc/get_est_cesion_no_cedido.xml'), ['Token' => 'ABCDEFGHIJKLM']);

        $this->assertFalse($response->isAssigned());
        $this->assertNull($response->getHolder());
        $this->assertSame(Status::Danger, $response->getStatusType());
        $data = $response->getData();
        $this->assertFalse($data['assigned']);
        $this->assertSame('2', $data['status']);
        $this->assertNull($data['holder']);
        $this->assertNull($data['last_annotation']);
    }

    // Registro de reclamos.

    public function testAssignabilityOfADocumentThatIsAssignableByTheLapseOfTheTerm(): void
    {
        $response = new CheckDocumentAssignabilityResponse($this->fixture('rcv/consultar_doc_dte_cedible.xml'));

        $this->assertSame(25, $response->getCode());
        $this->assertSame('DTE Cedible, habiendo pasado 8 dias se entiende dado acuse de recibo', $response->getDescription());
        $this->assertTrue($response->isAssignable());
        $this->assertSame(['codigo' => 25, 'glosa' => $response->getDescription()], $response->toArray());
        $this->assertSame($response->toArray(), $response->jsonSerialize());
    }

    public function testAcceptanceOutsideTheTermIsReportedWithTheCodeOfTheSii(): void
    {
        $response = new SubmitDocumentAcceptanceResponse($this->fixture('rcv/ingresar_aceptacion_reclamo_doc_plazo_vencido.xml'));

        $this->assertSame(8, $response->getCode());
        $this->assertSame('Pasados 8 dias despues de la recepcion no es posible registrar reclamos o eventos', $response->getDescription());
        $this->assertTrue($response->isAccepted());
        $this->assertSame(['codigo' => 8, 'glosa' => $response->getDescription()], $response->toArray());
        $this->assertSame($response->toArray(), $response->jsonSerialize());
    }

    public function testAcceptanceCodesThatAreNotAcceptances(): void
    {
        $response = new SubmitDocumentAcceptanceResponse(['return' => ['codResp' => 16]]);

        $this->assertSame(16, $response->getCode());
        $this->assertSame('', $response->getDescription());
        $this->assertFalse($response->isAccepted());
    }

    public function testReceptionDateIsReturnedInIsoFormat(): void
    {
        $response = new GetDocumentSiiReceptionDateResponse($this->fixture('rcv/consultar_fecha_recepcion_sii.xml'));

        $this->assertSame('2024-01-15 10:00:00', $response->getReceptionDate());
        $this->assertSame(['fecha_recepcion_sii' => '2024-01-15 10:00:00'], $response->toArray());
        $this->assertSame($response->toArray(), $response->jsonSerialize());
    }

    public function testReceptionDateThatTheSiiDoesNotHaveIsAnError(): void
    {
        $this->expectException(GetDocumentSiiReceptionDateException::class);
        new GetDocumentSiiReceptionDateResponse(['data' => '']);
    }

    public function testDocumentWithoutEventsHasNoEvents(): void
    {
        $response = new ListDocumentEventsResponse($this->fixture('rcv/listar_eventos_hist_doc_sin_eventos.xml'));

        $this->assertSame([], $response->getEvents());
        $this->assertSame([], $response->toArray());
        $this->assertSame([], $response->jsonSerialize());
    }

    public function testEventsOfADocumentAreListedWithTheirDatesInIsoFormat(): void
    {
        // Estructura que arma el job con el objeto SOAP cuando hay eventos (no hay
        // una respuesta real con eventos: el documento de prueba no tiene).
        $single = new ListDocumentEventsResponse(['return' => ['listaEventosDoc' => [
            'codEvento' => 'ACD', 'descEvento' => 'Acepta Contenido del Documento',
            'rutResponsable' => '12345678', 'dvResponsable' => '5', 'fechaEvento' => '15-01-2024 10:00:00',
        ]]]);
        $this->assertCount(1, $single->getEvents());
        $event = $single->getEvents()[0];
        $this->assertSame('ACD', $event->getCodigo());
        $this->assertSame('Acepta Contenido del Documento', $event->getGlosa());
        $this->assertSame('12345678-5', $event->getResponsable());
        $this->assertSame('2024-01-15 10:00:00', $event->getFecha());
        $this->assertSame([
            'codigo' => 'ACD', 'glosa' => 'Acepta Contenido del Documento',
            'responsable' => '12345678-5', 'fecha' => '2024-01-15 10:00:00',
        ], $event->toArray());
        $this->assertSame($event->toArray(), $event->jsonSerialize());

        $several = new ListDocumentEventsResponse(['listaEventosDoc' => [
            ['codEvento' => 'ERM', 'descEvento' => 'Recibo de Mercaderías', 'rutResponsable' => '1', 'dvResponsable' => '9', 'fechaEvento' => 'sin formato'],
            ['codEvento' => 'RCD'],
        ]]);
        $this->assertCount(2, $several->getEvents());
        $this->assertSame('sin formato', $several->getEvents()[0]->getFecha());
        $this->assertSame('-', $several->getEvents()[1]->getResponsable());
        $this->assertSame([$several->getEvents()[0]->toArray(), $several->getEvents()[1]->toArray()], $several->jsonSerialize());
    }

    // Estados de envío.

    public function testEstadoEnvioSiiFromTheCodesOfTheSii(): void
    {
        $this->assertSame(EstadoEnvioSii::ACEPTADO, EstadoEnvioSii::tryFromSiiCodigo('EPR'));
        $this->assertSame(EstadoEnvioSii::ACEPTADO, EstadoEnvioSii::tryFromSiiCodigo('LOK'));
        $this->assertSame(EstadoEnvioSii::REPARO, EstadoEnvioSii::tryFromSiiCodigo('RPR'));
        $this->assertSame(EstadoEnvioSii::RECHAZADO, EstadoEnvioSii::tryFromSiiCodigo('RFR'));
        $this->assertSame(EstadoEnvioSii::ENVIADO, EstadoEnvioSii::tryFromSiiCodigo('PDR'));
        $this->assertSame(EstadoEnvioSii::ENVIADO, EstadoEnvioSii::tryFromSiiCodigo('-11'));
        $this->assertNull(EstadoEnvioSii::tryFromSiiCodigo('XYZ'));
        $this->assertSame(EstadoEnvioSii::RECHAZADO, EstadoEnvioSii::tryFromGlosa('RSC - Rechazado por Error en Schema'));
        $this->assertNull(EstadoEnvioSii::tryFromGlosa('Glosa desconocida'));
    }

    public function testEstadoEnvioSiiDescribesItself(): void
    {
        $expected = [
            'E' => ['Enviado', Status::Secondary, false, false, false, false],
            'A' => ['Aceptado', Status::Success, true, true, false, false],
            'R' => ['Con reparos', Status::Warning, true, true, false, true],
            'X' => ['Rechazado', Status::Danger, true, false, true, true],
        ];

        foreach (EstadoEnvioSii::cases() as $estado) {
            [$label, $type, $final, $accepted, $glosa, $detalle] = $expected[$estado->value];
            $this->assertSame($label, $estado->getLabel());
            $this->assertSame($type, $estado->getStatusType());
            $this->assertSame($final, $estado->isFinal());
            $this->assertSame($accepted, $estado->isAceptado());
            $this->assertSame($glosa, $estado->shouldStoreGlosa());
            $this->assertSame($detalle, $estado->shouldStoreDetalle());
        }
    }

    // Códigos de error documentados del SII. No hay respuesta real capturada de
    // estos casos: se arman con la estructura de las respuestas reales.

    /**
     * @param array<string, string> $header
     * @return array<string, mixed>
     */
    private function sii(array $header): array
    {
        return ['SII:RESPUESTA' => ['SII:RESP_HDR' => $header, 'SII:RESP_BODY' => []]];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideStatusErrors(): array
    {
        return [
            'token inactivo' => ['002', 'Token Inactivo'],
            'error de estado' => ['-1', 'ERROR: RETORNO CAMPO ESTADO'],
            'código desconocido' => ['XYZ', 'Error desconocido código #XYZ al subir el XML.'],
        ];
    }

    #[DataProvider('provideStatusErrors')]
    public function testDocumentStatusErrors(string $status, string $description): void
    {
        $response = new ValidateDocumentResponse($this->sii(['ESTADO' => $status]));

        $this->assertSame($status, $response->getStatus());
        $this->assertSame($description, $response->getDescription());
        $this->assertNull($response->getData()['received']);
    }

    #[DataProvider('provideStatusErrors')]
    public function testDocumentStatusBySignatureErrors(string $status, string $description): void
    {
        $response = new ValidateDocumentSignatureResponse($this->sii(['ESTADO' => $status]));

        $this->assertSame($status, $response->getStatus());
        $this->assertSame($description, $response->getDescription());
        $this->assertFalse($response->getData()['received']);
    }

    public function testDocumentStatusBySignatureWithAnAttentionNumber(): void
    {
        $response = new ValidateDocumentSignatureResponse($this->sii([
            'ESTADO' => 'DOK',
            'NUM_ATENCION' => '77   ( 2024/01/15 10:00:00)',
        ]));

        $this->assertSame(77, $response->getData()['query_number']);
        $this->assertSame('2024-01-15T10:00:00', $response->getData()['query_datetime']);
    }

    public function testAnAttentionNumberThatTheSiiSendsInAnotherFormatIsRejected(): void
    {
        $response = new ValidateDocumentResponse($this->sii(['ESTADO' => 'DOK', 'NUM_ATENCION' => 'no es un número']));

        $this->expectException(TranslatableInvalidArgumentException::class);
        $response->getData();
    }

    public function testUploadStatusErrors(): void
    {
        $error = new CheckXmlDocumentSentStatusResponse($this->sii(['ESTADO' => '-5']));
        $this->assertSame('ERROR RETORNO DATOS', $error->getDescription());
        $this->assertTrue($error->getData()['error']);

        $unknown = new CheckXmlDocumentSentStatusResponse($this->sii(['ESTADO' => 'ZZZ']));
        $this->assertSame('Error desconocido código #ZZZ al subir el XML.', $unknown->getDescription());
        $this->assertTrue($unknown->getData()['error']);
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker;

use DateTime;
use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Contract\SignatureComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Entity\Xml as XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DispatcherWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagManagerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentEnvelopeInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Entity\SobreEnvio;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;
use libredte\lib\Core\Package\Billing\Component\Document\Support\DocumentEnvelope;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Mandatario;

/**
 * Clase para la gestión de sobres de documentos (para envíos/transferencias).
 */
class DispatcherWorker extends AbstractWorker implements DispatcherWorkerInterface
{
    public function __construct(
        private XmlComponentInterface $xmlComponent,
        private SignatureComponentInterface $signatureComponent,
        private DocumentBagManagerWorkerInterface $documentBagManagerWorker,
        iterable $jobs = [],
        iterable $handlers = [],
        iterable $strategies = []
    ) {
        parent::__construct(
            jobs: $jobs,
            handlers: $handlers,
            strategies: $strategies
        );
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(
        DocumentEnvelopeInterface $envelope
    ): DocumentEnvelopeInterface {
        $this->ensureSobreEnvio($envelope);
        $this->ensureDocuments($envelope);
        $this->ensureEmisor($envelope);
        $this->ensureMandatario($envelope);
        $this->ensureReceptor($envelope);
        $this->ensureCaratula($envelope);
        $this->ensureXmlDocument($envelope);

        return $envelope;
    }

    /**
     * {@inheritDoc}
     */
    public function loadXml(string $xml): DocumentEnvelopeInterface
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXML($xml);
        $envelope = new DocumentEnvelope();
        $envelope->setXmlDocument($xmlDocument);

        return $this->normalize($envelope);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(
        DocumentEnvelopeInterface|XmlInterface|string $source
    ): void {
        // TODO: Agregar validaciones del sobre.
    }

    /**
     * {@inheritDoc}
     */
    public function validateSchema(
        DocumentEnvelopeInterface|XmlInterface|string $source
    ): void {
        // Obtener el documento XML.
        if ($source instanceof DocumentEnvelopeInterface) {
            $xmlDocument = $source->getXmlDocument();
        } elseif ($source instanceof XmlInterface) {
            $xmlDocument = $source;
        } else {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($source);
        }

        // Validar esquema del sobre de documentos (EnvioDTE y EnvioBOLETA).
        $schema = sprintf(
            '%s/resources/schemas/%s',
            dirname(__DIR__, 6),
            $xmlDocument->getSchema()
        );
        $this->xmlComponent->getValidatorWorker()->validateSchema(
            $xmlDocument,
            $schema
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validateSignature(
        DocumentEnvelopeInterface|XmlInterface|string $source
    ): void {
        $xml = $source instanceof DocumentEnvelopeInterface
            ? $source->getXmlDocument()
            : $source
        ;

        $this->signatureComponent->getValidatorWorker()->validateXml($xml);
    }

    /**
     * Se asegura que exista un arreglo con las bolsas de los documentos
     * tributarios si no está definida y existe un documento XML en el sobre.
     *
     * @param DocumentEnvelopeInterface $envelope
     * @return void
     */
    protected function ensureDocuments(
        DocumentEnvelopeInterface $envelope
    ): void {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getDocuments() !== null || !$envelope->getSobreEnvio()) {
            return;
        }

        // Buscar documentos (DTE) en el XML del sobre y crear la bolsa de
        // cada documento para agregarla al listado de documentos que el sobre
        // gestiona.
        foreach ($envelope->getSobreEnvio()->getXmlDocumentos() as $xml) {
            $xmlDocument = new XmlDocument();
            $xmlDocument->loadXml($xml);
            $bag = $this->documentBagManagerWorker->create($xmlDocument);
            $envelope->addDocument($bag);
        }
    }

    protected function ensureEmisor(DocumentEnvelopeInterface $envelope): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getEmisor() || !$envelope->getDocuments()) {
            return;
        }

        // Asignar como emisor del sobre el emisor del primer documento.
        $emisor = $envelope->getDocuments()[0]->getEmisor();
        $envelope->setEmisor($emisor);
    }

    protected function ensureMandatario(DocumentEnvelopeInterface $envelope): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getMandatario() || !$envelope->getCertificate()) {
            return;
        }

        // Asignar como mandatario del sobre el usuario del certificado digital.
        $mandatario = new Mandatario($envelope->getCertificate()->getId());
        $envelope->setMandatario($mandatario);
    }

    protected function ensureReceptor(DocumentEnvelopeInterface $envelope): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getReceptor() || !$envelope->getDocuments()) {
            return;
        }

        // Asignar como receptor del sobre el receptor del primer documento.
        $receptor = $envelope->getDocuments()[0]->getReceptor();
        $envelope->setReceptor($receptor);
    }

    protected function ensureCaratula(DocumentEnvelopeInterface $envelope): void
    {
        // Verificar si es necesario, y se puede, asignar.
        if (
            $envelope->getCaratula()
            || !$envelope->getDocuments()
            || !$envelope->getEmisor()
            || !$envelope->getMandatario()
            || !$envelope->getReceptor()
        ) {
            return;
        }

        // Si se agregaron más tipos de documentos que los permitidos error.
        $SubTotDTE = $this->getResumen($envelope);

        $maximoTiposDocumentos = $envelope->getTipoSobre()->getMaximoTiposDocumentos();
        if (isset($SubTotDTE[$maximoTiposDocumentos])) {
            throw new DispatcherException(
                'Se agregaron más tipos de documentos de los que son permitidos en el sobre (%d).',
                $maximoTiposDocumentos
            );
        }

        // Timestamp de la firma del sobre que se generará.
        $timestamp = date('Y-m-d\TH:i:s');

        // Crear datos de la carátula.
        $caratula = [
            '@attributes' => [
                'version' => '1.0',
            ],
            'RutEmisor' => $envelope->getEmisor()->getRut(),
            'RutEnvia' => $envelope->getMandatario()->getRun(),
            'RutReceptor' => $envelope->getReceptor()->getRut(),
            'FchResol' => $envelope->getEmisor()->getAutorizacionDte()->getFechaResolucion(),
            'NroResol' => $envelope->getEmisor()->getAutorizacionDte()->getNumeroResolucion(),
            'TmstFirmaEnv' => $timestamp,
            'SubTotDTE' => $SubTotDTE,
        ];

        // Asignar carátula al sobre.
        $envelope->setCaratula($caratula);
    }

    protected function ensureXmlDocument(
        DocumentEnvelopeInterface $envelope
    ): void {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getXmlDocument() || !$envelope->getDocuments()) {
            return;
        }

        // Generar la estructura base del documento XML del sobre de documentos.
        $data = [
            $envelope->getTipoSobre()->getTagXml() => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte '
                        . $envelope->getTipoSobre()->getSchema()
                    ,
                    'version' => '1.0',
                ],
                'SetDTE' => [
                    '@attributes' => [
                        'ID' => 'LibreDTE_SetDoc',
                    ],
                    'Caratula' => $envelope->getCaratula(),
                    'DTE' => '',
                ],
            ],
        ];

        // Generar el XML de los documentos que se deberán incorporar al sobre.
        $documents = [];
        foreach ($envelope->getDocuments() as $document) {
            $documents[] = trim(str_replace(
                [
                    '<?xml version="1.0" encoding="ISO-8859-1"?>',
                    '<?xml version="1.0"?>',
                ],
                '',
                $document->getDocument()->getXml()
            ));
        }

        // Crear el documento XML del sobre (estructura base, sin DTE).
        $xmlDocument = $this->xmlComponent->getEncoderWorker()->encode($data);

        // Agregar los DTE dentro de SetDTE reemplazando el tag vacio DTE.
        $xmlBaseSobre = $xmlDocument->saveXML();
        $xmlSobre = str_replace('<DTE/>', implode("\n", $documents), $xmlBaseSobre);

        // Reemplazar el documento XML del sobre del envío con el string XML que
        // contiene ahora los documentos del envío.
        $xmlDocument->loadXML($xmlSobre);

        // Asignar el documento XML al sobre de documentos.
        $envelope->setXmlDocument($xmlDocument);

        // Si existe un certificado en el sobre de documento se firma el sobre.
        if ($envelope->getCertificate()) {
            $this->sign($envelope);
        }
    }

    protected function ensureSobreEnvio(
        DocumentEnvelopeInterface $envelope
    ): void {
        // Verificar si es necesario, y se puede, asignar.
        if ($envelope->getSobreEnvio() || !$envelope->getXmlDocument()) {
            return;
        }

        // Asignar la entidad del sobre del envío.
        $sobreEnvio = new SobreEnvio($envelope->getXmlDocument());
        $envelope->setSobreEnvio($sobreEnvio);
    }

    /**
     * Obtiene el resumen de los documentos que hay en el sobre.
     *
     * Esto se usa para para generar los tags del XML `SubTotDTE`.
     *
     * @param DocumentEnvelopeInterface $envelope
     * @return array Arreglo con el resumen de documentos por tipo.
     */
    protected function getResumen(DocumentEnvelopeInterface $envelope): array
    {
        // Contar los documentos por cada tipo.
        $subtotales = [];
        foreach ($envelope->getDocuments() as $document) {
            $codigo = $document->getTipoDocumento()->getCodigo();
            if (!isset($subtotales[$codigo])) {
                $subtotales[$codigo] = 0;
            }
            $subtotales[$codigo]++;
        }

        // Crear el resumen con los datos en el formato del tag `SubTotDTE`.
        $SubTotDTE = [];
        foreach ($subtotales as $tipo => $subtotal) {
            $SubTotDTE[] = [
                'TpoDTE' => $tipo,
                'NroDTE' => $subtotal,
            ];
        }

        // Entregar el resumen.
        return $SubTotDTE;
    }

    /**
     * Firma el sobre de documentos.
     *
     * @param DocumentEnvelopeInterface $envelope
     * @return void
     */
    protected function sign(DocumentEnvelopeInterface $envelope): void
    {
        // El certificado digital para realizar la firma.
        $certificate = $envelope->getCertificate();

        // Asignar marca de tiempo si no se pasó una.
        $timestamp = $envelope->getCaratula()['TmstFirmaEnv'];

        // Corroborar que el certificado esté vigente según el timestamp usado.
        if (!$certificate->isActive($timestamp)) {
            throw new DispatcherException(sprintf(
                'El certificado digital de %s no está vigente en el tiempo %s, su rango de vigencia es del %s al %s.',
                $certificate->getID(),
                (new DateTime($timestamp))->format('d/m/Y H:i'),
                (new DateTime($certificate->getFrom()))->format('d/m/Y H:i'),
                (new DateTime($certificate->getTo()))->format('d/m/Y H:i'),
            ));
        }

        // Obtener el documento XML a firmar.
        $xmlDocument = $envelope->getXmlDocument();

        // Firmar el documento XML del sobre y retornar el XML firmado.
        $xmlSigned = $this->signatureComponent->getGeneratorWorker()->signXml(
            $xmlDocument,
            $certificate,
            $envelope->getId()
        );

        // Cargar XML en el documento y luego en la bolsa.
        $xmlDocument->loadXml($xmlSigned);
        $envelope->setXmlDocument($xmlDocument);
    }
}

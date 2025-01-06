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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use DateTime;
use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Contract\SignatureComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Signature\Exception\SignatureException;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlComponentInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\BuilderStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\NormalizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\ValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\BuilderException;

/**
 * Clase abstracta (base) para las estrategias de construcción ("builders") de
 * documentos tributarios.
 */
abstract class AbstractBuilderStrategy extends AbstractStrategy implements BuilderStrategyInterface
{
    /**
     * Clase del documento que este "builder" construirá.
     *
     * @var string
     */
    protected string $documentClass;

    public function __construct(
        private NormalizerWorkerInterface $normalizerWorker,
        private SanitizerWorkerInterface $sanitizerWorker,
        private ValidatorWorkerInterface $validatorWorker,
        private XmlComponentInterface $xmlComponent,
        private SignatureComponentInterface $signatureComponent
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(XmlInterface $xmlDocument): DocumentInterface
    {
        $class = $this->documentClass;
        $document = new $class($xmlDocument);

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function build(DocumentBagInterface $bag): DocumentInterface
    {
        // Normalizar los datos del documento de la bolsa si corresponde.
        $normalize = $bag->getNormalizerOptions()['normalize'] ?? true;
        if ($normalize) {
            // Normalizar los datos parseados de la bolsa.
            $this->normalizerWorker->normalize($bag);

            // Sanitizar los datos normalizados de la bolsa.
            $this->sanitizerWorker->sanitize($bag);

            // Validar los datos normalizados y sanitizados de la bolsa.
            $this->validatorWorker->validate($bag);
        }

        // Si existe un CAF en la bolsa se timbra.
        if ($bag->getCaf()) {
            $this->stamp($bag);
        }

        // Si existe un certificado en la bolsa se firma.
        if ($bag->getCertificate()) {
            $this->sign($bag);
        }

        // Crear el documento XML del DTE y agregar a la bolsa si no se agregó
        // previamente (al firmar).
        if (!$bag->getXmlDocument()) {
            $xmlDocument = $this->xmlComponent->getEncoderWorker()->encode(
                $bag->getData()
            );
            $bag->setXmlDocument($xmlDocument);
        }

        // Crear el DTE y agregar a la bolsa.
        $document = $this->create($bag->getXmlDocument());
        $bag->setDocument($document);

        // Entregar la instancia del documento tributario creado.
        return $document;
    }

    /**
     * Timbra un documento tributario.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function stamp(DocumentBagInterface $bag): void
    {
        // Obtener el CAF de la bolsa.
        $caf = $bag->getCaf();

        // Generar un borrador del DTE para manipular sus datos.
        $xmlDocument = $this->xmlComponent->getEncoderWorker()->encode(
            $bag->getNormalizedData()
        );
        $draft = $this->create($xmlDocument);

        // Verificar que el folio del documento esté dentro del rango del CAF.
        if (!$caf->enRango($draft->getFolio())) {
            throw new BuilderException(sprintf(
                'El folio %d del documento %s no está disponible en el rango del CAF %s.',
                $draft->getFolio(),
                $draft->getId(),
                $caf->getId()
            ));
        }

        // Asignar marca de tiempo si no se pasó una.
        $timestamp = $bag->getBuilderOptions()['timestamp'] ?? date('Y-m-d\TH:i:s');

        // Corroborar que el CAF esté vigente según el timestamp usado.
        if (!$caf->vigente($timestamp)) {
            throw new BuilderException(sprintf(
                'El CAF %s que contiene el folio %d del documento %s no está vigente, venció el día %s.',
                $caf->getId(),
                $draft->getFolio(),
                $draft->getId(),
                (new DateTime($caf->getFechaVencimiento()))->format('d/m/Y'),
            ));
        }

        // Preparar datos del timbre.
        $tedData = $draft->getPlantillaTED();
        $cafArray = $this->xmlComponent->getDecoderWorker()->decode(
            $caf->getXmlDocument()
        );
        $tedData['TED']['DD']['CAF'] = $cafArray['AUTORIZACION']['CAF'];
        $tedData['TED']['DD']['TSTED'] = $timestamp;

        // Armar XML del timbre y obtener los datos a timbrar (tag DD: datos
        // del documento).
        $tedXmlDocument = $this->xmlComponent->getEncoderWorker()->encode($tedData);
        $ddToStamp = $tedXmlDocument->C14NWithIsoEncodingFlattened('/TED/DD');

        // Timbrar los "datos a timbrar" $ddToStamp.
        $privateKey = $caf->getPrivateKey();
        $signatureAlgorithm = OPENSSL_ALGO_SHA1;
        try {
            $timbre = $this->signatureComponent->getGeneratorWorker()->sign(
                $ddToStamp,
                $privateKey,
                $signatureAlgorithm
            );
        } catch (SignatureException) {
            throw new BuilderException('No fue posible timbrar los datos.');
        }

        // Agregar timbre al TED.
        $tedData['TED']['FRMT']['@value'] = $timbre;

        // Actualizar los datos del documento incorporando el timbre calculado.
        $bag->setTimbre($tedData);
    }

    /**
     * Firma un documento tributario.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function sign(DocumentBagInterface $bag): void
    {
        $certificate = $bag->getCertificate();

        // Asignar marca de tiempo si no se pasó una.
        $timestamp = $bag->getBuilderOptions()['timestamp'] ?? date('Y-m-d\TH:i:s');

        // Corroborar que el certificado esté vigente según el timestamp usado.
        if (!$certificate->isActive($timestamp)) {
            throw new BuilderException(sprintf(
                'El certificado digital de %s no está vigente en el tiempo %s, su rango de vigencia es del %s al %s.',
                $certificate->getID(),
                (new DateTime($timestamp))->format('d/m/Y H:i'),
                (new DateTime($certificate->getFrom()))->format('d/m/Y H:i'),
                (new DateTime($certificate->getTo()))->format('d/m/Y H:i'),
            ));
        }

        // Agregar timestamp.
        $data = $bag->getData();
        $tagXml = $bag->getTipoDocumento()->getTagXml()->getNombre();
        $data['DTE'][$tagXml]['TmstFirma'] = $timestamp;
        $xmlDocument = $this->xmlComponent->getEncoderWorker()->encode($data);

        // Firmar el tag que contiene el documento y retornar el XML firmado.
        $xmlSigned = $this->signatureComponent->getGeneratorWorker()->signXml(
            $xmlDocument,
            $certificate,
            $bag->getId()
        );

        // Cargar XML en el documento y luego en la bolsa.
        $xmlDocument->loadXml($xmlSigned);
        $bag->setXmlDocument($xmlDocument);
    }
}

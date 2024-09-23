<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Core\Sii\Dte\Documento;

use DateTime;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\SignatureException;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\Contribuyente\Contribuyente;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\Caf;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\CafException;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlException;
use libredte\lib\Core\Xml\XmlUtils;
use libredte\lib\Core\Xml\XmlValidator;

/**
 * Clase abstracta (base) de la representación de un documento.
 */
abstract class AbstractDocumento
{
    /**
     * Código del tipo de documento tributario al que está asociada esta
     * instancia de un documento.
     */
    protected int $codigo;

    /**
     * Instancia del tipo de documento tributario, según el código, asociado a
     * esta instancia de un documento.
     *
     * @var DocumentoTipo
     */
    private DocumentoTipo $tipo;

    /**
     * Arreglo con los datos del documento tributario.
     *
     * Estos datos podrían o no haber sido normalizados. Sin embargo, si no
     * fueron normalizados, se espera que se hayan asignados según lo que el
     * SII requiere (o sea, como si se hubiesen "normalizado").
     *
     * @var array
     */
    protected array $data;

    /**
     * Instancia del documento XML asociado a los datos.
     *
     * @var XmlDocument
     */
    protected XmlDocument $xmlDocument;

    /**
     * Contribuyente emisor del documento.
     *
     * Este objeto representa al contribuyente que emitió el documento.
     *
     * @var Contribuyente
     */
    private Contribuyente $emisor;

    /**
     * Contribuyente receptor del documento.
     *
     * Este objeto representa al contribuyente que recibió el documento.
     *
     * @var Contribuyente
     */
    private Contribuyente $receptor;

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase.
     *
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Entrega la instancia del tipo de documento asociado a este documento.
     *
     * @return DocumentoTipo
     */
    public function getTipo(): DocumentoTipo
    {
        if (!isset($this->tipo)) {
            $this->tipo = new DocumentoTipo(
                $this->codigo,
                $this->dataProvider
            );
        }

        return $this->tipo;
    }

    /**
     * Asigna los datos del documento.
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        unset($this->xmlDocument);

        return $this;
    }

    /**
     * Obtiene los datos del documento.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Carga el XML completo de un documento para crear la instancia del
     * documento XML asociada a este documento.
     *
     * @param string $xml
     * @return self
     */
    public function loadXML(string $xml): self
    {
        $this->xmlDocument = new XmlDocument();
        $this->xmlDocument->loadXML($xml);

        return $this;
    }

    /**
     * Entrega el string XML del documento XML.
     *
     * Es un wrapper de XmlDocument::getXML();
     *
     * @return string
     */
    public function getXml(): string
    {
        return $this->getXmlDocument()->getXML();
    }

    /**
     * Entrega el string XML del documento XML.
     *
     * Es un wrapper de XmlDocument::saveXML();
     *
     * @return string
     */
    public function saveXml(): string
    {
        return $this->getXmlDocument()->saveXML();
    }

    /**
     * Entrega el ID que LibreDTE asigna al documento.
     *
     * @return string
     */
    public function getId(): string
    {
        return sprintf(
            'LibreDTE_T%dF%d',
            $this->getCodigo(),
            $this->getFolio()
        );
    }

    /**
     * Entrega el código del tipo de documento tributario.
     *
     * @return int
     */
    public function getCodigo(): int
    {
        return $this->codigo;
    }

    /**
     * Entrega el folio del documento tributario.
     *
     * @return int
     */
    public function getFolio(): int
    {
        $data = $this->getData();

        return (int) $data['Encabezado']['IdDoc']['Folio'];
    }

    /**
     * Entrega la fecha de emisión asignada al documento tributario.
     *
     * Esta es la fecha de emisión informada al SII del documento, no es la
     * fecha de creación real del documento en LibreDTE.
     *
     * @return string
     */
    public function getFechaEmision(): string
    {
        $data = $this->getData();

        return $data['Encabezado']['IdDoc']['FchEmis'];
    }

    /**
     * Obtiene el contribuyente emisor del documento.
     *
     * @return Contribuyente Instancia de Contribuyente que representa al
     * emisor.
     */
    public function getEmisor(): Contribuyente
    {
        if (!isset($this->emisor)) {
            $data = $this->getData();

            $this->emisor = new Contribuyente(
                data: $data['Encabezado']['Emisor'],
                dataProvider: $this->dataProvider
            );
        }

        return $this->emisor;
    }

    /**
     * Obtiene el contribuyente receptor del documento.
     *
     * @return Contribuyente Instancia de Contribuyente que representa al
     * receptor.
     */
    public function getReceptor(): Contribuyente
    {
        if (!isset($this->receptor)) {
            $data = $this->getData();

            $this->receptor = new Contribuyente(
                data: $data['Encabezado']['Receptor'],
                dataProvider: $this->dataProvider
            );
        }

        return $this->receptor;
    }

    /**
     * Entrega todos los valores del tag "Totales".
     *
     * @return array
     */
    public function getTotales(): array
    {
        $data = $this->getData();

        return $data['Encabezado']['Totales'];
    }

    /**
     * Entrega el monto total del documento.
     *
     * El monto estará en la moneda del documento.
     *
     * @return int|float Monto total del documento.
     */
    public function getMontoTotal(): int|float
    {
        $data = $this->getData();

        return $data['Encabezado']['Totales']['MntTotal'];
    }

    /**
     * Entrega el detalle del documento.
     *
     * Se puede solicitar todo el detalle o el detalle de una línea en
     * específico.
     *
     * @param integer|null $index Índice de la línea de detalle solicitada o
     * `null` (por defecto) para obtener todas las líneas.
     * @return array
     */
    public function getDetalle(?int $index = null): array
    {
        $data = $this->getData();

        return $index !== null
            ? $data['Detalle'][$index] ?? []
            : $data['Detalle'] ?? []
        ;
    }

    /**
     * Obtiene la instancia del documento XML asociada al documento tributario.
     *
     * @return XmlDocument
     */
    public function getXmlDocument(): XmlDocument
    {
        if (!isset($this->xmlDocument)) {
            $xmlDocumentData = [
                'DTE' => [
                    '@attributes' => [
                        'version' => '1.0',
                        'xmlns' => 'http://www.sii.cl/SiiDte',
                    ],
                    $this->getTipo()->getTagXML() => array_merge([
                        '@attributes' => [
                            'ID' => $this->getId(),
                        ],
                    ], $this->getData()),
                ],
            ];
            $this->xmlDocument = XmlConverter::arrayToXml($xmlDocumentData);
        }

        return $this->xmlDocument;
    }

    /**
     * Realiza el timbrado del documento.
     *
     * @param Caf $caf Instancia del CAF con el que se desea timbrar.
     * @param string $timestamp Marca de tiempo a utilizar en el timbre.
     * @throws CafException Si existe algún problema al timbrar el documento.
     */
    public function timbrar(Caf $caf, ?string $timestamp = null): void
    {
        // Verificar que el folio del documento esté dentro del rango del CAF.
        if (!$caf->enRango($this->getFolio())) {
            throw new CafException(sprintf(
                'El folio %d del documento %s no está disponible en el rango del CAF %s.',
                $this->getFolio(),
                $this->getID(),
                $caf->getID()
            ));
        }

        // Asignar marca de tiempo si no se pasó una.
        if ($timestamp === null) {
            $timestamp = date('Y-m-d\TH:i:s');
        }

        // Corroborar que el CAF esté vigente según el timestamp usado.
        if (!$caf->vigente($timestamp)) {
            throw new CafException(sprintf(
                'El CAF %s que contiene el folio %d del documento %s no está vigente, venció el día %s.',
                $caf->getID(),
                $this->getFolio(),
                $this->getID(),
                (new DateTime($caf->getFechaVencimiento()))->format('d/m/Y'),
            ));
        }

        // Preparar datos del timbre.
        $tedData = [
            'TED' => [
                '@attributes' => [
                    'version' => '1.0',
                ],
                'DD' => [
                    'RE' => $this->getEmisor()->getRut(),
                    'TD' => $this->getTipo()->getCodigo(),
                    'F' => $this->getFolio(),
                    'FE' => $this->getFechaEmision(),
                    'RR' => $this->getReceptor()->getRut(),
                    'RSR' => $this->getReceptor()->getRazonSocial(),
                    'MNT' => $this->getMontoTotal(),
                    'IT1' => $this->getDetalle(0)['NmbItem'] ?? '',
                    'CAF' => $caf->getAutorizacion(),
                    'TSTED' => $timestamp,
                ],
                'FRMT' => [
                    '@attributes' => [
                        'algoritmo' => 'SHA1withRSA',
                    ],
                    '@value' => '', // Se agregará luego.
                ],
            ],
        ];

        // Armar XML del timbre y obtener los datos a timbrar (tag DD: datos
        // del documento).
        $tedXmlDocument = XmlConverter::arrayToXml($tedData);
        $ddToStamp = $tedXmlDocument->C14NWithIsoEncodingFlattened('/TED/DD');

        // Timbrar los "datos a timbrar" $ddToStamp.
        $timbre = $caf->timbrar($ddToStamp);
        $tedData['TED']['FRMT']['@value'] = $timbre;

        // Actualizar los datos del documento incorporando el timbre calculado.
        $newData = array_merge($this->getData(), $tedData);
        $this->setData($newData);
    }

    /**
     * Realiza la firma del documento.
     *
     * @param Certificate $certificate Instancia que representa la firma
     * electrónica.
     * @param string $timestamp Marca de tiempo a utilizar en la firma.
     * @return string String con el XML firmado.
     * @throws SignatureException Si existe algún problema al firmar el documento.
     */
    public function firmar(Certificate $certificate, ?string $timestamp = null): string
    {
        // Asignar marca de tiempo si no se pasó una.
        if ($timestamp === null) {
            $timestamp = date('Y-m-d\TH:i:s');
        }

        // Corroborar que el certificado esté vigente según el timestamp usado.
        if (!$certificate->isActive($timestamp)) {
            throw new SignatureException(sprintf(
                'El certificado digital de %s no está vigente en el tiempo %s, su rango de vigencia es del %s al %s.',
                $certificate->getID(),
                (new DateTime($timestamp))->format('d/m/Y H:i'),
                (new DateTime($certificate->getFrom()))->format('d/m/Y H:i'),
                (new DateTime($certificate->getTo()))->format('d/m/Y H:i'),
            ));
        }

        // Agregar timestamp.
        $newData = array_merge($this->getData(), ['TmstFirma' => $timestamp]);
        $this->setData($newData);

        // Firmar el tag que contiene el documento y retornar el XML firmado.
        $xmlSigned = SignatureGenerator::signXml(
            $this->getXmlDocument(),
            $certificate,
            $this->getId()
        );

        // Cargar XML en el documento.
        $this->xmlDocument->loadXML($xmlSigned);

        // Entregar XML firmado.
        return $xmlSigned;
    }

    /**
     * Obtiene una instancia del nodo de la firma.
     *
     * @return XmlSignatureNode
     * @throws SignatureException Si el documento XML no está firmado.
     */
    public function getXmlSignatureNode(): XmlSignatureNode
    {
        $tag = $this->getXmlDocument()->documentElement->tagName;
        $xpath = '/*[local-name()="' . $tag . '"]/*[local-name()="Signature"]';
        $signatureElement = XmlUtils::xpath($this->getXmlDocument(), $xpath)->item(0);
        if ($signatureElement === null) {
            throw new SignatureException('El documento XML del DTE no se encuentra firmado (no se encontró el nodo "Signature").');
        }

        $xmlSignatureNode = new XmlSignatureNode();
        $xmlSignatureNode->loadXML($signatureElement->C14N());

        return $xmlSignatureNode;
    }

    /**
     * Valida la firma electrónica del documento XML del DTE.
     *
     * @return void
     * @throws SignatureException Si la validación del esquema falla.
     */
    public function validateSignature()
    {
        $xmlSignatureNode = $this->getXmlSignatureNode();
        $xmlSignatureNode->validate($this->getXmlDocument());
    }

    /**
     * Valida el esquema del XML del DTE.
     *
     * @return void
     * @throws XmlException Si la validación del esquema falla.
     */
    public function validateSchema(): void
    {
        // Las boletas no se validan de manera individual (el DTE). Se validan
        // a través del EnvioBOLETA.
        if ($this->getTipo()->esBoleta()) {
            return;
        }

        // Validar esquema de otros DTE (no boletas).
        $schema = 'DTE_v10.xsd';
        $schemaPath = PathManager::getSchemasPath($schema);
        XmlValidator::validateSchema($this->getXmlDocument(), $schemaPath);
    }
}

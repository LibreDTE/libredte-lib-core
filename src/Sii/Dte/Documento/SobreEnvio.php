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

use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\SignatureException;
use libredte\lib\Core\Signature\SignatureGenerator;
use libredte\lib\Core\Signature\XmlSignatureNode;
use libredte\lib\Core\Sii\Dte\Documento\Builder\DocumentoFactory;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlException;
use libredte\lib\Core\Xml\XmlUtils;
use libredte\lib\Core\Xml\XmlValidator;

/**
 * Clase que representa un sobre para el envío de documentos al SII.
 *
 * Este sobre permite enviar facturas (EnvioDTE) y boletas (EnvioBOLETA).
 */
class SobreEnvio
{
    /**
     * Constante que representa que el envío es de DTE.
     *
     * Este sobre se usa para todo menos boletas.
     */
    private const SOBRE_DTE = 0;

    /**
     * Constante que representa que el envío es de boletas.
     */
    private const SOBRE_BOLETA = 1;

    /**
     * Configuración (reglas) para el documento XML del envío.
     */
    private const CONFIG = [
        self::SOBRE_DTE => [
            // Máxima cantidad de tipos de documentos en el envío.
            'SubTotDTE_max' => 20,
            // Máxima cantidad de documentos en un envío.
            'DTE_max' => 2000,
            // Tag XML para el envío.
            'tag' => 'EnvioDTE',
            // Schema principal del XML del envío.
            'schema' => 'EnvioDTE_v10',
        ],
        self::SOBRE_BOLETA => [
            // Máxima cantidad de tipos de documentos en el envío.
            'SubTotDTE_max' => 2,
            // Máxima cantidad de documentos en un envío.
            'DTE_max' => 1000,
            // Tag XML para el envío.
            'tag' => 'EnvioBOLETA',
            // Schema principal del XML del envío.
            'schema' => 'EnvioBOLETA_v11',
        ],
    ];

    /**
     * Instancia del documento XML asociado al sobre.
     *
     * @var XmlDocument
     */
    protected XmlDocument $xmlDocument;

    /**
     * Tipo de sobre que se está generando.
     *
     * Posibles valores:
     *
     *   - SobreEnvio::SOBRE_DTE
     *   - SobreEnvio::SOBRE_BOLETA
     *
     * @var int
     */
    private int $tipo;

    /**
     * Datos de la carátula del envío
     *
     * @var array
     */
    private array $caratula;

    /**
     * Arreglo con las instancias de documentos que se enviarán.
     *
     * @var array<int, AbstractDocumento>
     */
    private array $documentos;

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Constructor del sobre del envío de DTE al SII.
     *
     * @param DataProviderInterface|null $dataProvider
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Permite crear el documento XML del sobre a partir de un string XML.
     *
     * @param string $xml
     * @return void
     */
    public function loadXML(string $xml)
    {
        $this->xmlDocument = new XmlDocument();
        $this->xmlDocument->loadXML($xml);
    }

    /**
     * Obtiene el string XML del sobre con el formato de XmlDocument::getXML().
     *
     * @return string
     */
    public function getXml(): string
    {
        return $this->getXmlDocument()->getXML();
    }

    /**
     * Obtiene el string XML del sobre en el formato de XmlDocument::saveXML().
     *
     * @return string
     */
    public function saveXml(): string
    {
        return $this->getXmlDocument()->saveXML();
    }

    /**
     * Realiza la firma del sobre del envío.
     *
     * @param Certificate $certificate Instancia que representa la firma
     * electrónica.
     * @return string String con el XML firmado.
     * @throws SignatureException Si existe algún problema al firmar el sobre.
     */
    public function firmar(Certificate $certificate): string
    {
        $this->getXmlDocument();

        $xmlSigned = SignatureGenerator::signXml(
            $this->xmlDocument,
            $certificate,
            'LibreDTE_SetDoc'
        );

        $this->xmlDocument->loadXML($xmlSigned);

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
            throw new SignatureException('El sobre del envío del XML no se encuentra firmado (no se encontró el nodo "Signature").');
        }

        $xmlSignatureNode = new XmlSignatureNode();
        $xmlSignatureNode->loadXML($signatureElement->C14N());

        return $xmlSignatureNode;
    }

    /**
     * Valida la firma electrónica del documento XML del sobre.
     *
     * @return void
     * @throws SignatureException Si la validación de la firma falla.
     */
    public function validateSignature()
    {
        $xmlSignatureNode = $this->getXmlSignatureNode();
        $xmlSignatureNode->validate($this->getXmlDocument());
    }

    /**
     * Valida el esquema del XML del sobre del envío.
     *
     * Este método valida tanto los esquemas de EnvioDTE como el EnvioBOLETA.
     *
     * @return void
     * @throws XmlException Si la validación del esquema falla.
     */
    public function validateSchema(): void
    {
        XmlValidator::validateSchema($this->getXmlDocument());
    }

    /**
     * Agrega un documento al listado que se enviará al SII.
     *
     * @param AbstractDocumento $documento Instancia del documento que se desea
     * agregar al listado del envío.
     */
    public function agregar(AbstractDocumento $documento): void
    {
        // Si ya se generó la carátula no se permite agregar nuevos documentos.
        if (isset($this->caratula)) {
            throw new DocumentoException(
                'No es posible agregar documentos cuando la carátula ya fue generada.'
            );
        }

        // Determinar el tipo del envío (DTE o BOLETA).
        $esBoleta = $documento->getTipo()->esBoleta();
        if (!isset($this->tipo)) {
            $this->tipo = $esBoleta
                ? self::SOBRE_BOLETA
                : self::SOBRE_DTE
            ;
        }

        // Validar que el tipo de documento sea del tipo que se espera.
        elseif ($esBoleta !== (bool) $this->tipo) {
            throw new DocumentoException(
                'No es posible mezclar DTE con BOLETA en el envío al SII.'
            );
        }

        // Si no está definido como arreglo se crea el arreglo de documentos.
        if (!isset($this->documentos)) {
            $this->documentos = [];
        }

        // Validar que no se haya llenado la lista.
        if (isset($this->documentos[self::CONFIG[$this->tipo]['DTE_max'] - 1])) {
            throw new DocumentoException(sprintf(
                'No es posible agregar nuevos documentos al envío al SII, límite de %d.',
                self::CONFIG[$this->tipo]['DTE_max']
            ));
        }

        // Agregar documento al listado.
        $this->documentos[] = $documento;
    }

    /**
     * Asignar la carátula del sobre del envío.
     *
     * @param array $caratula Arreglo con datos: RutEnvia, FchResol y NroResol.
     * @return array Arreglo con la carátula normalizada.
     */
    public function setCaratula(array $caratula): array
    {
        // Si no hay documentos para enviar error.
        if (!isset($this->documentos[0])) {
            throw new DocumentoException(
                'No existen documentos en el sobre para poder generar la carátula.'
            );
        }

        // Si se agregaron más tipos de documentos que los permitidos error.
        $SubTotDTE = $this->getSubTotDTE();
        if (isset($SubTotDTE[self::CONFIG[$this->tipo]['SubTotDTE_max']])) {
            throw new DocumentoException(
                'Se agregaron más tipos de documento de los que son permitidos (%d).',
                self::CONFIG[$this->tipo]['SubTotDTE_max']
            );
        }

        // Generar carátula.
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0',
            ],
            'RutEmisor' => $this->documentos[0]->getEmisor()->getRut(),
            'RutEnvia' => false,
            'RutReceptor' => $this->documentos[0]->getReceptor()->getRut(),
            'FchResol' => '',
            'NroResol' => '',
            'TmstFirmaEnv' => date('Y-m-d\TH:i:s'),
            'SubTotDTE' => $SubTotDTE,
        ], $caratula);

        // Retornar la misma carátula pero normalizada.
        return $this->caratula;
    }

    /**
     * Obtiene los datos para generar los tags SubTotDTE.
     *
     * @return array Arreglo con los datos para generar los tags SubTotDTE.
     */
    private function getSubTotDTE(): array
    {
        $SubTotDTE = [];
        $subtotales = [];

        foreach ($this->documentos as $documento) {
            if (!isset($subtotales[$documento->getTipo()->getCodigo()])) {
                $subtotales[$documento->getTipo()->getCodigo()] = 0;
            }
            $subtotales[$documento->getTipo()->getCodigo()]++;
        }

        foreach ($subtotales as $tipo => $subtotal) {
            $SubTotDTE[] = [
                'TpoDTE' => $tipo,
                'NroDTE' => $subtotal,
            ];
        }

        return $SubTotDTE;
    }

    /**
     * Genera el documento XML.
     *
     * @return XmlDocument
     */
    public function getXmlDocument(): XmlDocument
    {
        if (!isset($this->xmlDocument)) {
            // Generar estructura base del XML del sobre (envío).
            $xmlDocumentData = [
                self::CONFIG[$this->tipo]['tag'] => [
                    '@attributes' => [
                        'xmlns' => 'http://www.sii.cl/SiiDte',
                        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                        'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte '
                            . self::CONFIG[$this->tipo]['schema'] . '.xsd'
                        ,
                        'version' => '1.0',
                    ],
                    'SetDTE' => [
                        '@attributes' => [
                            'ID' => 'LibreDTE_SetDoc',
                        ],
                        'Caratula' => $this->caratula,
                        'DTE' => '',
                    ],
                ],
            ];
            $this->xmlDocument = XmlConverter::arrayToXml($xmlDocumentData);

            // Generar XML de los documentos que se deberán incorporar.
            $documentos = [];
            foreach ($this->documentos as $doc) {
                $documentos[] = trim(str_replace(
                    [
                        '<?xml version="1.0" encoding="ISO-8859-1"?>',
                        '<?xml version="1.0"?>',
                    ],
                    '',
                    $doc->getXML()
                ));
            }

            // Agregar los DTE dentro de SetDTE reemplazando el tag vacio DTE.
            $xmlEnvio = $this->xmlDocument->saveXML();
            $xml = str_replace('<DTE/>', implode("\n", $documentos), $xmlEnvio);

            // Reemplazar el documento XML del sobre del envío.
            $this->xmlDocument->loadXML($xml);
        }

        // Entregar el documento XML.
        return $this->xmlDocument;
    }

    /**
     * Entrega el listado de documentos incluídos en el sobre.
     *
     * @return array<AbstractDocumento>
     */
    public function getDocumentos(): array
    {
        if (!isset($this->documentos)) {
            $factory = new DocumentoFactory($this->dataProvider);
            $documentosNodeList = $this
                ->getXmlDocument()
                ->getElementsByTagName('DTE')
            ;
            foreach ($documentosNodeList as $documentoNode) {
                $xml = $documentoNode->C14N();
                $documento = $factory->loadFromXml($xml);
                $this->agregar($documento);
            }
        }

        return $this->documentos;
    }
}

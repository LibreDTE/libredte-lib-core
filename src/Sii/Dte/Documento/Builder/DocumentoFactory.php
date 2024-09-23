<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

namespace libredte\lib\Core\Sii\Dte\Documento\Builder;

use Symfony\Component\Yaml\Yaml;
use libredte\lib\Core\Xml\XmlDocument;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoException;

/**
 * Fábrica de documentos tributarios electrónicos.
 *
 * Permite crear un documento tributario electrónico a partir de los datos de
 * un arreglo o de un string XML.
 *
 * La principal ventaja de usar esta fábrica es que abstrae todo lo que se debe
 * hacer para buscar el "builder" del documento tributario y crear un documento
 * fácilmente a partir de sus datos. Además se preocupa de instanciar solo una
 * vez cada "builder".
 */
class DocumentoFactory
{
    /**
     * Constructores ("builders") de documentos que están inicializados.
     *
     * Esta es la "caché" para evitar instanciar más de una vez un "builder".
     *
     * @var array
     */
    private array $builders = [];

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase.
     *
     * @param ?DataProviderInterface $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Construye un documento tributario electrónico a partir de los datos en
     * un arreglo.
     *
     * @param array $data Arreglo con los datos del documento.
     * @return AbstractDocumento Documento tributario construído.
     */
    public function createFromArray(array $data): AbstractDocumento
    {
        // Crear builder para el documento que se creará.
        $builderClass = $this->getDocumentoBuilderClass($data);
        $builder = $this->getDocumentoBuilder($builderClass);

        // Construir y retornar el documento tributario solicitado.
        return $builder->build($data);
    }

    /**
     * Construye un documento tributario electrónico a partir de los datos en
     * un string XML.
     *
     * @param string $data String XML con los datos del documento.
     * @return AbstractDocumento Documento tributario construído.
     */
    public function createFromXml(string $data): AbstractDocumento
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXML($data);
        $array = XmlConverter::xmlToArray($xmlDocument);

        // Obtener los datos del documento a generar.
        $documentoData = $array['DTE']['Documento']
            ?? $array['DTE']['Exportaciones']
            ?? $array['DTE']['Liquidacion']
            ?? null
        ;

        // Si el XML no tiene los tags válidos se lanza una excepción.
        if ($documentoData === null) {
            throw new DocumentoException(
                'El nodo raíz del XML del documento debe ser el tag "DTE". Dentro de este nodo raíz debe existir un tag "Documento", "Exportaciones" o "Liquidacion". Este segundo nodo es el que debe contener los datos del documento.'
            );
        }

        // Quitar los atributos que tenga el tag encontrado.
        unset($documentoData['@attributes']);

        // Crear el documento a partir de los datos encontrados.
        return $this->createFromArray($documentoData);
    }

    /**
     * Construye un documento tributario electrónico a partir de los datos en
     * un string YAML.
     *
     * @param string $data String YAML con los datos del documento.
     * @return AbstractDocumento Documento tributario construído.
     */
    public function createFromYaml(string $data): AbstractDocumento
    {
        $array = Yaml::parse($data);

        return $this->createFromArray($array);
    }

    /**
     * Construye un documento tributario electrónico a partir de los datos en
     * un string JSON.
     *
     * @param string $data String JSON con los datos del documento.
     * @return AbstractDocumento Documento tributario construído.
     */
    public function createFromJson(string $data): AbstractDocumento
    {
        $array = json_decode($data, true);

        return $this->createFromArray($array);
    }

    /**
     * Carga un documento tributario electrónico a partir de los datos en
     * un string XML.
     *
     * NOTE: Este método de creación de un documento espera que el XML contenga
     * todos los nodos y datos necesarios del documento (ej: incluyendo firma).
     * Se debe utilizar solamente para construir los documentos que vienen de
     * un XML que ya está listo el DTE. Si se desea crear un documento a partir
     * de datos que están en un string XML, pero que no están normalizados,
     * timbrados o firmados, se debe utilizar createFromXml().
     *
     * @param string $xml String XML con los datos del documento.
     * @return AbstractDocumento Documento tributario construído.
     */
    public function loadFromXml(string $xml): AbstractDocumento
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXML($xml);
        $array = XmlConverter::xmlToArray($xmlDocument);

        // Obtener los datos del documento a generar.
        $data = $array['DTE']['Documento']
            ?? $array['DTE']['Exportaciones']
            ?? $array['DTE']['Liquidacion']
            ?? null
        ;

        // Crear builder para el documento que se creará.
        $builderClass = $this->getDocumentoBuilderClass($data);
        $builder = $this->getDocumentoBuilder($builderClass);

        // Construir una instancia del documento con los datos del XML y
        // retornarla.
        return $builder->loadFromXml($xml);
    }

    /**
     * Entrega la instancia del "builder" según la clase que se ha indicado.
     *
     * Este método utiliza una "caché" para entregar siempre el mismo "builder"
     * para el mismo tipo de documento.
     *
     * @param string $class Clase del "builder" que se desea su instancia.
     * @return AbstractDocumentoBuilder Instancia del "builder" solicitado.
     */
    private function getDocumentoBuilder(string $class): AbstractDocumentoBuilder
    {
        if (!isset($this->builders[$class])) {
            $this->builders[$class] = new $class(
                $this->dataProvider
            );
        }

        return $this->builders[$class];
    }

    /**
     * Determina qué "builder" se debe utilizar según el código del documento
     * que viene en los datos del documento que se debe crear.
     *
     * @param array $data Arreglo con los datos del documento.
     * @return string Clase del "builder" a usar para crear el documento.
     */
    private function getDocumentoBuilderClass(array $data): string
    {
        // Obtener el código del tipo de documento que se debe generar.
        $TipoDTE = $data['Encabezado']['IdDoc']['TipoDTE'] ?? null;
        if ($TipoDTE === null) {
            throw new DocumentoException(sprintf(
                'No se encontró el campo %s en el documento, el cual es obligatorio.',
                'TipoDTE'
            ));
        }

        // Determinar clase del "builder" en base al tipo de documento.
        switch ((int) $TipoDTE) {
            case 33:
                return FacturaAfectaBuilder::class;
            case 34:
                return FacturaExentaBuilder::class;
            case 39:
                return BoletaAfectaBuilder::class;
            case 41:
                return BoletaExentaBuilder::class;
            case 43:
                return LiquidacionFacturaBuilder::class;
            case 46:
                return FacturaCompraBuilder::class;
            case 52:
                return GuiaDespachoBuilder::class;
            case 56:
                return NotaDebitoBuilder::class;
            case 61:
                return NotaCreditoBuilder::class;
            case 110:
                return FacturaExportacionBuilder::class;
            case 111:
                return NotaDebitoExportacionBuilder::class;
            case 112:
                return NotaCreditoExportacionBuilder::class;
            default:
                throw new DocumentoException(sprintf(
                    'El valor "%s" del campo %s del documento es inválido.',
                    $TipoDTE,
                    'TipoDTE'
                ));
        }
    }
}

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

namespace libredte\lib\Core\Sii\Dte\Documento\Builder;

use libredte\lib\Core\Repository\ImpuestosAdicionalesRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoNormalizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoSanitizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\UtilsTrait;

/**
 * Clase abstracta (base) para los constructores ("builders") de documentos.
 */
abstract class AbstractDocumentoBuilder
{
    // Traits que usa esta clase.
    use UtilsTrait;

    /**
     * Clase del documento que este "builder" construirá.
     *
     * @var string
     */
    protected string $documentoClass;

    /**
     * Instancia vacía del documento.
     *
     * Se crea en el constructor y se deja vacía en el "builder". Luego, en
     * cada llamada a build() esta instancia se clona para construir un nuevo
     * documento.
     */
    private AbstractDocumento $documentoBaseForNewBuilds;

    /**
     * Tipo de documento que normalizará esta instancia del normalizador.
     *
     * @var DocumentoTipo
     */
    private DocumentoTipo $tipoDocumento;

    /**
     * Normalizador de datos del documento a construir.
     *
     * @var DocumentoNormalizer
     */
    private DocumentoNormalizer $normalizer;

    /**
     * Sanitizador de datos del documento a construir.
     *
     * @var DocumentoSanitizer
     */
    private DocumentoSanitizer $sanitizer;

    /**
     * Repositorio de impuestos adicionales.
     *
     * @var ImpuestosAdicionalesRepository
     */
    private ImpuestosAdicionalesRepository $impuestosAdicionalesRepository;

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

        // Crear el documento tributario vacío.
        $class = $this->documentoClass;
        $this->documentoBaseForNewBuilds = new $class(
            $this->dataProvider
        );

        // Obtener el tipo de documento tributario.
        $this->tipoDocumento = $this->documentoBaseForNewBuilds->getTipo();

        // Crear instancias del normalizador y sanitizador.
        $this->normalizer = new DocumentoNormalizer(
            $this->tipoDocumento,
            [$this, 'applyDocumentoNormalization']
        );
        $this->sanitizer = new DocumentoSanitizer();
    }

    /**
     * Normaliza los datos con reglas específicas para el tipo de documento.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    abstract public function applyDocumentoNormalization(array $data): array;

    /**
     * Construye el documentro tributario electrónico a partir de un arreglo
     * con los datos del documento.
     *
     * @param array $data Arreglo con los datos del documento a crear.
     * @param bool $normalize Si se deben normalizar los datos del arreglo.
     * @return AbstractDocumento Documento tributario construído.
     */
    public function build(array $data, bool $normalize = true): AbstractDocumento
    {
        // Normalizar los datos si así se requiere.
        if ($normalize) {
            $data = $this->normalizer->normalize($data);
            $data = $this->sanitizer->sanitize($data);
        }

        // Crear el documento tributario y asignar sus datos.
        $documento = clone $this->documentoBaseForNewBuilds;
        $documento->setData($data);

        // Entregar la instancia del documento tributario creada.
        return $documento;
    }

    /**
     * Construye el documento tributario electrónico a partir de los datos de
     * un string XML.
     *
     * Este método es para cargar XML de documentos completos. O sea, con todos
     * sus datos (normalizados), el documento timbrado (con su CAF asociado) y
     * con su firma electrónica.
     *
     * @param string $xml String del XML del documento con todos sus datos.
     * @return AbstractDocumento Instancia del documento construído con el XML.
     */
    public function loadFromXml(string $xml): AbstractDocumento
    {
        // Crear el documento tributario y asignar sus datos.
        $documento = clone $this->documentoBaseForNewBuilds;
        $documento->loadXML($xml);

        // Entregar la instancia del documento tributario creada.
        return $documento;
    }

    /**
     * Entrega el tipo de documento que este "builder" puede construir.
     *
     * @return DocumentoTipo
     */
    protected function getTipoDocumento(): DocumentoTipo
    {
        return $this->tipoDocumento;
    }

    /**
     * Entrega el repositorio de impuestos adicionales que se pueden usar en un
     * documento tributario.
     *
     * @return ImpuestosAdicionalesRepository
     */
    protected function getImpuestosAdicionalesRepository(): ImpuestosAdicionalesRepository
    {
        if (!isset($this->impuestosAdicionalesRepository)) {
            $this->impuestosAdicionalesRepository =
                new ImpuestosAdicionalesRepository($this->dataProvider)
            ;
        }

        return $this->impuestosAdicionalesRepository;
    }
}

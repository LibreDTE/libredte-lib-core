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

namespace libredte\lib\Core\Sii\Dte\Documento\Parser;

/**
 * Clase que maneja el análisis sintáctico de los datos de un documento.
 *
 * Se encarga de tomar los datos en cierto formato y transformarlos a un formato
 * estándarizado para ser usado en LibreDTE como un arreglo PHP con los datos en
 * la estructura oficial del SII.
 */
class DocumentoParser
{
    /**
     * Parser por defecto que se debe utilizar al analizar los datos si no se
     * ha especificado uno al llamar al método DocumentoParser::parse().
     *
     * @var string
     */
    protected string $defaultParser = 'sii.json';

    /**
     * Alias de parsers.
     *
     * Esto es por compatibilidad hacia atrás y simplificación en el uso de los
     * parsers estándares de LibreDTE que usan el formato oficial del SII.
     *
     * @var array
     */
    protected array $parsersAlias = [
        'json' => 'sii.json',
        'yaml' => 'sii.yaml',
        'xml' => 'sii.xml',
    ];

    /**
     * Listado de parsers instanciados para ser reutilizados.
     *
     * @var array<string,DocumentoParserInterface>
     */
    protected array $parsers = [];

    /**
     * Ejecuta el análisis sintáctico (parseo) de los datos.
     *
     * @param string|array $data Datos del documento a transformar.
     * @return array Arreglo con los datos transformados.
     * @throws DocumentoParserException Si existe un error al parsear los datos.
     */
    public function parse(string|array $data, string $parser = null): array
    {
        // Si no se indicó parser se usa el por defecto.
        if ($parser === null) {
            $parser = $this->defaultParser;
        }

        // Si el parser es un alias se ajusta el nombre del parser al real.
        if (isset($this->parsersAlias[$parser])) {
            $parser = $this->parsersAlias[$parser];
        }

        // Obtener la instancia del parser real.
        $parser = $this->getParserInstance($parser);

        // Llamar al parser para transformar los datos.
        $parsedData = $parser->parse($data);

        // Entregar los datos transformados.
        return $parsedData;
    }

    /**
     * Obtiene la instancia de un parser.
     *
     * @param string $parser Nombre del parser que se desea obtener.
     * @return DocumentoParserInterface Instancia del parser.
     */
    private function getParserInstance(string $parser): DocumentoParserInterface
    {
        // Si el parser no está previamente cargado se carga.
        if (!isset($this->parsers[$parser])) {
            // Determinar la clase del parser solicitado.
            $class = $this->getParserClass($parser);

            // Si la clase del parser no existe error.
            if (!class_exists($class)) {
                throw new DocumentoParserException(sprintf(
                    'El analizador sintáctico (parser) con el formato %s no existe.',
                    $parser
                ));
            }

            // Instanciar el parser.
            $this->parsers[$parser] = new $class;
        }

        // Entregar la instancia del parser.
        return $this->parsers[$parser];
    }

    /**
     * Determina la clase del parser que se está solicitando.
     *
     * @param string $parser Nombre del parser solicitado.
     * @return string FQCN de la clase del parser solicitado.
     */
    private function getParserClass(string $parser): string
    {
        // Determinar nombre del archivo PHP y de la clase.
        $parts = array_map(function ($part) {
            return ucfirst(strtolower($part));
        }, explode('.', $parser));
        $file = __DIR__ . '/' . implode('/', $parts) . 'Parser.php';
        $class = implode('\\', $parts) . 'Parser';

        // La clase existe en el namespace actual.
        if (file_exists($file)) {
            $class = __NAMESPACE__ . '\\' . $class;
        }
        // La clase podría existir en lib-pro.
        else {
            $class = str_replace('\\Core\\', '\\Pro\\', __NAMESPACE__)
                . '\\' . $class
            ;
        }

        // Entregar el FQCN de la clase del parser buscado.
        return $class;
    }
}

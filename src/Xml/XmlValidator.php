<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

namespace libredte\lib\Core\Xml;

use libredte\lib\Core\Service\PathManager;

/**
 * Clase para la validación de XML y manejo de errores.
 */
class XmlValidator
{
    /**
     * Traducciones, y transformaciones, por defecto de los errores de libxml.
     *
     * El objetivo es simplificar los mensajes "técnicos" de libxml y dejarlos
     * más sencillos para que un humano "no técnico" los pueda entender más
     * fácilmente.
     *
     * @var array
     */
    private static $defaultLibxmlTranslations = [
        '\': '
            => '\' (línea %(line)s): ',
        ': [facet \'pattern\'] The value'
            => ': tiene el valor',
        ': This element is not expected. Expected is one of'
            => ': no era el esperado, el campo esperado era alguno de los siguientes',
        ': This element is not expected. Expected is'
            => ': no era el esperado, el campo esperado era',
        'is not accepted by the pattern'
            => 'el que no es válido según la expresión regular (patrón)',
        'is not a valid value of the local atomic type'
            => 'no es un valor válido para el tipo de dato del campo',
        'is not a valid value of the atomic type'
            => 'no es un valor válido, se requiere un valor de tipo',
        ': [facet \'maxLength\'] The value has a length of '
            => ': el valor del campo tiene un largo de ',
        '; this exceeds the allowed maximum length of '
            => ' caracteres excediendo el largo máximo permitido de ',
        ': [facet \'enumeration\'] The value '
            => ': el valor ',
        'is not an element of the set'
            => 'no es válido, debe ser alguno de los valores siguientes',
        '[facet \'minLength\'] The value has a length of'
            => 'el valor del campo tiene un largo de ',
        '; this underruns the allowed minimum length of'
            => ' y el largo mínimo requerido es',
        'Missing child element(s). Expected is'
            => 'debe tener en su interior, nivel inferior, el campo',
        'Character content other than whitespace is not allowed because the content type is \'element-only\''
            => 'el valor del campo es inválido',
        'Element'
            => 'Campo',
        ' ( '
            => ' \'',
        ' ).'
            => '\'.',
        'No matching global declaration available for the validation root'
            => 'El nodo raíz del XML no coincide con lo esperado en la definición del esquema',
    ];

    /**
     * Realiza la validación de esquema de un documento XML.
     *
     * @param XmlDocument $xmlDocument Documento XML que se desea validar.
     * @param string|null $schemaPath Ruta hacia el archivo XSD del esquema
     * XML contra el que se validará. Si no se indica, se obtiene desde el
     * documento XML si está definido en "xsi:schemaLocation".
     * @param array $translations Traducciones adicionales para aplicar.
     * @throws XmlException Si el XML no es válido según su esquema.
     */
    public static function validateSchema(
        XmlDocument $xmlDocument,
        ?string $schemaPath = null,
        array $translations = []
    ): void
    {
        // Determinar $schemaPath si no fue pasado.
        if ($schemaPath === null) {
            $schemaPath = self::getSchemaPath($xmlDocument);
        }

        // Obtener estado actual de libxml y cambiarlo antes de validar para
        // poder obtenerlos a una variable si hay errores al validar.
        $useInternalErrors = libxml_use_internal_errors(true);

        // Validar el documento XML.
        $isValid = $xmlDocument->schemaValidate($schemaPath);

        // Obtener errores, limpiarlos y restaurar estado de errores de libxml.
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        // Si el XML no es válido lanzar excepción con los errores traducidos.
        if (!$isValid) {
            $errors = !empty($errors)
                ? self::translateLibxmlErrors($errors, array_merge($translations, [
                    '{' . $xmlDocument->getNamespace() . '}' => '',
                ]))
                : []
            ;
            throw new XmlException(
                'La validación del XML falló.',
                $errors
            );
        }
    }

    /**
     * Traduce los errores de libxml a mensajes más sencillos para humanos.
     *
     * @param array $errors Arreglo con los errores originales de libxml.
     * @param array $translations Traducciones adicionales para aplicar.
     * @return array Arreglo con los errores traducidos.
     */
    private static function translateLibxmlErrors(
        array $errors,
        array $translations = []
    ): array
    {
        // Definir reglas de traducción.
        $replace = array_merge(self::$defaultLibxmlTranslations, $translations);

        // Traducir los errores.
        $translatedErrors = [];
        foreach ($errors as $error) {
            $translatedErrors[] = str_replace(
                ['%(line)s'],
                [(string) $error->line],
                str_replace(
                    array_keys($replace),
                    array_values($replace),
                    trim($error->message)
                )
            );
        }

        // Entregar errores traducidos.
        return $translatedErrors;
    }

    /**
     * Busca la ruta del esquema XML para validar el documento XML.
     *
     * @param XmlDocument $xmlDocument Documento XML para el cual se busca su
     * esquema XML.
     * @return string Ruta hacia el archivo XSD con el esquema del XML.
     * @throws XmlException Si el esquema del XML no se encuentra.
     */
    private static function getSchemaPath(XmlDocument $xmlDocument): string
    {
        // Determinar el nombre del archivo del esquema del XML.
        $schema = $xmlDocument->getSchema();
        if ($schema === null) {
            throw new XmlException(
                'El XML no contiene una ubicación de esquema válida en el atributo "xsi:schemaLocation".'
            );
        }

        // Armar la ruta al archivo del esquema y corroborar que exista.
        $schemaPath = PathManager::getSchemasPath($schema);
        if ($schemaPath === null) {
            throw new XmlException(sprintf(
                'No se encontró el archivo de esquema XML %s.',
                $schema
            ));
        }

        // Entregar la ruta al esquema (existe y se puede leer el archivo).
        return $schemaPath;
    }
}

<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sasco\LibreDTE;

/**
 * Clase para manejar la internacionalización
 *
 * Clase basada en \sowerphp\core\I18n disponible en:
 * <https://github.com/SowerPHP/sowerphp/blob/master/lib/sowerphp/core/I18n.php>
 *
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-17
 */
class I18n
{

    private static $idioma = 'es'; ///< Idioma por defecto de los mensajes
    private static $locales = [ ///< Mapeo de idioma a locales
        'es' => 'es_CL.utf8',
        'en' => 'en_US.utf8',
    ];

    /**
     * Método para cambiar el idioma que se usará en las traducciones
     * @param idioma Código de 2 caracteres del idioma
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public static function setIdioma($idioma = 'es')
    {
        self::$idioma = $idioma;
    }

    /**
     * Método que realiza la traducción de un string a otro idioma.
     *
     * Plantilla para archivo master.po (para locale en_US.utf8):
     *
     *	msgid ""
     *	msgstr ""
     *	"Project-Id-Version: proyecto en_US master\n"
     *	"PO-Revision-Date: 2014-03-02 11:37-0300\n"
     *	"Last-Translator: Nombre del traductor <traductor@example.com>\n"
     *	"Language-Team: English\n"
     *	"Language: en_US\n"
     *	"MIME-Version: 1.0\n"
     *	"Content-Type: text/plain; charset=UTF-8\n"
     *	"Content-Transfer-Encoding: 8bit\n"
     *	"Plural-Forms: nplurals=2; plural=(n != 1);\n"
     *
     *	msgid "Buscar"
     *	msgstr "Search"
     *
     * Guardar la plantilla en locale/en_US.utf8/LC_MESSAGES/master.po
     * Luego ejecutar:
     *   $ msgfmt master.po -o master.mo
     *
     * En caso que se esté creando desde un archivo pot se debe crear el archivo po con:
     *   $ msginit --locale=en_US.utf8 --input=master.pot
     * Lo anterior creará el archivo en_US.po y luego se usa msgfmt con este archivo
     *
     * La locale que se esté utilizando debe existir en el sistema, verificar con:
     *   $ locale -a
     * En caso que no exista editar /etc/locale.gen para agregarla y luego ejecutar:
     *   # locale-gen
     *
     * Cuando se crean o modifican los directorios en locale se debe reiniciar
     * el servicio Apache (¿?)
     *
     * @param string Texto que se desea traducir
     * @param domain Dominio que se desea utilizar para la traducción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-17
     */
    public static function translate($string, $domain = 'master')
    {
        if (!isset(self::$locales[self::$idioma]) or !function_exists('gettext')) {
            return $string;
        }
        $locale = self::$locales[self::$idioma];
        putenv("LANG=".$locale);
        setlocale(LC_MESSAGES, $locale);
        bindtextdomain($domain, dirname(dirname(__FILE__)).'/locale');
        textdomain($domain);
        bind_textdomain_codeset($domain, 'UTF-8');
        return gettext($string);
    }

}

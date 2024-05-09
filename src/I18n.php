<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib;

/**
 * Clase para manejar la internacionalización.
 */
class I18n
{

    private static $idioma = 'es'; ///< Idioma por defecto de los mensajes
    private static $locales = [ ///< Mapeo de idioma a locales
        'es' => 'es_CL.utf8',
        'en' => 'en_US.utf8',
    ];

    /**
     * Método para cambiar el idioma que se usará en las traducciones.
     * @param idioma Código de 2 caracteres del idioma
     */
    public static function setIdioma($idioma = 'es')
    {
        self::$idioma = $idioma;
    }

    /**
     * Método que realiza la traducción de un string a otro idioma.
     * @param string Texto que se desea traducir
     * @param domain Dominio que se desea utilizar para la traducción
     */
    public static function translate($string, $domain = 'master')
    {
        if (!isset(self::$locales[self::$idioma]) or !function_exists('gettext')) {
            return $string;
        }
        $locale = self::$locales[self::$idioma];
        putenv("LANG=".$locale);
        setlocale(LC_MESSAGES, $locale);
        bindtextdomain($domain, dirname(dirname(__FILE__)) . '/locale');
        textdomain($domain);
        bind_textdomain_codeset($domain, 'UTF-8');
        return gettext($string);
    }

}

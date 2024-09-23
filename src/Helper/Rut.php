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

namespace libredte\lib\Core\Helper;

use UnexpectedValueException;

/**
 * Clase para trabajar con identificadores RUT, y RUN, de Chile.
 */
class Rut
{
    /**
     * Se define que un RUT no puede ser menos que 1.000.000.
     *
     * Si bien legalmente podrían existir RUT o RUN menores a este. En la
     * práctica (año 2024) no deberían existir RUT o RUN vigentes, en uso, que
     * sean menores a este mínimo definido acá.
     */
    private const RUT_MIN = 1000000;

    /**
     * Se define que un RUT no puede ser mayor que 99.999.999.
     *
     * Si bien legalmente podrían existir RUT o RUN mayores a este. En la
     * práctica (año 2024) no deberían existir RUT o RUN vigentes, en uso, que
     * sean mayores a este máximo definido acá.
     */
    private const RUT_MAX = 99999999;

    /**
     * Extrae de un RUT las 2 partes: el RUT en si (solo el número) y el dígito
     * verificador.
     *
     * @param string $rut RUT completo, con DV (puntos y guión opcionales).
     * @return array Un arreglo con 2 elementos: rut (int) y dv (string).
     */
    public static function toArray(string $rut): array
    {
        $rut = self::removeThousandsSeparatorAndDash($rut);
        $dv = strtoupper(substr($rut, -1));
        $rut = substr($rut, 0, -1);

        return [(int) $rut, $dv];
    }

    /**
     * Formatea un RUT al formato: 11222333-4.
     *
     * NOTE: Este método no agrega puntos al RUT, si se desea usar el RUT con
     * puntos se debe utilizar formatFull().
     *
     * @param string|int $rut
     * @return string
     */
    public static function format(string|int $rut): string
    {
        // Si es un string, se espera que venga con DV, el guión es opcional.
        if (is_string($rut)) {
            return self::formatFromString($rut);
        }

        // Si es un int, es solo la parte numérica del RUT (sin DV).
        return self::formatFromInt($rut);
    }

    /**
     * Formatea un RUT al formato: 11.222.333-4.
     *
     * @param string|integer $rut
     * @return string
     */
    public static function formatFull(string|int $rut): string
    {
        $rut = self::format($rut);
        [$rut, $dv] = self::toArray($rut);

        return self::addThousandsSeparator($rut) . '-' . $dv;
    }

    /**
     * Calcula el dígito verificador de un RUT.
     *
     * @param int $rut RUT al que se calculará el dígito verificador.
     * @return string Dígito verificador calculado para el RUT indicado.
     */
    public static function calculateDv(int $rut): string
    {
        $s = 1;
        for ($m = 0; $rut != 0; $rut /= 10) {
            $rut = (int) $rut;
            $s = ($s + $rut % 10 * (9 - $m++ % 6)) % 11;
        }
        return strtoupper(chr($s ? $s + 47 : 75));
    }

    /**
     * Valida el RUT ingresado.
     *
     * @param string $rut RUT con dígito verificador (puntos y guión opcionales).
     * @throws UnexpectedValueException Si se encontró algún problema al
     * validar el RUT.
     */
    public static function validate(string $rut): void
    {
        $originalRut = $rut;
        [$rut, $dv] = self::toArray($rut);

        // Validar mínimo del RUT.
        if ($rut < self::RUT_MIN) {
            throw new UnexpectedValueException(sprintf(
                'El RUT no puede ser menor a %s y se encontró el valor %s.',
                self::addThousandsSeparator(self::RUT_MIN),
                self::addThousandsSeparator($rut)
            ));
        }

        // Validar máximo del RUT.
        if ($rut > self::RUT_MAX) {
            throw new UnexpectedValueException(sprintf(
                'El RUT no puede ser mayor a %s y se encontró el valor %s.',
                self::addThousandsSeparator(self::RUT_MAX),
                self::addThousandsSeparator($rut)
            ));
        }

        // Validar que el dígito verificador sea entre 0-9 o 'K'.
        if (!preg_match('/^[0-9K]$/', $dv)) {
            throw new UnexpectedValueException(sprintf(
                'El dígito verificador debe ser un caracter entre "0" y "9", o la letra "K" mayúscula. Se encontró el valor "%s".',
                $dv
            ));
        }

        // Validar que el DV sea correcto para el RUT.
        $real_dv = self::calculateDv((int) $rut);
        if ($dv !== $real_dv) {
            throw new UnexpectedValueException(sprintf(
                'El dígito verificador del RUT %s es incorrecto. Se encontró el valor "%s" y para la parte numérica %s del RUT se espera que el dígito verificador sea "%s".',
                self::formatFull($originalRut),
                $dv,
                self::addThousandsSeparator($rut),
                $real_dv
            ));
        }
    }

    /**
     * Entrega la parte numérica del RUT a partir de un RUT que tiene DV.
     *
     * @param string $rut RUT completo, con DV (puntos y guión opcionales).
     * @return integer Parte numérica del RUT (no incluye DV).
     */
    public static function removeDv(string $rut): int
    {
        $rut = self::removeThousandsSeparatorAndDash($rut);

        return (int) substr($rut, 0, -1);
    }

    /**
     * Agrega el dígito verificador al RUT y lo entrega como un string con el
     * DV concatenado al final (sin formato).
     *
     * @param int $rut RUT en formato número y sin dígito verificador.
     * @return string String con el RUT con dígito verificador, sin formato.
     */
    public static function addDv(int $rut): string
    {
        return ((string) $rut) . self::calculateDv($rut);
    }

    /**
     * Da formato al RUT a partir de un RUT que venía como string (con DV).
     *
     * @param string $rut
     * @return string
     */
    private static function formatFromString(string $rut): string
    {
        return self::formatAsString(trim($rut));
    }

    /**
     * Da formato al RUT a partir de un RUT que venía como número (sin DV).
     *
     * @param integer $rut
     * @return string
     */
    private static function formatFromInt(int $rut): string
    {
        $rut = self::addDv($rut);

        return self::formatAsString($rut);
    }

    /**
     * Limpia el RUT quitando el separador de miles (que pude venir con punto
     * o comas) y el guión.
     *
     * @param string $rut RUT completo posiblemente con puntos y guión.
     * @return string Entrega el RUT en formato 112223334.
     */
    private static function removeThousandsSeparatorAndDash(string $rut): string
    {
        return str_replace(['.', ',', '-'], '', $rut);
    }

    /**
     * Agrega el separador de miles al RUT y lo entrega como string.
     *
     * @param integer $rut Solo el número del RUT, sin DV.
     * @return string Parte numérica del RUT como string con separador de miles.
     */
    private static function addThousandsSeparator(int $rut): string
    {
        return number_format($rut, 0, '', '.');
    }

    /**
     * Entrega el RUT formateado como string pero sin puntos.
     *
     * Básicamente, toma el RUT y:
     *
     *   - Le quita los separadores de miles.
     *   - Se asegura de que tenga guión, si no lo tenía.
     *
     * @param string $rut RUT con DV (puntos y guión opcionales).
     * @return string RUT con DV y guión, sin puntos.
     */
    private static function formatAsString(string $rut): string
    {
        [$rut, $dv] = self::toArray($rut);

        return $rut . '-' . $dv;
    }
}

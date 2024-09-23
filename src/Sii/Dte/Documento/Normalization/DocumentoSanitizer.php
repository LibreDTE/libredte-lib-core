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

namespace libredte\lib\Core\Sii\Dte\Documento\Normalization;

use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoException;

/**
 * Clase que maneja la limpieza (sanitización) de los datos de un documento.
 *
 * El objetivo es reducir los problemas por errores de esquema que se pueden
 * dar por los datos que se usan para crear el documento.
 */
class DocumentoSanitizer
{
    /**
     * Ejecuta la limpieza de los datos.
     *
     * @param array $data Arreglo con los datos del documento a limpiar.
     * @return array Arreglo con los datos limpios.
     */
    public function sanitize(array $data): array
    {
        $data = $this->applyInitialSanitization($data);
        $data = $this->applyProSanitization($data);

        return $data;
    }

    /**
     * Limpia los datos del documento.
     *
     * @param array $data Arreglo con los datos del documento a limpiar.
     * @return array Arreglo con los datos limpios.
     */
    private function applyInitialSanitization(array $data): array
    {
        // Si no viene un folio asignado error.
        if (!isset($data['Encabezado']['IdDoc']['Folio'])) {
            throw new DocumentoException(
                'Es obligatorio indicar el folio del documento.'
            );
        }

        // Formatear y validar los RUT.
        $data['Encabezado']['Emisor']['RUTEmisor'] = Rut::format(
            $data['Encabezado']['Emisor']['RUTEmisor']
        );
        $data['Encabezado']['Receptor']['RUTRecep'] = Rut::format(
            $data['Encabezado']['Receptor']['RUTRecep']
        );
        Rut::validate($data['Encabezado']['Emisor']['RUTEmisor']);
        Rut::validate($data['Encabezado']['Receptor']['RUTRecep']);

        // Limpiar datos del emisor.
        if (!empty($data['Encabezado']['Emisor']['Acteco'])) {
            if (strlen((string)$data['Encabezado']['Emisor']['Acteco']) === 5) {
                $data['Encabezado']['Emisor']['Acteco'] =
                    '0' . $data['Encabezado']['Emisor']['Acteco']
                ;
            }
        }

        // Limpiar datos del receptor.
        if (!empty($data['Encabezado']['Receptor']['RznSocRecep'])) {
            $data['Encabezado']['Receptor']['RznSocRecep'] =
                mb_substr($data['Encabezado']['Receptor']['RznSocRecep'], 0, 100)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['GiroRecep'])) {
            $data['Encabezado']['Receptor']['GiroRecep'] =
                mb_substr($data['Encabezado']['Receptor']['GiroRecep'], 0, 40)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['Contacto'])) {
            $data['Encabezado']['Receptor']['Contacto'] =
                mb_substr($data['Encabezado']['Receptor']['Contacto'], 0, 80)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['CorreoRecep'])) {
            $data['Encabezado']['Receptor']['CorreoRecep'] =
                mb_substr($data['Encabezado']['Receptor']['CorreoRecep'], 0, 80)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['DirRecep'])) {
            $data['Encabezado']['Receptor']['DirRecep'] =
                mb_substr($data['Encabezado']['Receptor']['DirRecep'], 0, 70)
            ;
        }
        if (!empty($data['Encabezado']['Receptor']['CmnaRecep'])) {
            $data['Encabezado']['Receptor']['CmnaRecep'] =
                mb_substr($data['Encabezado']['Receptor']['CmnaRecep'], 0, 20)
            ;
        }

        // Entregar los datos limpios.
        return $data;
    }

    /**
     * Limpia los datos del documento utilizando funcionalidades Pro.
     *
     * @param array $data Arreglo con los datos del documento a limpiar.
     * @return array Arreglo con los datos limpios.
     */
    private function applyProSanitization(array $data): array
    {
        // Limpiar los datos con las funcionalidades Pro de la biblioteca.
        $class = '\libredte\lib\Pro\Sii\Dte\Documento\Normalization\DocumentoSanitizer';
        if (class_exists($class)) {
            $sanitizer = $class::create($this);
            $data = $sanitizer->sanitize($data);
        }

        // Entregar los datos limpios.
        return $data;
    }
}

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

namespace libredte\lib\Core\Sii\Dte\Documento\Normalization;

use libredte\lib\Core\Helper\Rut;

/**
 * Clase que maneja la validación de los datos de un documento.
 */
class DocumentoValidator
{
    /**
     * Ejecuta la validación de los datos.
     *
     * @param array $data Arreglo con los datos del documento a validar.
     */
    public function validate(array $data): void
    {
        $this->applyCoreValidation($data);
        $this->applyProValidation($data);
    }

    /**
     * Valida los datos del documento utilizando funcionalidades Core.
     *
     * @param array $data Arreglo con los datos del documento a validar.
     * @throws UnexpectedValueException
     */
    private function applyCoreValidation(array $data): void
    {
        // Validar los RUTs.
        Rut::validate($data['Encabezado']['Emisor']['RUTEmisor']);
        Rut::validate($data['Encabezado']['Receptor']['RUTRecep']);
    }

    /**
     * Valida los datos del documento utilizando funcionalidades Pro.
     *
     * @param array $data Arreglo con los datos del documento a validar.
     * @throws DocumentoValidatorException
     */
    private function applyProValidation(array $data): void
    {
        // Validar los datos con las funcionalidades Pro de la biblioteca.
        $class = '\libredte\lib\Pro\Sii\Dte\Documento\Normalization\DocumentoValidator';
        if (class_exists($class)) {
            $validator = $class::create($this);
            $validator->validate($data);
        }
    }
}

<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Helper;

/**
 * Textos de ayuda asociados a los estados de rechazo del SII.
 *
 * Permite mostrar al usuario una explicación accionable cuando el SII rechaza
 * un DTE, derivada del código de rechazo presente en la glosa y el detalle.
 */
class SiiEnvioAyudas
{
    /**
     * Mapa de ayudas por código de glosa SII.
     *
     * Claves de primer nivel: primeros 3 caracteres de la glosa (ej: 'RCH').
     * Si el valor es un array, las claves son prefijos del campo detalle.
     * Si el valor es string, aplica para cualquier detalle de ese código.
     *
     * Placeholders disponibles: {dte}, {folio}.
     */
    private const AYUDAS = [
        'RCH' => [
            'CAF-3-517'     => 'El CAF (archivo de folios) que contiene al folio {folio} se encuentra vencido y ya no es válido. Debe eliminar el DTE, anular los folios del CAF vencido y solicitar un nuevo CAF. Finalmente emitir nuevamente el DTE con el primer folio disponible del nuevo CAF.',
            'DTE-3-100'     => 'Posible problema con doble envío al SII. Verifique el documento en el SII y corrobore el estado real.',
            'DTE-3-101'     => 'El folio {folio} ya fue usado para enviar un DTE al SII con otros datos. Debe eliminar el DTE y corregir el folio siguiente si es necesario a uno que no haya sido usado previamente. Finalmente emitir nuevamente el DTE.',
            'REF-3-750'     => 'El DTE emitido T{dte}F{folio} hace referencia a un documento que no existe en SII. Normalmente esto ocurre al hacer referencia a un documento rechazado. Los documentos rechazados no se deben referenciar, ya que no son válidos. Ejemplo: no puede crear una nota de crédito para una factura rechazada por el SII.',
            'REF-3-415'     => 'Se está generando un DTE que requiere referencias y no se está colocando una referencia válida. Ejemplo: no puede anular una guía de despacho con una nota de crédito, puesto que la guía no genera un débito fiscal.',
            'HED-3-305'     => 'La fecha de emisión del DTE es previa a la fecha de autorización del documento.',
            'DTE-3-601'     => 'El folio {folio} del documento fue anulado previo a la emisión del DTE en SII y no puede ser utilizado. Este documento debe ser eliminado y se debe emitir con nuevo folio.',
            'REF L[5] -3-758' => 'Es obligatorio en NC y ND especificar el código de referencia (anula documento, corrige montos o corrige textos). Debe eliminar este DTE y emitir nuevamente agregando el código de referencia que corresponda.',
            'ENV-3-6'       => 'Falta el permiso para firmar o enviar documentos en la configuración de usuarios en SII.',
            'ENV-3-0'       => 'Probablemente se ha incluido un dato no permitido por el SII en el XML. Puede ser el formato de algún número o un caracter inválido (como un emoji).',
        ],
        'RFR' => 'Problema con la firma al enviar el documento al SII. Se recomienda reenviar el documento y luego volver a consultar el estado.',
    ];

    /**
     * Devuelve el texto de ayuda para un documento rechazado, o null si no
     * existe ayuda específica para esa combinación de glosa y detalle.
     *
     * @param string  $glosa    Glosa SII almacenada (ej: 'RCH - DTE Rechazado').
     * @param ?string $detalle  Detalle SII almacenado (ej: '(DTE-3-101) Folio...').
     * @param int     $tipoDoc  Código del tipo de documento (para placeholder {dte}).
     * @param int     $folio    Folio del documento (para placeholder {folio}).
     */
    public static function get(
        string $glosa,
        ?string $detalle,
        int $tipoDoc,
        int $folio,
    ): ?string {
        $codigo = explode(' ', $glosa, 2)[0];

        if (!isset(self::AYUDAS[$codigo])) {
            return null;
        }

        $ayuda = self::AYUDAS[$codigo];

        if (is_array($ayuda)) {
            if ($detalle === null) {
                return null;
            }
            foreach ($ayuda as $clave => $texto) {
                if (str_starts_with($detalle, '(' . $clave . ')')) {
                    return self::replacePlaceholders($texto, $tipoDoc, $folio);
                }
            }
            return null;
        }

        return self::replacePlaceholders($ayuda, $tipoDoc, $folio);
    }

    private static function replacePlaceholders(
        string $texto,
        int $tipoDoc,
        int $folio
    ): string {
        return str_replace(
            ['{dte}', '{folio}'],
            [(string) $tipoDoc, (string) $folio],
            $texto
        );
    }
}

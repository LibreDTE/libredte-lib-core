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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Lib\Core\Foundation\Abstract\AbstractStrategy;
use Derafu\Lib\Core\Helper\Rut;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\SanitizerStrategyInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\SanitizerException;

/**
 * Clase abstracta (base) para las estrategias de sanitización de documentos
 * tributarios.
 */
abstract class AbstractSanitizerStrategy extends AbstractStrategy implements SanitizerStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function sanitize(DocumentBagInterface $bag): array
    {
        $this->sanitizeEssentials($bag);
        $this->sanitizeDocument($bag);

        return $bag->getNormalizedData();
    }

    /**
     * Sanitización personalizada de cada estrategia.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    abstract protected function sanitizeDocument(DocumentBagInterface $bag): void;

    /**
     * Limpia los datos esenciales del documento.
     *
     * @param DocumentBagInterface $bag
     * @return void
     */
    protected function sanitizeEssentials(DocumentBagInterface $bag): void
    {
        $data = $bag->getNormalizedData();

        // Si no viene un folio asignado error.
        if (!isset($data['Encabezado']['IdDoc']['Folio'])) {
            throw new SanitizerException(
                'Es obligatorio indicar el folio del documento.'
            );
        }

        // Formatear los RUT.
        $data['Encabezado']['Emisor']['RUTEmisor'] = Rut::format(
            $data['Encabezado']['Emisor']['RUTEmisor']
        );
        $data['Encabezado']['Receptor']['RUTRecep'] = Rut::format(
            $data['Encabezado']['Receptor']['RUTRecep']
        );

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

        // Actualizar los datos normalizados con los sanitizados.
        $bag->setNormalizedData($data);
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract;

use Derafu\Lib\Core\Foundation\Abstract\AbstractFactory;
use Derafu\Lib\Core\Foundation\Contract\FactoryInterface;
use Derafu\Lib\Core\Helper\Rut;

/**
 * Clase abstracta para las implementaciones de fábricas de contribuyentes,
 * emisores y receptores de documentos tributarios.
 */
abstract class AbstractContribuyenteFactory extends AbstractFactory implements FactoryInterface
{
    /**
     * Normaliza los datos del contribuyente que podrían venir en diferentes
     * índices.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $normalized = [];

        $normalized['rut'] = (
            $data['rut']
            ?? $data['RUTEmisor']
            ?? $data['RUTRecep']
            ?? null
        ) ?: '66666666-6';

        if (is_int($normalized['rut'])) {
            $normalized['rut'] = Rut::addDv($normalized['rut']);
        }

        $normalized['razon_social'] = (
            $data['razon_social']
            ?? $data['RznSoc']
            ?? $data['RznSocEmisor']
            ?? $data['RznSocRecep']
            ?? null
        ) ?: null;

        $normalized['giro'] = (
            $data['giro']
            ?? $data['GiroEmis']
            ?? $data['GiroEmisor']
            ?? $data['GiroRecep']
            ?? null
        ) ?: null;

        $normalized['actividad_economica'] = (
            (int) (
                $data['actividad_economica']
                ?? $data['Acteco']
                ?? 0
            )
        ) ?: null;

        $normalized['telefono'] = (
            $data['telefono']
            ?? $data['Telefono']
            ?? $data['Contacto']
            ?? null
        ) ?: null;

        $normalized['email'] = (
            $data['email']
            ?? $data['CorreoEmisor']
            ?? $data['CorreoRecep']
            ?? null
        ) ?: null;

        $normalized['direccion'] = (
            $data['direccion']
            ?? $data['DirOrigen']
            ?? $data['DirRecep']
            ?? null
        ) ?: null;

        $normalized['comuna'] = (
            $data['comuna']
            ?? $data['CmnaOrigen']
            ?? $data['CmnaRecep']
            ?? null
        ) ?: null;

        return $normalized;
    }
}

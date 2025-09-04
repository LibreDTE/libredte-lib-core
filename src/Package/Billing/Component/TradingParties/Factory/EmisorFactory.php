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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Factory;

use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Support\Factory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Abstract\AbstractContribuyenteFactory;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\AutorizacionDte;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Emisor;

/**
 * Fábrica de una entidad de emisor.
 */
class EmisorFactory extends AbstractContribuyenteFactory implements EmisorFactoryInterface
{
    /**
     * Clase de la entidad de los emisores.
     *
     * @var string
     */
    private string $class = Emisor::class;

    /**
     * {@inheritDoc}
     */
    public function create(array $data): EmisorInterface
    {
        $normalized = $this->normalizeData($data);

        [$normalized['rut'], $normalized['dv']] = Rut::toArray($normalized['rut']);

        $emisor = Factory::create($normalized, $this->class);

        if (!empty($data['autorizacionDte'])) {
            $emisor->setAutorizacionDte(
                new AutorizacionDte(
                    $data['autorizacionDte']['fechaResolucion'] ?? '',
                    (int) ($data['autorizacionDte']['numeroResolucion'] ?? 0)
                )
            );
        }

        return $emisor;
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeData(array $data): array
    {
        $normalized = parent::normalizeData($data);

        $normalized['codigo_sucursal'] = (
            (int) (
                $data['codigo_sucursal']
                ?? $data['CdgSIISucur']
                ?? false
            )
        ) ?: null;

        $normalized['vendedor'] = (
            $data['vendedor']
            ?? $data['CdgVendedor']
            ?? null
        ) ?: null;

        return $normalized;
    }
}

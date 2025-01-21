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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Service;

use Derafu\Lib\Core\Helper\Hydrator;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorProviderInterface;

/**
 * Proveedor falso de datos de un emisor para pruebas.
 *
 * La aplicación que use LibreDTE debe implementar este servicio para resolver
 * los datos que falten de un emisor al emitir un documento.
 */
class FakeEmisorProvider implements EmisorProviderInterface
{
    /**
     * Constructor del servicio y sus dependencias.
     *
     * @param EmisorFactoryInterface $emisorFactory
     */
    public function __construct(private EmisorFactoryInterface $emisorFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(int|string|EmisorInterface $emisor): EmisorInterface
    {
        // Si se pasó el RUT se crea una instancia del emisor usando la factory.
        if (is_int($emisor) || is_string($emisor)) {
            $emisor = $this->emisorFactory->create(['rut' => $emisor]);
        }

        // El emisor se estandariza como SASCO SPA.
        $emisor = Hydrator::hydrate($emisor, [
            'rut' => 76192083,
            'dv' => '9',
            'razon_social' => 'SASCO SpA',
            'giro' => 'Tecnología, Informática y Telecomunicaciones',
            'actividad_economica' => 726000,
            'telefono' => '+56 2 12345678',
            'email' => 'correo.sasco@example.com',
            'direccion' => 'DBG',
            'comuna' => 'Santa Cruz',
            'codigo_sucursal' => 123456,
            'vendedor' => 'libredte',
        ]);

        // Se entrega la instancia del emisor.
        return $emisor;
    }
}

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
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\ReceptorProviderInterface;

/**
 * Proveedor falso de datos de un receptor para pruebas.
 *
 * La aplicación que use LibreDTE debe implementar este servicio para resolver
 * los datos que falten de un receptor al emitir un documento.
 */
class FakeReceptorProvider implements ReceptorProviderInterface
{
    /**
     * Constructor del servicio y sus dependencias.
     *
     * @param ReceptorFactoryInterface $receptorFactory
     */
    public function __construct(
        private ReceptorFactoryInterface $receptorFactory
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(int|string|ReceptorInterface $receptor): ReceptorInterface
    {
        // Si se pasó el RUT se crea una instancia del receptor usando la
        // factory.
        if (is_int($receptor) || is_string($receptor)) {
            $receptor = $this->receptorFactory->create(['rut' => $receptor]);
        }

        // El emisor se estandariza como SII.
        $receptor = Hydrator::hydrate($receptor, [
            'rut' => 60803000,
            'dv' => 'K',
            'razon_social' => 'Servicio de Impuestos Internos',
            'giro' => 'Gobierno',
            'telefono' => '+56 2 32525575',
            'email' => 'correo.sii@example.com',
            'direccion' => 'Alonso Ovalle 680',
            'comuna' => 'Santiago',
        ]);

        // Se entrega la instancia del receptor.
        return $receptor;
    }
}

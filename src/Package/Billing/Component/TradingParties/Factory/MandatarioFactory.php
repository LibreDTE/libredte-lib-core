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

use Derafu\Lib\Core\Foundation\Abstract\AbstractFactory;
use Derafu\Lib\Core\Helper\Factory;
use Derafu\Lib\Core\Helper\Rut;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Entity\Mandatario;
use LogicException;

/**
 * Fábrica de una entidad de mandatario.
 */
class MandatarioFactory extends AbstractFactory implements MandatarioFactoryInterface
{
    /**
     * Clase de la entidad de los mandatarios.
     *
     * @var string
     */
    private string $class = Mandatario::class;

    /**
     * {@inheritDoc}
     */
    public function create(array $data): MandatarioInterface
    {
        $data = $this->normalizeData($data);

        [$data['run'], $data['dv']] = Rut::toArray($data['run']);

        return Factory::create($data, $this->class);
    }

    /**
     * Normaliza los datos del mandatario para su creación.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $data['run'] = $data['run'] ?? $data['rut'] ?? null;
        unset($data['rut']);

        if (empty($data['run']) || $data['nombre'] || $data['email']) {
            throw new LogicException(
                'Los atributos run, nombre y email del mandatario son obligatorios.'
            );
        }

        return $data;
    }
}

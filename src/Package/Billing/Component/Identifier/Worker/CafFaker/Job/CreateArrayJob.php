<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use OpenSSLAsymmetricKey;

/**
 * Crea un CAF falso para pruebas, como arreglo con la estructura del XML.
 *
 * Este CAF tiene claves públicas y privadas válidas, pero la firma no será
 * verificable por el SII. Su IDK es `CafInterface::FAKE_IDK`.
 */
#[Job(name: 'create_array', worker: 'caf_faker', component: 'identifier', package: 'billing')]
class CreateArrayJob extends AbstractJob implements JobInterface
{
    /**
     * Crea un CAF falso y lo entrega como arreglo.
     *
     * Cada llamada genera un par de claves nuevo, por lo que dos llamadas con
     * los mismos datos no entregan el mismo resultado.
     *
     * @param string $rut RUT del emisor.
     * @param string $razonSocial Razón social del emisor.
     * @param int $tipoDocumento Código del tipo de documento.
     * @param int $folioDesde Folio inicial.
     * @param int $folioHasta Folio final.
     * @return array<array-key, mixed> El CAF falso en formato arreglo.
     * @throws CafException Si el folio inicial es mayor que el final.
     */
    public function create(
        string $rut,
        string $razonSocial,
        int $tipoDocumento,
        int $folioDesde,
        int $folioHasta
    ): array {
        if ($folioDesde > $folioHasta) {
            throw new CafException([
                'When creating a CAF, the starting document number ({from}) cannot be greater than the ending document number ({to}).',
                'from' => $folioDesde,
                'to' => $folioHasta,
            ]);
        }

        [$privateKey, $publicKey] = $this->generateKeys();

        return [
            'AUTORIZACION' => [
                'CAF' => [
                    '@attributes' => ['version' => '1.0'],
                    'DA' => [
                        'RE' => $rut,
                        'RS' => mb_substr($razonSocial, 0, 40),
                        'TD' => $tipoDocumento,
                        'RNG' => [
                            'D' => $folioDesde,
                            'H' => $folioHasta,
                        ],
                        'FA' => date('Y-m-d'),
                        'RSAPK' => [
                            'M' => 'bGlicmVkdGUtY2FmLW1vZHVsdXM=',
                            'E' => 'bGlicmVkdGUtY2FmLWV4cG9uZW50',
                        ],
                        'IDK' => CafInterface::FAKE_IDK,
                    ],
                    'FRMA' => [
                        '@attributes' => ['algoritmo' => 'SHA1withRSA'],
                        '@value' => 'bGlicmVkdGUtY2FmLXNpZ25hdHVyZQ==',
                    ],
                ],
                'RSASK' => $privateKey,
                'RSAPUBK' => $publicKey,
            ],
        ];
    }

    /**
     * Genera las claves privada y pública del CAF, en formato PEM.
     *
     * @return array{string, string} Clave privada y clave pública.
     */
    private function generateKeys(): array
    {
        $res = ValueCaster::requireObject(
            openssl_pkey_new(),
            OpenSSLAsymmetricKey::class
        );
        openssl_pkey_export($res, $privateKey);
        $details = ValueType::array()->parse(openssl_pkey_get_details($res));

        return [
            ValueCaster::requireString($privateKey),
            ValueCaster::requireString($details['key']),
        ];
    }
}

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

namespace libredte\lib\Core\Package\System\Component\Certificate\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\System\Component\Certificate\Contract\LoaderWorkerInterface;

/**
 * Clase para la carga genérica de certificados digitales.
 */
#[Worker(name: 'loader', component: 'certificate', package: 'system')]
class LoaderWorker extends AbstractWorker implements LoaderWorkerInterface
{
    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'load',
        summary: 'Carga un certificado digital',
        description: <<<'MD'
            Carga un certificado digital y entrega sus datos: titular, vigencia, emisor, el certificado en PEM y su llave privada. Sirve para comprobar que un certificado se puede leer con la contraseña o las llaves indicadas antes de usarlo en otras operaciones.

            ## Cómo se usa

            Se envía el certificado en `certificate`. La respuesta usa los mismos nombres de campo que se aceptan como entrada (`cert` y `pkey`), por lo que se puede enviar de vuelta a esta u otra operación.
            MD,
        parameters: [
            'certificate' => [
                'description' => <<<'MD'
                    Certificado digital. Se acepta una de estas formas:

                    - `data` (archivo PKCS12 o PFX codificado en base64) y `password`.
                    - `certificate` y `privateKey`, ambos en formato PEM.
                    - `cert` y `pkey`, ambos en formato PEM, que son los nombres con que se entrega un certificado en las respuestas. Opcionalmente `chain`, con la lista de certificados intermedios.
                    MD,
                'example' => [
                    'data' => '',
                    'password' => '',
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Certificado cargado. `id` es el RUN del titular. `from` y `to` son el inicio y el fin de la vigencia, `total_days` su duración en días y `expiration_days` los días que le quedan. `is_active` indica si está vigente. `cert` es el certificado en PEM, `chain` los certificados intermedios y `pkey` la llave privada en PEM.
                    MD,
                'example' => [
                    'id' => '12345678-9',
                    'name' => 'Juan Pérez',
                    'email' => 'juan.perez@example.com',
                    'from' => '2024-01-01T00:00:00',
                    'to' => '2025-01-01T00:00:00',
                    'total_days' => 366,
                    'expiration_days' => 300,
                    'is_active' => true,
                    'issuer' => 'AC de Ejemplo',
                    'modulus' => 'rta/vl0+RO1n1oBpvS0WB6/UvN+F+OQS1y9xmakY...',
                    'exponent' => 'AQAB',
                    'cert' => "-----BEGIN CERTIFICATE-----\nMIIEbjCCA1agAwIBAgIB...",
                    'chain' => [],
                    'pkey' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASC...",
                ],
            ],
        ],
    )]
    public function load(CertificateInterface $certificate): CertificateInterface
    {
        return $certificate;
    }
}

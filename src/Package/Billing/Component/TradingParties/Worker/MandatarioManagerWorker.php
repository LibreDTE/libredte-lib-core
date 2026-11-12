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

namespace libredte\lib\Core\Package\Billing\Component\TradingParties\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Certificate\Contract\CertificateFakerInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioFactoryInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\MandatarioManagerWorkerInterface;

/**
 * Clase para el worker que administra los mandatarios.
 */
#[Worker(name: 'mandatario_manager', component: 'trading_parties', package: 'billing')]
class MandatarioManagerWorker extends AbstractWorker implements MandatarioManagerWorkerInterface
{
    public function __construct(
        private MandatarioFactoryInterface $mandatarioFactory,
        private CertificateFakerInterface $certificateFaker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'create_from_certificate',
        summary: 'Obtiene el mandatario de un certificado digital',
        description: <<<'MD'
            Entrega los datos de la persona titular de un certificado digital (su RUN, su nombre y su correo electrónico), tomados del propio certificado.

            ## Cómo se usa

            Se envía el certificado en `certificate`. La respuesta es el mandatario, que se puede usar donde se pida uno, por ejemplo para identificar a quien firma o envía un documento.
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
                    'certificate' => '',
                    'privateKey' => '',
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => 'Mandatario con el RUN, el nombre y el correo del titular del certificado.',
                'example' => [
                    'run' => '12345678-9',
                    'nombre' => 'Juan Pérez',
                    'email' => 'juan.perez@example.com',
                ],
            ],
        ],
    )]
    public function createFromCertificate(
        CertificateInterface $certificate
    ): MandatarioInterface {
        return $this->mandatarioFactory->create([
            'run' => $certificate->getId(),
            'nombre' => $certificate->getName(),
            'email' => $certificate->getEmail(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'create_fake_certificate',
        summary: 'Crea un certificado digital falso para pruebas',
        description: <<<'MD'
            Genera un certificado digital falso a nombre del mandatario indicado, firmado por una autoridad certificadora de prueba. Sirve para probar la firma de documentos sin usar un certificado real; no es válido ante el SII.

            ## Cómo se usa

            Se envían los datos del mandatario. La respuesta es el certificado completo, con su llave privada en `pkey`, y se puede enviar como `certificate` a las operaciones que firman documentos, como `billing.document.builder::build` o `billing.book.builder::build`.
            MD,
        parameters: [
            'mandatario' => [
                'description' => 'Persona a cuyo nombre se genera el certificado: `run` (RUN con dígito verificador, por ejemplo `12345678-9`), `nombre` y `email`.',
                'example' => [
                    'run' => '12345678-9',
                    'nombre' => 'Juan Pérez',
                    'email' => 'juan.perez@example.com',
                ],
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    Certificado generado. `id` es el RUN del titular. `from` y `to` son el inicio y el fin de la vigencia, `total_days` su duración en días y `expiration_days` los días que le quedan. `is_active` indica si está vigente. `cert` es el certificado en PEM, `chain` los certificados intermedios y `pkey` la llave privada en PEM.
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
                    'issuer' => 'Derafu Test Certificate Authority',
                    'modulus' => 'rta/vl0+RO1n1oBpvS0WB6/UvN+F+OQS1y9xmakY...',
                    'exponent' => 'AQAB',
                    'cert' => "-----BEGIN CERTIFICATE-----\nMIIEbjCCA1agAwIBAgIB...",
                    'chain' => [
                        "-----BEGIN CERTIFICATE-----\nMIIERjCCAy6gAwIBAgIC...",
                    ],
                    'pkey' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASC...",
                ],
            ],
        ],
    )]
    public function createFakeCertificate(
        MandatarioInterface $mandatario
    ): CertificateInterface {
        return $this->certificateFaker->createFake(
            id: $mandatario->getRun(),
            name: $mandatario->getNombre(),
            email: $mandatario->getEmail()
        );
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Support;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Trait\OptionsAwareTrait;
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use Derafu\Translation\Exception\Core\TranslatableRuntimeException as RuntimeException;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Enum\SiiEnvironment;
use Symfony\Component\OptionsResolver\Options;

/**
 * Clase para administrar la solicitud al sitio web del SII de Chile.
 */
class SiiRequest implements SiiRequestInterface
{
    use OptionsAwareTrait;

    /**
     * Reglas de esquema de las opciones de la solicitud al SII.
     *
     * @var array
     */
    protected array $optionsSchema = [
        // Por defecto la conexión es a los servidores de producción del SII.
        'environment' => [
            'types' => SiiEnvironment::class,
            'default' => SiiEnvironment::PRODUCTION,
            'choices' => [
                SiiEnvironment::PRODUCTION,
                SiiEnvironment::STAGING,
            ],
        ],

        // Especifica cuántos reintentos se realizarán de manera automática al
        // hacer una solicitud al SII. El reintento se hará utilizando
        // "exponential backoff", por lo que un número muy grande implicará un
        // tiempo de ejecución alto.
        'retries' => [
            'types' => 'int',
            'default' => 10,
            'normalizer' => null, // Se asigna en el constructor como callback.
        ],

        // Especifica si se debe o no realizar la validación del certificado
        // SSL del SII. A veces, en pruebas sobre todo, ha resultado útil poder
        // desactivar esta validación. Sin embargo, se desaconseja hacerlo por
        // motivos de seguridad.
        'verify_ssl' => [
            'types' => 'bool',
            'default' => true,
        ],

        // Opciones para el token que se utilizará en la solicitud.
        'token' => [
            'types' => 'array',
            'schema' => [
                // Esta es la caché por defecto que se utilizará al solicitar
                // una caché que implemente PSR-16 para almarcenar el token.
                'cache' => [
                    'types' => 'string',
                    'default' => 'memory',
                    'choices' => ['memory', 'filesystem'],
                ],

                // Formato de la clave en caché para guardar el token asociado a
                // un certificado. Se utiliza un placeholder que se reemplazará
                // con el ID del certificado.
                'key' => [
                    'types' => 'string',
                    'default' => 'libredte_lib_sii_auth_token_%s',
                ],

                // Tiempo en segundos que el token es válido desde que se
                // solicitó. Será el tiempo que se mantenga en caché.
                'ttl' => [
                    'types' => 'int',
                    'default' => 60,
                ],
            ],
        ],


    ];

    /**
     * Certificado digital que se utilizará en las consultas al SII.
     *
     * @var CertificateInterface|null
     */
    private ?CertificateInterface $certificate;

    /**
     * Constructor de la configuración de conexión.
     *
     * @param CertificateInterface|null $certificate
     * @param array|OptionsInterface $options Opciones de la solicitud.
     */
    public function __construct(
        ?CertificateInterface $certificate = null,
        array|OptionsInterface $options = []
    ) {
        $this->certificate = $certificate;

        // Resolver opciones de la solicitud.
        $this->optionsSchema['retries']['normalizer'] =
            fn (Options $options, int $value) => max(0, min(10, $value))
        ;
        if (isset($options['environment']) && is_numeric($options['environment'])) {
            $options['environment'] = SiiEnvironment::from((int) $options['environment']);
        }
        $this->setOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironment(): SiiEnvironment
    {
        return $this->options->getReader()->requireObject('environment', SiiEnvironment::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getRetries(?int $retries = null): int
    {
        $retriesInOptions = $this->options->getReader()->requireInt('retries');

        return max(0, min(
            $retries ?? $retriesInOptions,
            $this->optionsSchema['retries']['default']
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getVerifySsl(): bool
    {
        return $this->options->getReader()->requireBool('verify_ssl');
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenDefaultCache(): string
    {
        return $this->options->getReader()->requireString('token.cache');
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenKey(): string
    {
        $key = $this->options->getReader()->requireString('token.key');

        if (!str_contains($key, '%s')) {
            throw new RuntimeException(
                'The token key must allow assigning the ID associated with the token.'
            );
        }

        if ($this->certificate === null) {
            throw new LogicException(
                'There is no digital certificate associated with the request.'
            );
        }

        return sprintf($key, $this->certificate->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenTtl(): int
    {
        return $this->options->getReader()->requireInt('token.ttl');
    }

    /**
     * {@inheritDoc}
     */
    public function getCertificate(): ?CertificateInterface
    {
        return $this->certificate;
    }
}

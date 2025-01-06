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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Support;

use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;

/**
 * Clase que permite generar un CAF falso (FakeCaf) para pruebas.
 *
 * Este CAF tiene claves públicas y privadas válidas, pero la firma no será
 * verificable por el SII.
 */
class CafFaker
{
    /**
     * IDK para Caf falsos.
     *
     * Se debe utilizar un valor diferente a los oficiales para poder omitir la
     * validación del SII al cargar el CAF.
     *
     * Define un "ambiente" de LibreDTE (DTE con este IDK no se envían a SII).
     */
    public const IDK = 666;

    /**
     * Datos del contribuyente emisor del CAF.
     *
     * @var array
     */
    private array $emisor;

    /**
     * Tipo de documento del CAF.
     *
     * @var int
     */
    private int $tipoDocumento;

    /**
     * Rango de folios.
     *
     * @var array
     */
    private array $rangoFolios;

    /**
     * Clave privada en formato PEM.
     *
     * @var string
     */
    private string $privateKey;

    /**
     * Clave pública en formato PEM.
     *
     * @var string
     */
    private string $publicKey;

    /**
     * Configura los datos del emisor.
     *
     * @param string $rut RUT del emisor.
     * @param string $razonSocial Razón social del emisor.
     */
    public function setEmisor(string $rut, string $razonSocial): static
    {
        $this->emisor = [
            'rut' => $rut,
            'razonSocial' => $razonSocial,
        ];

        return $this;
    }

    /**
     * Configura el tipo de documento del CAF.
     *
     * @param int $tipoDocumento Código del tipo de documento.
     */
    public function setTipoDocumento(int $tipoDocumento): static
    {
        $this->tipoDocumento = $tipoDocumento;

        return $this;
    }

    /**
     * Configura el rango de folios.
     *
     * @param int $desde Folio inicial.
     * @param int $hasta Folio final.
     */
    public function setRangoFolios(int $desde, int $hasta): static
    {
        if ($desde > $hasta) {
            throw new CafException(sprintf(
                'Al crear un CAF el folio desde (%d) no puede ser mayor que el folio hasta (%d).',
                $desde,
                $hasta
            ));
        }

        $this->rangoFolios = [
            'desde' => $desde,
            'hasta' => $hasta,
        ];

        return $this;
    }

    /**
     * Genera las claves públicas y privadas del CAF.
     */
    private function generateKeys(): void
    {
        $res = openssl_pkey_new();
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = $privateKey;
        $this->publicKey = openssl_pkey_get_details($res)['key'];
    }

    /**
     * Crea un CAF falso a partir de los datos asignados al CafFaker.
     *
     * @return array El CAF falso en formato arreglo.
     */
    public function toArray(): array
    {
        // Generar las claves del CAF.
        $this->generateKeys();

        // Retornar el arreglo que representa el CAF.
        return [
            'AUTORIZACION' => [
                'CAF' => [
                    '@attributes' => ['version' => '1.0'],
                    'DA' => [
                        'RE' => $this->emisor['rut'],
                        'RS' => mb_substr($this->emisor['razonSocial'], 0, 40),
                        'TD' => $this->tipoDocumento,
                        'RNG' => [
                            'D' => $this->rangoFolios['desde'],
                            'H' => $this->rangoFolios['hasta'],
                        ],
                        'FA' => date('Y-m-d'),
                        'RSAPK' => [
                            'M' => 'bGlicmVkdGUtY2FmLW1vZHVsdXM=',
                            'E' => 'bGlicmVkdGUtY2FmLWV4cG9uZW50',
                        ],
                        'IDK' => self::IDK,
                    ],
                    'FRMA' => [
                        '@attributes' => ['algoritmo' => 'SHA1withRSA'],
                        '@value' => 'bGlicmVkdGUtY2FmLXNpZ25hdHVyZQ==',
                    ],
                ],
                'RSASK' => $this->privateKey,
                'RSAPUBK' => $this->publicKey,
            ],
        ];
    }
}

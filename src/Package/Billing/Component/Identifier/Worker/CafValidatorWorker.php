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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Value\ValueCaster;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafValidatorException;

/**
 * Worker que permite validar archivos CAF.
 */
#[Worker(name: 'caf_validator', component: 'identifier', package: 'billing')]
class CafValidatorWorker extends AbstractWorker implements CafValidatorWorkerInterface
{
    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'validate',
        parameters: [
            'caf' => ['example' => ''],
        ],
    )]
    public function validate(CafInterface $caf): CafInterface
    {
        // Verificar firma del CAF con la clave pública del SII.
        $public_key_sii = $this->getSiiCertificate($caf->getIdk());
        if ($public_key_sii !== null) {
            // Las claves se cargan antes de usarlas: con un texto que no es una
            // clave, las funciones de cifrado y de verificación de OpenSSL
            // emiten un warning de PHP en vez de solo fallar.
            $sii_key = openssl_pkey_get_public($public_key_sii);
            if ($sii_key === false) {
                throw new CafValidatorException([
                    'Could not obtain SII certificate {file} to validate the CAF.',
                    'file' => $caf->getIdk() . '.cer',
                ]);
            }

            $firma = $caf->getFirma();
            $signed_da = $caf->getXmlDocument()
                ->setEncoding('ISO-8859-1')
                ->C14NEncodedFlattened('/AUTORIZACION/CAF/DA')
            ;
            if (openssl_verify($signed_da, base64_decode($firma), $sii_key) !== 1) {
                throw new CafValidatorException([
                    'CAF {id} signature is not valid (the CAF is not authorized by the SII).',
                    'id' => $caf->getId(),
                ]);
            }
        }

        // Verificar que la clave pública y privada sean válidas. Esto se hace
        // encriptando un texto random y desencriptándolo.
        $private_key = openssl_pkey_get_private($caf->getPrivateKey());
        if ($private_key === false) {
            throw new CafValidatorException([
                'CAF {id} failed private key validation (possible corrupt CAF file).',
                'id' => $caf->getId(),
            ]);
        }
        $test_plain = md5(date('U'));
        if (!openssl_private_encrypt($test_plain, $test_encrypted, $private_key)) {
            throw new CafValidatorException([
                'CAF {id} failed private key validation (possible corrupt CAF file).',
                'id' => $caf->getId(),
            ]);
        }
        $public_key = openssl_pkey_get_public($caf->getPublicKey());
        if ($public_key === false) {
            throw new CafValidatorException([
                'CAF {id} failed public key validation (possible corrupt CAF file).',
                'id' => $caf->getId(),
            ]);
        }
        if (!openssl_public_decrypt(ValueCaster::requireString($test_encrypted), $test_decrypted, $public_key)) {
            throw new CafValidatorException([
                'CAF {id} failed public key validation (possible corrupt CAF file).',
                'id' => $caf->getId(),
            ]);
        }
        if ($test_plain !== $test_decrypted) {
            throw new CafValidatorException([
                'CAF {id} failed to encrypt and decrypt a test string correctly (possible corrupt CAF file).',
                'id' => $caf->getId(),
            ]);
        }

        // Retornar el CAF validado.
        return $caf;
    }

    /**
     * Método para obtener el certificado X.509 del SII para la validación del
     * XML del CAF.
     *
     * @param int $idk IDK del certificado.
     * @return string|null Contenido del certificado o `null` si es un CAF
     * falso.
     * @throws CafException Si no es posible obtener el certificado del SII.
     */
    private function getSiiCertificate(int $idk): ?string
    {
        if ($idk === CafInterface::FAKE_IDK) {
            return null;
        }

        $filename = $idk . '.cer';
        $filepath = dirname(__DIR__, 6) . '/resources/certificates/' . $filename;

        if (!file_exists($filepath)) {
            throw new CafValidatorException([
                'Could not obtain SII certificate {file} to validate the CAF.',
                'file' => $filename,
            ]);
        }

        return ValueCaster::requireString(file_get_contents($filepath));
    }
}

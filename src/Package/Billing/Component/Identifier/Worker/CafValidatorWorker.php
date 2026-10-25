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
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafValidatorException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;

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
        parameters: [
            'caf' => ['example' => ''],
        ],
    )]
    public function validate(CafInterface $caf): CafInterface
    {
        // Verificar firma del CAF con la clave pública del SII.
        $public_key_sii = $this->getSiiCertificate($caf->getIdk());
        if ($public_key_sii !== null) {
            $firma = $caf->getFirma();
            $signed_da = $caf->getXmlDocument()
                ->setEncoding('ISO-8859-1')
                ->C14NEncodedFlattened('/AUTORIZACION/CAF/DA')
            ;
            if (openssl_verify($signed_da, base64_decode($firma), $public_key_sii) !== 1) {
                throw new CafValidatorException(sprintf(
                    'La firma del CAF %s no es válida (no está autorizado por el SII).',
                    $caf->getId()
                ));
            }
        }

        // Verificar que la clave pública y privada sean válidas. Esto se hace
        // encriptando un texto random y desencriptándolo.
        $private_key = $caf->getPrivateKey();
        $test_plain = md5(date('U'));
        if (!openssl_private_encrypt($test_plain, $test_encrypted, $private_key)) {
            throw new CafValidatorException(sprintf(
                'El CAF %s no pasó la validación de su clave privada (posible archivo CAF corrupto).',
                $caf->getId()
            ));
        }
        $public_key = $caf->getPublicKey();
        if (!openssl_public_decrypt($test_encrypted, $test_decrypted, $public_key)) {
            throw new CafValidatorException(sprintf(
                'El CAF %s no pasó la validación de su clave pública (posible archivo CAF corrupto).',
                $caf->getId()
            ));
        }
        if ($test_plain !== $test_decrypted) {
            throw new CafValidatorException(sprintf(
                'El CAF %s no logró encriptar y desencriptar correctamente un texto de prueba (posible archivo CAF corrupto).',
                $caf->getId()
            ));
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
        if ($idk === CafFaker::IDK) {
            return null;
        }

        $filename = $idk . '.cer';
        $filepath = dirname(__DIR__, 6) . '/resources/certificates/' . $filename;

        if (!file_exists($filepath)) {
            throw new CafValidatorException(sprintf(
                'No fue posible obtener el certificado del SII %s para validar el CAF.',
                $filename
            ));
        }

        return file_get_contents($filepath);
    }
}

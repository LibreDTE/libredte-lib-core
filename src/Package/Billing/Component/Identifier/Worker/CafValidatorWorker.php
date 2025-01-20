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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker;

use Derafu\Lib\Core\Foundation\Abstract\AbstractWorker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafValidatorWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafValidatorException;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFaker;

/**
 * Worker que permite validar archivos CAF.
 */
class CafValidatorWorker extends AbstractWorker implements CafValidatorWorkerInterface
{
    /**
     * {@inheritDoc}
     */
    public function validate(CafInterface $caf): void
    {
        // Verificar firma del CAF con la clave pública del SII.
        $public_key_sii = $this->getSiiCertificate($caf->getIdk());
        if ($public_key_sii !== null) {
            $firma = $caf->getFirma();
            $signed_da = $caf->getXmlDocument()->C14NWithIsoEncodingFlattened('/AUTORIZACION/CAF/DA');
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

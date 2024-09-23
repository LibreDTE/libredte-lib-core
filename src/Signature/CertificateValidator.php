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

namespace libredte\lib\Core\Signature;

/**
 * Clase que realizar validaciones al certificado digital para corroborar que
 * puede ser utilizado en una aplicación de facturación electrónica de Chile
 * según requerimientos del SII.
 */
class CertificateValidator
{
    /**
     * Realiza diferentes validaciones de la firma electrónica.
     *
     * @return void
     * @throws CertificateException
     */
    public static function validate(Certificate $certificate): void
    {
        // Validar que venga el ID (RUN) de la firma.
        $id = $certificate->getID(false);

        // Validar que venga DV en el ID (RUN).
        $dv = explode('-', $id)[1] ?? null;
        if ($dv === null) {
            throw new CertificateException(sprintf(
                'El ID (RUN) %s de la firma no es válido, debe incluir "-" (guión).',
                $id
            ));
        }

        // Validar que si el ID (RUN) termina con DV igual a "K", sea mayúscula.
        if ($dv === 'k') {
            throw new CertificateException(sprintf(
                'El RUN %s asociado a la firma no es válido, termina en "k" (minúscula). Debe adquirir una nueva firma y al comprarla corroborar que la "K" sea mayúscula. Se recomienda no comprar con el mismo proveedor: %s. Esto es necesario porque LibreDTE no puede utilizar un RUN que terminan con "k" (minúscula) en el certificado digital (firma electrónica).',
                $id,
                $certificate->getIssuer()
            ));
        }

        // Validar que la firma esté vigente (no vencida).
        if (!$certificate->isActive()) {
            throw new CertificateException(sprintf(
                'La firma venció el %s, debe usar una firma vigente. Si no posee una, debe adquirirla con un proveedor autorizado por el SII.',
                $certificate->getTo()
            ));
        }
    }
}

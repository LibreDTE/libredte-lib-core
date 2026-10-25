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

namespace libredte\lib\Core\Package\System\Component\Certificate\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Certificate\Contract\CertificateInterface;

/**
 * Interfaz para la carga genérica de certificados digitales.
 *
 * No depende de ningún paquete de negocio: cualquier consumidor externo
 * (API, consola) puede usar esto para cargar un certificado a partir de sus
 * datos crudos (PKCS#12 con clave, o certificado y llave privada en PEM) y
 * obtener de vuelta sus datos ya parseados (RUN, nombre, email, vigencia,
 * etc.), sin necesitar una operación de negocio específica que use un
 * certificado como insumo.
 */
interface LoaderWorkerInterface extends WorkerInterface
{
    /**
     * Carga un certificado digital.
     *
     * La deserialización de los datos crudos del certificado (recibidos vía
     * API/consola) ya ocurre antes de invocar este método: quien llama
     * recibe una instancia de `CertificateInterface` ya resuelta.
     *
     * @param CertificateInterface $certificate
     * @return CertificateInterface
     */
    public function load(CertificateInterface $certificate): CertificateInterface;
}

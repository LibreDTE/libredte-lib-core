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

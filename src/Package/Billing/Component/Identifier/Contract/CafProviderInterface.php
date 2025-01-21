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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Contract;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Interfaz para el servicio provee el CAF con el folio que se debe asignar al
 * emitir un documento tributario.
 *
 * Este proveedor permite que una aplicación entregue el CAF desde cualquier
 * fuente. Incluso que pueda proveer CAF falsos como la implementación que viene
 * por defecto en LibreDTE.
 */
interface CafProviderInterface
{
    /**
     * Provee un CAF para el emisor y tipo de documento solicitado.
     *
     * Opcionalmente se puede indicar el folio que se desea utilizar.
     *
     * @param EmisorInterface $emisor Emisor para el que se busca un CAF.
     * @param TipoDocumentoInterface $tipoDocumento Documento a buscar su CAF.
     * @param int|null $folio Permite indicar si se quiere un folio específico.
     * @return CafBagInterface Bolsa con los datos del CAF encontrado.
     */
    public function retrieve(
        EmisorInterface $emisor,
        TipoDocumentoInterface $tipoDocumento,
        ?int $folio = null
    ): CafBagInterface;
}

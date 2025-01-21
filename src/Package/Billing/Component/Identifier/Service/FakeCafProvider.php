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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Service;

use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafBagInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafProviderInterface;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Implementación de un proveedor de archivos CAF falsos.
 *
 * Esta implementación es solo para pruebas de LibreDTE. Cada aplicación deberá
 * implementar un servicio real de CafProviderInterface para que sea inyectado
 * donde sea necesario (ej: en emisión masiva).
 */
class FakeCafProvider implements CafProviderInterface
{
    /**
     * Historial de folios asignados.
     *
     * Permite entregar siempre folios diferentes para los emisores y tipos de
     * documentos. Así se evitan problemas de repetir folios. Esto es lo que
     * realmente debería implementar la aplicación que use LibreDTE: un control
     * de la asignación de folios.
     *
     * El arreglo guardará por cada emisor y tipo de documento el último folio
     * que fue entregado.
     *
     * @var array<int, array<int, int>>
     */
    private array $folios = [];

    /**
     * Constructor con dependencias del servicio.
     *
     * @param CafFakerWorkerInterface $cafFakerWorker
     */
    public function __construct(
        private CafFakerWorkerInterface $cafFakerWorker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(
        EmisorInterface $emisor,
        TipoDocumentoInterface $tipoDocumento,
        ?int $folio = null
    ): CafBagInterface {
        // Resolver el folio que corresponde usar.
        if ($folio === null) {
            if (!isset($this->folios[$emisor->getRutAsInt()][$tipoDocumento->getCodigo()])) {
                $this->folios[$emisor->getRutAsInt()][$tipoDocumento->getCodigo()] = 0;
            }
            $folio = $this->folios[$emisor->getRutAsInt()][$tipoDocumento->getCodigo()] + 1;
        }

        // Recordar el folio entregado.
        $this->folios[$emisor->getRutAsInt()][$tipoDocumento->getCodigo()] = $folio;

        // Crear la bolsa con el CAF falso para el emisor, tipo de documento y
        // folio determinado.
        return $this->cafFakerWorker->create(
            $emisor,
            $tipoDocumento->getCodigo(),
            $folio
        );
    }
}

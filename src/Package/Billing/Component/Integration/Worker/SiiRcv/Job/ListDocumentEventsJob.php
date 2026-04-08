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

namespace libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiRcv\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Xml\Contract\XmlServiceInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiLazyWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Contract\SiiRequestInterface;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\ListDocumentEventsException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\ListDocumentEventsResponse;
use Throwable;

/**
 * Lista el historial de eventos de un DTE en el RCV del SII.
 */
#[Job(name: 'list_document_events', worker: 'sii_rcv', component: 'integration', package: 'billing')]
class ListDocumentEventsJob extends AbstractJob implements JobInterface
{
    private const WSDL_SERVICE = 'registroreclamodteservice';

    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Lista los eventos históricos de un DTE.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return ListDocumentEventsResponse
     * @throws ListDocumentEventsException En caso de error.
     */
    public function list(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): ListDocumentEventsResponse {
        Rut::validate($company);
        [$rut, $dv] = Rut::toArray($company);

        try {
            $token = $this->siiLazyWorker->authenticate($request);
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: self::WSDL_SERVICE,
                function: 'listarEventosHistDoc',
                args: [
                    'rutEmisor' => $rut,
                    'dvEmisor' => $dv,
                    'tipoDoc' => $document,
                    'folio' => $number,
                ],
                token: $token
            );
        } catch (Throwable $e) {
            throw new ListDocumentEventsException(sprintf(
                'No fue posible listar los eventos del documento T%dF%d de %s en el RCV del SII. %s',
                $document,
                $number,
                $company,
                $e->getMessage()
            ));
        }

        return new ListDocumentEventsResponse(
            $this->xmlService->decode($xmlResponse)
        );
    }
}

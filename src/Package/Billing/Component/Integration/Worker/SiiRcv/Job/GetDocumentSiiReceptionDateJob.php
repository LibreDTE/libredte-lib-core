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
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiRcv\GetDocumentSiiReceptionDateException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\Response\SiiRcv\GetDocumentSiiReceptionDateResponse;
use Throwable;

/**
 * Consulta la fecha de recepción de un DTE en el SII.
 */
#[Job(name: 'get_document_sii_reception_date', worker: 'sii_rcv', component: 'integration', package: 'billing')]
class GetDocumentSiiReceptionDateJob extends AbstractJob implements JobInterface
{
    private const WSDL_SERVICE = 'registroreclamodteservice';

    public function __construct(
        private SiiLazyWorkerInterface $siiLazyWorker,
        private XmlServiceInterface $xmlService
    ) {
    }

    /**
     * Consulta la fecha en que el SII recibió un DTE.
     *
     * @param SiiRequestInterface $request Datos de la solicitud al SII.
     * @param string $company RUT del emisor del DTE (formato RUT-DV).
     * @param int $document Tipo de documento tributario electrónico.
     * @param int $number Folio del documento.
     * @return GetDocumentSiiReceptionDateResponse
     * @throws GetDocumentSiiReceptionDateException En caso de error.
     */
    public function get(
        SiiRequestInterface $request,
        string $company,
        int $document,
        int $number
    ): GetDocumentSiiReceptionDateResponse {
        Rut::validate($company);
        [$rut, $dv] = Rut::toArray($company);

        try {
            $token = $this->siiLazyWorker->authenticate($request);
            $xmlResponse = $this->siiLazyWorker->consumeWebservice(
                request: $request,
                service: self::WSDL_SERVICE,
                function: 'consultarFechaRecepcionSii',
                args: [
                    'rutEmisor' => $rut,
                    'dvEmisor' => $dv,
                    'tipoDoc' => $document,
                    'folio' => $number,
                ],
                token: $token
            );
        } catch (Throwable $e) {
            throw new GetDocumentSiiReceptionDateException(sprintf(
                'No fue posible obtener la fecha de recepción del documento T%dF%d de %s desde el SII. %s',
                $document,
                $number,
                $company,
                $e->getMessage()
            ));
        }

        $response = $this->xmlService->decode($xmlResponse);

        return new GetDocumentSiiReceptionDateResponse($response);
    }
}

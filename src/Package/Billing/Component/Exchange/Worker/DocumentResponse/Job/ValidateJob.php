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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Worker\DocumentResponse\Job;

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\DocumentResponseException;

/**
 * Valida el esquema XSD y la firma electrónica de un documento de respuesta.
 */
#[Job(name: 'validate', worker: 'document_response', component: 'exchange', package: 'billing')]
class ValidateJob extends AbstractJob implements JobInterface
{
    public function __construct(
        private XmlServiceInterface $xmlService,
        private SignatureServiceInterface $signatureService,
    ) {
    }

    /**
     * Valida el esquema XSD y la firma electrónica del documento.
     *
     * @param AbstractExchangeDocument $document Documento a validar.
     * @return bool `true` si el documento es válido.
     * @throws DocumentResponseException Si el esquema o la firma no son válidos.
     */
    public function validate(AbstractExchangeDocument $document): bool
    {
        $schema = dirname(__DIR__, 8) . '/resources/schemas/' . $document->getSchema();

        $xmlStr = $document->getXml();

        // Convertir a XmlDocument para la validación de esquema.
        $xmlDocument = new XmlDocument();
        $xmlDocument->loadXml($xmlStr);

        $this->xmlService->validate($xmlDocument, $schema);

        // Validar la firma. Para EnvioRecibos hay múltiples firmas (una por
        // recibo + una del SetRecibos), se valida solo la del nodo principal.
        $results = $this->signatureService->validateXml($xmlStr);

        if (empty($results)) {
            throw new DocumentResponseException(
                'No se encontró ninguna firma electrónica en el documento.'
            );
        }

        // La última firma corresponde al nodo principal del documento.
        $result = $results[count($results) - 1];

        if (!$result->isValid()) {
            throw new DocumentResponseException(sprintf(
                'La firma electrónica del documento no es válida. %s',
                $result->getError()?->getMessage() ?? ''
            ));
        }

        return true;
    }
}

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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\AbstractExchangeDocument;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\EnvioRecibos;
use libredte\lib\Core\Package\Billing\Component\Exchange\Entity\RespuestaEnvio;
use libredte\lib\Core\Package\Billing\Component\Exchange\Exception\DocumentResponseException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Support\ExchangeDocumentBag;
use NoDiscard;

/**
 * Interfaz para `DocumentResponseWorker`.
 */
interface DocumentResponseWorkerInterface extends WorkerInterface
{
    /**
     * Construye el XML `EnvioRecibos` firmado.
     *
     * Cada `Recibo` se firma individualmente con el ID `LibreDTE_T{tipo}F{folio}`,
     * y luego el `SetRecibos` se firma con ID `LibreDTE_SetDteRecibidos`.
     *
     * @param ExchangeDocumentBag $bag Bolsa con la carátula y los recibos.
     * @return EnvioRecibos
     * @throws DocumentResponseException En caso de error.
     */
    public function buildEnvioRecibos(ExchangeDocumentBag $bag): EnvioRecibos;

    /**
     * Construye el XML `RespuestaDTE` firmado.
     *
     * El nodo `Resultado` se firma con ID `LibreDTE_ResultadoEnvio`.
     * Puede contener `RecepcionEnvio` o `ResultadoDTE` según los datos del bag.
     *
     * @param ExchangeDocumentBag $bag Bolsa con la carátula y las respuestas.
     * @return RespuestaEnvio
     * @throws DocumentResponseException En caso de error.
     */
    public function buildRespuestaEnvio(ExchangeDocumentBag $bag): RespuestaEnvio;

    /**
     * Valida el esquema XSD del documento de respuesta.
     *
     * @param AbstractExchangeDocument|XmlDocumentInterface|string $source
     * @return XmlDocumentInterface El documento XML validado.
     * @throws XmlException Si la validación del esquema falla.
     * @throws DocumentResponseException Si no se puede determinar el esquema.
     */
    #[NoDiscard()]
    public function validateSchema(
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): XmlDocumentInterface;

    /**
     * Valida la(s) firma(s) electrónica(s) del documento de respuesta.
     *
     * Para `EnvioRecibos` hay múltiples firmas (una por recibo más la del
     * `SetRecibos`). Se retornan todos los resultados.
     *
     * @param AbstractExchangeDocument|XmlDocumentInterface|string $source
     * @return array<SignatureValidationResultInterface>
     */
    #[NoDiscard()]
    public function validateSignature(
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): array;
}

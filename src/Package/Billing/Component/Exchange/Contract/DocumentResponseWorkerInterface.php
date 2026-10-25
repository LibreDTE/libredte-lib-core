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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Signature\Contract\SignatureValidationResultInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use libredte\lib\Core\Package\Billing\Component\Exchange\Abstract\AbstractExchangeDocument;
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
     * @return EnvioRecibos El `EnvioRecibos` construido y firmado.
     * @throws DocumentResponseException En caso de error.
     */
    public function buildEnvioRecibos(ExchangeDocumentBag $bag): EnvioRecibos;

    /**
     * Construye el XML `RespuestaDTE` firmado.
     *
     * El nodo `Resultado` se firma con ID `LibreDTE_ResultadoEnvio`.
     * Puede contener `RecepcionEnvio` o `ResultadoDTE` según los datos de la
     * bolsa.
     *
     * @param ExchangeDocumentBag $bag Bolsa con la carátula y las respuestas.
     * @return RespuestaEnvio El `RespuestaDTE` construido y firmado.
     * @throws DocumentResponseException En caso de error.
     */
    public function buildRespuestaEnvio(ExchangeDocumentBag $bag): RespuestaEnvio;

    /**
     * Valida el esquema XSD del documento de respuesta.
     *
     * @param AbstractExchangeDocument|XmlDocumentInterface|string $source
     * Origen a validar: el documento de respuesta ya construido (`EnvioRecibos`
     * o `RespuestaEnvio`), o su XML ya construido (como documento o como
     * string).
     * @return XmlDocumentInterface El documento XML validado.
     * @throws XmlException Si la validación del esquema falla.
     * @throws DocumentResponseException Si no se puede determinar el esquema.
     */
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
     * Origen a validar: el documento de respuesta ya construido (`EnvioRecibos`
     * o `RespuestaEnvio`), o su XML ya construido (como documento o como
     * string).
     * @return array<SignatureValidationResultInterface> El resultado de
     * validar cada firma electrónica encontrada en el documento.
     * @throws SignatureException Si el XML está mal formado o no contiene
     * firmas.
     */
    #[NoDiscard()]
    public function validateSignature(
        AbstractExchangeDocument|XmlDocumentInterface|string $source
    ): array;
}

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

namespace libredte\lib\Core\Package\Billing\Component\Exchange\Enum;

/**
 * Define los tipos de documentos que se pueden intercambiar con LibreDTE.
 *
 * Este enum lista los tipos de documentos que se pueden intercambiar en los
 * sobres. Como un sobre debe contener documentos del mismo tipo, este atributo
 * se asigna al sobre, no al documento.
 *
 * Cada tipo de documento tiene una relación específica con uno o más procesos
 * comerciales. Además, existe una correspondencia con los Documentos
 * Tributarios Electrónicos (DTE) de Chile para facilitar la interoperabilidad.
 *
 * El sistema identifica exclusivamente los documentos generados y enviados por
 * LibreDTE. Esto incluye aquellos necesarios para operaciones comerciales o
 * regulatorias, como cotizaciones (QUOTATION) o facturas electrónicas
 * (INVOICE).
 *
 * Las respuestas por parte de los clientes no son consideradas en este modelo,
 * excepto cuando están reguladas por normativas específicas, como las asociadas
 * a la recepción de facturas y el acuse de recibo de productos o servicios
 * definidos por el SII.
 *
 * Este enfoque simplifica la implementación inicial, asegurando que solo se
 * gestionen los documentos esenciales manejadas por LibreDTE. Respuestas
 * adicionales de clientes, como "QUOTATION_RESPONSE", podrían incorporarse en
 * el futuro si surgen nuevas necesidades o regulaciones.
 *
 * Para la definición de las URN se utilizó RFC 4198.
 *
 * @link https://www.rfc-editor.org/rfc/rfc4198.html
 */
enum DocumentType: string
{
    /**
     * Documento de cotización.
     *
     * Representa una oferta de bienes o servicios proporcionada por un
     * proveedor a un cliente potencial. Usualmente se utiliza en procesos de
     * cotización o abastecimiento.
     */
    case QUOTATION = 'urn:fdc:libredte.cl:2025:doc:quotation';

    /**
     * Propuesta.
     *
     * Documento utilizado en el contexto de licitaciones (tendering) para
     * presentar una oferta formal de bienes o servicios.
     */
    case PROPOSAL = 'urn:fdc:libredte.cl:2025:doc:proposal';

    /**
     * Oferta a licitación.
     *
     * Documento presentado en un proceso de licitación para competir con otros
     * proveedores.
     */
    case BID = 'urn:fdc:libredte.cl:2025:doc:bid';

    /**
     * Contrato.
     *
     * Documento que formaliza los términos y condiciones de un acuerdo entre
     * un proveedor y un cliente.
     */
    case CONTRACT = 'urn:fdc:libredte.cl:2025:doc:contract';

    /**
     * Catálogo.
     *
     * Documento que lista bienes o servicios disponibles para la venta, a
     * menudo usado en procesos de abastecimiento.
     */
    case CATALOGUE = 'urn:fdc:libredte.cl:2025:doc:catalogue';

    /**
     * Orden de compra.
     *
     * Documento mediante el cual un cliente realiza una solicitud formal de
     * bienes o servicios a un proveedor.
     */
    case ORDER = 'urn:fdc:libredte.cl:2025:doc:order';

    /**
     * Aviso de despacho.
     *
     * Indica que los bienes están en tránsito hacia el cliente.
     */
    case DISPATCH_ADVICE = 'urn:fdc:libredte.cl:2025:doc:dispatch_advice';

    /**
     * Nota de entrega.
     *
     * Certifica la recepción de bienes o servicios por parte del cliente.
     */
    case DELIVERY_NOTE = 'urn:fdc:libredte.cl:2025:doc:delivery_note';

    /**
     * Factura.
     *
     * Documento tributario electrónico que detalla una transacción comercial.
     */
    case INVOICE = 'urn:fdc:libredte.cl:2025:doc:invoice';

    /**
     * Nota de crédito.
     *
     * Documento utilizado para ajustar una factura previamente emitida, como
     * devoluciones o descuentos.
     */
    case CREDIT_NOTE = 'urn:fdc:libredte.cl:2025:doc:credit_note';

    /**
     * Nota de débito.
     *
     * Documento utilizado para aumentar el monto de una factura previamente
     * emitida, como cargos adicionales.
     */
    case DEBIT_NOTE = 'urn:fdc:libredte.cl:2025:doc:debit_note';

    /**
     * Respuesta de aplicación.
     *
     * Documento técnico que confirma la aceptación o rechazo de otro documento
     * intercambiado, como una factura.
     */
    case APPLICATION_RESPONSE = 'urn:fdc:libredte.cl:2025:doc:application_response';

    /**
     * Acuse de recibo.
     *
     * Documento que confirma la recepción de bienes o servicios.
     */
    case RECEIPT_ADVICE = 'urn:fdc:libredte.cl:2025:doc:receipt_advice';

    /**
     * Recordatorio.
     *
     * Documento que notifica al cliente sobre una deuda pendiente, como un
     * recordatorio de pago.
     */
    case REMINDER = 'urn:fdc:libredte.cl:2025:doc:reminder';

    /**
     * Ticket de soporte.
     *
     * Documento que representa una interacción relacionada con el soporte al
     * cliente, como un reclamo o una consulta, por un bien o servicio.
     */
    case TICKET = 'urn:fdc:libredte.cl:2025:doc:ticket';

    /**
     * Reporte.
     *
     * Documento utilizado para enviar información estructurada, como informes
     * de actividad o transacciones.
     */
    case REPORT = 'urn:fdc:libredte.cl:2025:doc:report';

    /**
     * Libro de registros contables o tributarios.
     *
     * Documento que recopila información consolidada, como libros de compras,
     * ventas o contabilidad general.
     */
    case BOOK = 'urn:fdc:libredte.cl:2025:doc:book';

    /**
     * Cualquier Documento Tributario Electrónico (DTE), menos boletas.
     *
     * Permite indicar que el documento es cualquier Documento Tributario
     * Electrónico (DTE) definido por el SII, con la excepción de boletas.
     *
     * Esta definición es 100% para Chile.
     */
    case B2B = 'urn:fdc:libredte.cl:2025:doc:b2b';

    /**
     * Boletas.
     *
     * Permite indicar que el documento es una boleta, ya sea afecta o exenta,
     * según la definición del SII.
     *
     * Esta definición es 100% para Chile.
     */
    case B2C = 'urn:fdc:libredte.cl:2025:doc:b2c';

    /**
     * Mapa que define en qué procesos pueden ser usados los documentos.
     *
     * @var array<string, ProcessType[]>
     */
    private const MAP_TO_PROCESSES = [
        self::QUOTATION->value => [ProcessType::QUOTING, ProcessType::SOURCING],
        self::PROPOSAL->value => [ProcessType::QUOTING, ProcessType::TENDERING],
        self::BID->value => [ProcessType::TENDERING],
        self::CONTRACT->value => [ProcessType::CONTRACTING],
        self::CATALOGUE->value => [ProcessType::SOURCING],
        self::ORDER->value => [ProcessType::ORDERING],
        self::DISPATCH_ADVICE->value => [ProcessType::FULFILLMENT],
        self::DELIVERY_NOTE->value => [ProcessType::FULFILLMENT],
        self::INVOICE->value => [ProcessType::BILLING],
        self::CREDIT_NOTE->value => [ProcessType::BILLING],
        self::DEBIT_NOTE->value => [ProcessType::BILLING],
        self::APPLICATION_RESPONSE->value => [ProcessType::BILLING],
        self::RECEIPT_ADVICE->value => [ProcessType::BILLING],
        self::REMINDER->value => [ProcessType::PAYMENT],
        self::TICKET->value => [ProcessType::SUPPORT],
        self::REPORT->value => [ProcessType::REPORTING],
        self::BOOK->value => [ProcessType::REPORTING],
        self::B2B->value => [ProcessType::BILLING, ProcessType::FULFILLMENT, ProcessType::REPORTING],
        self::B2C->value => [ProcessType::BILLING, ProcessType::REPORTING],
    ];

    /**
     * Mapa que relaciona un tipo de documento con un código referencia de
     * máximo 3 caracteres que se puede incluir en la sección de referencias de
     * un Documento Tributario Electrónico (DTE) de Chile.
     *
     * @var array<string, string>
     */
    private const MAP_TO_REFERENCE = [
        self::QUOTATION->value => 'COT',
        self::PROPOSAL->value => 'PRO',
        self::BID->value => '805',
        self::CONTRACT->value => '803',
        self::CATALOGUE->value => 'CAT',
        self::ORDER->value => '801',
        self::DISPATCH_ADVICE->value => '052',
        self::DELIVERY_NOTE->value => 'DEN',
        self::INVOICE->value => 'INV',
        self::CREDIT_NOTE->value => 'CNO',
        self::DEBIT_NOTE->value => 'DNO',
        self::APPLICATION_RESPONSE->value => 'RES',
        self::RECEIPT_ADVICE->value => 'REC',
        self::REMINDER->value => 'PAY',
        self::TICKET->value => 'TIC',
    ];

    /**
     * Mapa que relaciona un Documento Tributario Electrónico (DTE) de Chile con
     * uno de los tipos de documentos definidos acá.
     *
     * @var array<int, DocumentType>
     */
    private const MAP_FROM_DTE = [
        33 => self::INVOICE,
        34 => self::INVOICE,
        39 => self::INVOICE,
        41 => self::INVOICE,
        46 => self::INVOICE,
        52 => self::DISPATCH_ADVICE,
        56 => self::DEBIT_NOTE,
        61 => self::CREDIT_NOTE,
        110 => self::INVOICE,
        111 => self::DEBIT_NOTE,
        112 => self::CREDIT_NOTE,
    ];

    /**
     * Obtiene el ID del tipo de documento.
     *
     * El ID tiene la estructura de una URN según RFC 4198. Ejemplo:
     *
     *   `urn:fdc:libredte.cl:2025:doc:invoice`
     *
     * @return string
     */
    public function getID(): string
    {
        return $this->value;
    }

    /**
     * Verifica si un tipo de documento es válido para un proceso específico.
     *
     * @param ProcessType $process Tipo de proceso a validar.
     * @return bool `true` si el documento es válido para el proceso, `false` en
     * caso contrario.
     */
    public function isValidForProcess(ProcessType $process): bool
    {
        return in_array($process, self::MAP_TO_PROCESSES[$this->value]);
    }

    /**
     * Entrega el código de referencia (`TpoDocRef`), si existe, para ser
     * utilizado en la sección de referencias de un DTE.
     *
     * @return string|null
     */
    public function getReferenceCode(): ?string
    {
        return self::MAP_TO_REFERENCE[$this->value] ?? null;
    }

    /**
     * Intenta obtener un tipo de documento a partir de un código DTE chileno.
     *
     * @param int $code Código del DTE. Ejemplo: 33 para factura electrónica.
     * @return DocumentType|null El tipo de documento correspondiente o `null`
     * si no existe un mapeo.
     */
    public static function tryFromDTE(int $code): ?DocumentType
    {
        return self::MAP_FROM_DTE[$code] ?? null;
    }
}

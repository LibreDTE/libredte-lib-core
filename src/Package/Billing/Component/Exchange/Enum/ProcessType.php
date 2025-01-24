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
 * Define la lista de procesos comerciales para los que se pueden intercambiar
 * documentos.
 *
 * Para la definición de las URN se utilizó RFC 4198.
 *
 * @link https://www.rfc-editor.org/rfc/rfc4198.html
 */
enum ProcessType: string
{
    /**
     * Proceso de cotización.
     *
     * En estricto rigor podría estar dentro de "SOURCING", pero se separa
     * principalmente para distinguir que una cotización se utiliza cuando el
     * cliente está "explorando" y aun no decide.
     *
     * Lo anterior no implica que no pueda existir un documento "QUOTATION" en
     * un proceso de "SOURCING", sin embargo, ahí el significado es otro. Se
     * estaría solicitando la cotización para realizar un pago previo a la
     * facturación o una orden formal. Pero el interés está en "SOURCING" más
     * claro. Hay un compromiso o interés mayor, pero aun no formalizado ni
     * vinculante.
     */
    case QUOTING = 'urn:fdc:libredte.cl:2025:prac:quoting';

    /**
     * Proceso de licitación.
     *
     * Se refiere al proceso mediante el cual un comprador solicita propuestas
     * de proveedores para adquirir bienes o servicios. Este proceso suele
     * incluir la emisión de especificaciones, la recepción de ofertas, y la
     * evaluación de las mismas para seleccionar al proveedor más adecuado.
     *
     * En este contexto, se intercambian documentos como "PROPOSAL" o "BID".
     */
    case TENDERING = 'urn:fdc:libredte.cl:2025:prac:tendering';

    /**
     * Proceso de contratación.
     *
     * Representa la formalización de un acuerdo entre un comprador y un
     * proveedor para la provisión de bienes o servicios, típicamente como
     * resultado de un proceso de licitación o negociación previa. Este proceso
     * establece los términos y condiciones que regirán las transacciones
     * futuras.
     *
     * En este contexto, se intercambian documentos como "CONTRACT".
     */
    case CONTRACTING = 'urn:fdc:libredte.cl:2025:prac:contracting';

    /**
     * Proceso de abastecimiento.
     *
     * Representa la etapa en la que el cliente, con un interés mayor pero aún
     * sin compromiso vinculante, explora opciones con el proveedor. Esto puede
     * incluir la solicitud de un "QUOTATION" para realizar un pago previo o un
     * "CATALOGUE" para revisar productos disponibles y elegir.
     *
     * Este proceso no implica necesariamente una compra, pero denota una
     * intención más seria.
     */
    case SOURCING = 'urn:fdc:libredte.cl:2025:poacc:sourcing';

    /**
     * Proceso de orden de compra.
     *
     * Es cuando el cliente solicita formalmente, y de manera vinculante, algo
     * al proveedor (productos o servicios). Con esto el proveedor empezará a
     * preparar los productos o prestar los servicios.
     *
     * Acá el documento importante es "ORDER".
     */
    case ORDERING = 'urn:fdc:libredte.cl:2025:poacc:ordering';

    /**
     * Proceso de cumplimiento de órdenes.
     *
     * Representa la etapa en la que el proveedor entrega los bienes o presta
     * los servicios solicitados en la orden de compra ("ORDER"). Puede incluir
     * el intercambio de documentos como "DISPATCH_ADVICE" para confirmar el
     * envío, o un "DELIVERY_NOTE" para certificar la recepción.
     *
     * Este proceso asegura que las obligaciones acordadas en el proceso de
     * "ORDERING" se cumplan correctamente.
     */
    case FULFILLMENT = 'urn:fdc:libredte.cl:2025:poacc:fulfillment';

    /**
     * Proceso de facturación.
     *
     * Acá se intercambian los documentos tributarios electrónicos, por ejemplo
     * una "INVOICE" y los documentos asociados a la recepción, por ejemplo un
     * "RECEIPT_ADVICE".
     */
    case BILLING = 'urn:fdc:libredte.cl:2025:poacc:billing';

    /**
     * Proceso de pago.
     *
     * Incluye el intercambio de documentos relacionados con los pagos, como
     * facturas "pagables" ("INVOICE") o recordatorios de pago ("REMINDER").
     * Este proceso asegura la gestión adecuada de las transacciones financieras
     * asociadas a los acuerdos comerciales.
     */
    case PAYMENT = 'urn:fdc:libredte.cl:2025:poacc:payment';

    /**
     * Proceso de soporte al cliente.
     *
     * Representa el conjunto de interacciones y actividades destinadas a
     * brindar asistencia a los clientes en relación con productos o servicios
     * adquiridos o en consideración. Este proceso puede incluir consultas,
     * resolución de problemas, gestión de reclamos y solicitudes de garantía.
     *
     * En este contexto, se intercambian documentos como "TICKET".
     */
    case SUPPORT = 'urn:fdc:libredte.cl:2025:poacc:support';

    /**
     * Proceso de reportes para control continuo de transacciones.
     *
     * Representa el intercambio de información tributaria en tiempo real con la
     * entidad fiscal, en el contexto del control continuo de transacciones
     * (CTC). Este proceso puede incluir el envío directo de una "INVOICE" en un
     * documento de tipo "REPORT" u otros reportes requeridos por la autoridad
     * fiscal para garantizar el cumplimiento normativo como un "BOOK".
     *
     * Este proceso puede incluir el envío de libros tributarios, reportes de
     * ventas o cualquier documento exigido por la entidad fiscal.
     */
    case REPORTING = 'urn:fdc:libredte.cl:2025:ctc:reporting';

    /**
     * Obtiene el ID del tipo de proceso.
     *
     * El ID tiene la estructura de una URN según RFC 4198. Ejemplo:
     *
     *   `urn:fdc:libredte.cl:2025:poacc:billing`
     *
     * @return string
     */
    public function getID(): string
    {
        return $this->value;
    }
}

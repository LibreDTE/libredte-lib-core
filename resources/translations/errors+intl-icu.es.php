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

/**
 * Spanish (ICU) translations for translatable exception messages.
 *
 * Keys are the canonical English messages used in throw statements.
 */
return [
    'A digital certificate must be provided to authenticate with the SII.' =>
        'Para autenticar en el SII se debe proveer un certificado digital.',
    'Action "{action}" is not valid. Valid actions are: {valid}.' =>
        'La acción "{action}" no es válida. Las acciones válidas son: {valid}.',
    'An error occurred while creating the SOAP client for the SII API with WSDL {wsdl}. {detail}' =>
        'Ocurrió un error al crear el cliente SOAP para la API del SII con el WSDL {wsdl}. {detail}',
    'An error occurred with status code "{status}", which is unknown to LibreDTE. {detail}' =>
        'Ocurrió un error con código de estado "{status}", que es desconocido por LibreDTE. {detail}',
    'At least one document must be specified to create the envelope.' =>
        'Se debe indicar al menos un documento para crear el sobre.',
    'Attention number format "{input}" is incorrect.' =>
        'El formato del número de atención "{input}" es incorrecto.',
    'Authentication error: invalid, missing or expired TOKEN.' =>
        'Error de autenticación: TOKEN inválido, no existe o está expirado.',
    'CAF {caf} containing document number {folio} of document {document} is not valid; it expired on {date}.' =>
        'El CAF {caf} que contiene el folio {folio} del documento {document} no está vigente, venció el día {date}.',
    'CAF {id} failed private key validation (possible corrupt CAF file).' =>
        'El CAF {id} no pasó la validación de su clave privada (posible archivo CAF corrupto).',
    'CAF {id} failed public key validation (possible corrupt CAF file).' =>
        'El CAF {id} no pasó la validación de su clave pública (posible archivo CAF corrupto).',
    'CAF {id} failed to encrypt and decrypt a test string correctly (possible corrupt CAF file).' =>
        'El CAF {id} no logró encriptar y desencriptar correctamente un texto de prueba (posible archivo CAF corrupto).',
    'CAF {id} signature is not valid (the CAF is not authorized by the SII).' =>
        'La firma del CAF {id} no es válida (el CAF no está autorizado por el SII).',
    'CAFs of type {type} must be added before calling setAvailable().' =>
        'Debe agregar CAFs del tipo {type} antes de llamar a setAvailable().',
    'Cannot add new documents to envelope {envelope}; the limit is {max} documents per envelope.' =>
        'No es posible agregar nuevos documentos al sobre {envelope}, el límite es de {max} documentos por sobre.',
    'Cannot normalize without a TipoDocumento in the $bag.' =>
        'No es posible normalizar sin un TipoDocumento en la $bag.',
    'Cannot represent the render result as a single text string: it contains {count} file(s), exactly 1 was expected.' =>
        'No es posible representar el resultado del renderizado como una sola cadena de texto: contiene {count} archivo(s), se esperaba exactamente 1.',
    'Cannot sanitize without a TipoDocumento in the $bag.' =>
        'No es posible sanitizar sin un TipoDocumento en la $bag.',
    'Cannot validate without a TipoDocumento in the $bag.' =>
        'No es posible validar sin un TipoDocumento en la $bag.',
    'Circular dependency between examples: {path} -> {id}.' =>
        'Dependencia circular entre ejemplos: {path} -> {id}.',
    'Code {code} of the XmlDocument loaded in {class} does not match the expected value; it should be code {expected}.' =>
        'El código {code} del XmlDocument cargado en {class} no corresponde al esperado; debería ser el código {expected}.',
    'Could not build EnvioRecibos: {detail}' =>
        'No fue posible construir el EnvioRecibos: {detail}',
    'Could not build RespuestaDTE: {detail}' =>
        'No fue posible construir el RespuestaDTE: {detail}',
    'Could not build the AEC: {detail}' =>
        'No fue posible construir el AEC: {detail}',
    'Could not build the book "{type}": {detail}' =>
        'No fue posible construir el libro "{type}": {detail}',
    'Could not generate any rendering with the requested presentations.' =>
        'No fue posible generar ninguna renderización con las presentaciones solicitadas.',
    'Could not list events for document type {type} with number {folio} for {company} in the SII RCV. {detail}' =>
        'No fue posible listar los eventos del documento T{type}F{folio} de {company} en el RCV del SII. {detail}',
    'Could not load the book "{type}" from format "{format}": {detail}' =>
        'No fue posible cargar el libro "{type}" desde formato "{format}": {detail}',
    'Could not obtain SII certificate {file} to validate the CAF.' =>
        'No fue posible obtener el certificado del SII {file} para validar el CAF.',
    'Could not obtain signature status for document type {type} with number {folio} for {rut}-{dv} from the SII. {detail}' =>
        'No fue posible obtener el estado de la firma del documento T{type}F{folio} de {rut}-{dv} desde el SII. {detail}',
    'Could not obtain status for document type {type} with number {folio} for {rut}-{dv} from the SII. {detail}' =>
        'No fue posible obtener el estado del documento T{type}F{folio} de {rut}-{dv} desde el SII. {detail}',
    'Could not obtain the CAF {id} authorization date.' =>
        'No fue posible obtener la fecha de autorización del CAF {id}.',
    'Could not obtain the SII reception date for document type {type} with number {folio} for {company}. {detail}' =>
        'No fue posible obtener la fecha de recepción del documento T{type}F{folio} de {company} desde el SII. {detail}',
    'Could not obtain the seed.' =>
        'No fue posible obtener la semilla.',
    'Could not obtain the status of the XML sent to the SII with Track ID {trackId}. {detail}' =>
        'No fue posible obtener el estado del XML enviado al SII con Track ID {trackId}. {detail}',
    'Could not obtain the token to authenticate user {user} with the SII. {detail}' =>
        'No fue posible obtener el token para autenticar en el SII al usuario {user}. {detail}',
    'Could not interpret the document: {detail}' =>
        'No fue posible interpretar el documento: {detail}',
    'Could not query assignability of document type {type} with number {folio} for {company} in the SII RCV. {detail}' =>
        'No fue posible consultar la cesibilidad del documento T{type}F{folio} de {company} en el RCV del SII. {detail}',
    'Could not read file {file}.' =>
        'No fue posible leer el archivo {file}.',
    'Could not request the email with upload status {trackId} from the SII. {detail}' =>
        'No fue posible solicitar el correo con el estado del envío {trackId} al SII. {detail}',
    'Could not resolve the repository: {detail}' =>
        'No fue posible resolver el repositorio: {detail}',
    'Could not retrieve VAT for document type {type}.' =>
        'No se pudo recuperar el IVA para el documento {type}.',
    'Could not sign getToken. {detail}' =>
        'No fue posible firmar getToken. {detail}',
    'Could not stamp the data.' =>
        'No fue posible timbrar los datos.',
    'Could not submit action "{action}" for document type {type} with number {folio} for {company} in the SII RCV. {detail}' =>
        'No fue posible ingresar la acción "{action}" para el documento T{type}F{folio} de {company} en el RCV del SII. {detail}',
    'Currency type {currency} is not allowed; only USD, EUR and CLP are allowed.' =>
        'El tipo de moneda {currency} no está permitido, solo: USD, EUR y CLP.',
    'Digital certificate for {id} is not valid at {time}; its validity range is from {from} to {to}.' =>
        'El certificado digital de {id} no está vigente en el tiempo {time}, su rango de vigencia es del {from} al {to}.',
    'Direct access to the preview is not allowed.' =>
        'No puede acceder de forma directa a la previsualización.',
    'Could not process the document batch: {detail}' =>
        'No fue posible procesar el lote de documentos: {detail}',
    'Document date {date} is not valid; it must be in YYYY-MM-DD format.' =>
        'La fecha {date} del documento no es válida, debe tener formato AAAA-MM-DD.',
    'Document type code {code} was not found to process the CAF.' =>
        'No se encontró el código de documento {code} para procesar el CAF.',
    'Document type {documentType} cannot be added to an envelope of type {envelopeType}.' =>
        'El tipo de documento {documentType} no se puede agregar a un sobre de tipo {envelopeType}.',
    'Document type {documentType} does not match the document types this envelope can contain {allowed}.' =>
        'El tipo del documento {documentType} no coincide con los tipos de documento que el sobre puede contener {allowed}.',
    'Documents cannot be added to the envelope after the cover has been generated.' =>
        'No es posible agregar documentos al sobre cuando la carátula ya fue generada.',
    'Either batch content (inputData) or path (inputFile) must be specified.' =>
        'Se debe indicar el contenido (inputData) o la ruta (inputFile) del lote.',
    'Email address {email} is not valid.' =>
        'Correo electrónico {email} no es válido.',
    'Must indicate the field {field}.' =>
        'Falta indicar el campo {field}.',
    'Example "{id}" does not exist (referenced in DependsOn).' =>
        'El ejemplo "{id}" no existe (referenciado en DependsOn).',
    'Example "{id}" does not exist.' =>
        'El ejemplo "{id}" no existe.',
    'Could not receive the exchange: {detail}' =>
        'No fue posible recibir el intercambio: {detail}',
    'Could not send the exchange: {detail}' =>
        'No fue posible enviar el intercambio: {detail}',
    'Failed to send the AEC to the SII. {detail}' =>
        'Falló el envío del AEC al SII. {detail}',
    'Failed to send the XML to the SII. {detail}' =>
        'Falló el envío del XML al SII. {detail}',
    'File {file} has an invalid extension. Fix the extension or specify $contentType.' =>
        'El archivo {file} tiene una extensión inválida. Corrige la extensión o especifica el $contentType.',
    'Document number {folio} for document type {type} is duplicated in the file.' =>
        'El folio {folio} del tipo de documento {type} está repetido en el archivo.',
    'Document number {folio} of document {document} is not available in CAF {caf} range.' =>
        'El folio {folio} del documento {document} no está disponible en el rango del CAF {caf}.',
    'IMAP username and password must be specified.' =>
        'Se debe especificar el usuario y la contraseña de IMAP.',
    'Internal error on SII servers.' =>
        'Error interno en los servidores del SII.',
    'It is not possible to generate a document with a global amount discount that has both taxable and exempt items through LibreDTE. This use case is not supported.' =>
        'No es posible generar un documento con un descuento global en monto que tenga ítems afectos y exentos mediante LibreDTE. Este es un caso de uso no considerado.',
    'It is not possible to generate a receipt with additional taxes through LibreDTE. This use case is not supported.' =>
        'No es posible generar una boleta que tenga impuestos adicionales mediante LibreDTE. Este es un caso de uso no considerado.',
    'Item {line} has IndExe = {indExe}, which is not supported (only 1 and 2 are supported).' =>
        'El ítem {line} tiene IndExe = {indExe}, que no está soportado (solo se soportan 1 y 2).',
    'Missing document number.' =>
        'Falta el folio del documento.',
    'Missing document type.' =>
        'Falta el tipo de documento.',
    'Missing driver RUT.' =>
        'Falta el RUT del chofer.',
    'Missing driver name.' =>
        'Falta el nombre del chofer.',
    'Missing input parameters in the RTC request.' =>
        'Faltan parámetros de entrada en la solicitud al RTC.',
    'Missing item name.' =>
        'Falta el nombre del ítem.',
    'Missing item price.' =>
        'Falta el precio del ítem.',
    'Missing item quantity.' =>
        'Falta la cantidad del ítem.',
    'Missing recipient RUT.' =>
        'Falta el RUT del receptor.',
    'Missing recipient address.' =>
        'Falta la dirección del receptor.',
    'Missing recipient business activity.' =>
        'Falta el giro del receptor.',
    'Missing recipient business name.' =>
        'Falta la razón social del receptor.',
    'Missing recipient commune.' =>
        'Falta la comuna del receptor.',
    'More document types were added than allowed in the envelope ({max}).' =>
        'Se agregaron más tipos de documentos de los que son permitidos en el sobre ({max}).',
    'No CAF was found for type {type} with document number from {from}.' =>
        'No se encontró un CAF para el tipo {type} con folio desde {from}.',
    'No CAF was found for type {type} with document number {folio}.' =>
        'No se encontró un CAF para el tipo {type} con el folio {folio}.',
    'No CAFs are loaded for document type {type}.' =>
        'No hay CAFs cargados para el tipo de documento {type}.',
    'No available document number was found for document type {type}.' =>
        'No se encontró folio disponible para el tipo de documento {type}.',
    'No digital certificate was provided for the document status query.' =>
        'No se ha proporcionado un certificado digital para la consulta de estado del documento.',
    'No document numbers are available for document type {type}.' =>
        'No hay folios disponibles para el tipo de documento {type}.',
    'No response was received from SII SOAP function {function} after {attempts} attempts. {detail}' =>
        'No se obtuvo respuesta de la función {function} del servicio web SOAP del SII después de {attempts} intentos. {detail}',
    'No valid tax document code was found in the DTE data.' =>
        'No se encontró un código de documento tributario válido en los datos del DTE.',
    'Only one of batch content (inputData) or path (inputFile) must be specified.' =>
        'Se debe indicar solo uno: el contenido (inputData) o la ruta (inputFile) del lote.',
    'Payment method code {code} is incorrect; it must be 1 (cash), 2 (credit) or 3 (no cost).' =>
        'La forma de pago de código {code} es incorrecta; debe ser: 1 (contado), 2 (crédito) o 3 (sin costo).',
    'Rendering did not produce any file with presentation "{presentation}".' =>
        'El renderizado no generó ningún archivo con la presentación "{presentation}".',
    'Could not render the document: {detail}' =>
        'No fue posible renderizar el documento: {detail}',
    'Rendering presentation "{presentation}" does not exist.' =>
        'La presentación de renderizado "{presentation}" no existe.',
    'Root element "{root}" is not recognized as a response document.' =>
        'No se reconoce el elemento raíz "{root}" como documento de respuesta.',
    'Row {row}: {detail}' =>
        'Fila {row}: {detail}',
    'SII servers are experiencing internal problems. {detail}' =>
        'Los servidores del SII están con problemas internos. {detail}',
    'SMTP username and password must be specified.' =>
        'Se debe especificar el usuario y la contraseña de SMTP.',
    'The AEC XML to send to the SII cannot be empty.' =>
        'El XML del AEC que se desea enviar al SII no puede estar vacío.',
    'The CAF XML does not include an assigned IDK.' =>
        'El CAF no tiene un IDK asignado en el XML.',
    'The CAF XML does not include an assigned authorization date.' =>
        'El CAF no tiene una fecha de autorización asignada en el XML.',
    'The CAF XML does not include an assigned authorization.' =>
        'El CAF no tiene una autorización asignada en el XML.',
    'The CAF XML does not include an assigned document type.' =>
        'El CAF no tiene un tipo de documento asignado en el XML.',
    'The CAF XML does not include an assigned starting document number.' =>
        'El CAF no tiene un folio desde asignado en el XML.',
    'The CAF XML does not include an assigned ending document number.' =>
        'El CAF no tiene un folio hasta asignado en el XML.',
    'The CAF XML does not include an assigned issuer RUT.' =>
        'El CAF no tiene un RUT de emisor asignado en el XML.',
    'The CAF XML does not include an assigned issuer business name.' =>
        'El CAF no tiene una razón social de emisor asignada en el XML.',
    'The CAF XML does not include an assigned private key.' =>
        'El CAF no tiene una clave privada asignada en el XML.',
    'The CAF XML does not include an assigned public key.' =>
        'El CAF no tiene una clave pública asignada en el XML.',
    'The CAF XML does not include an assigned signature.' =>
        'El CAF no tiene una firma asignada en el XML.',
    'The SII AEC upload response does not include a valid status code.' =>
        'La respuesta del envío del AEC al SII no trae un código de estado válido.',
    'The SII XML upload response does not include a valid status code.' =>
        'La respuesta del envío del XML al SII no trae un código de estado válido.',
    'The SII has no reception date on record for the queried document.' =>
        'El SII no registra fecha de recepción para el documento consultado.',
    'The SII rejected the AEC with unknown status code "{status}".' =>
        'El SII rechazó el AEC con el código de estado desconocido "{status}".',
    'The SII response does not include the expected headers.' =>
        'La respuesta del SII no incluye las cabeceras esperadas.',
    'The XML schema is invalid. {detail}' =>
        'El esquema del XML es inválido. {detail}',
    'The XML sent was already received by the SII. {detail}' =>
        'El XML enviado ya fue previamente recibido por el SII. {detail}',
    'The XML to send to the SII cannot be empty.' =>
        'El XML que se desea enviar al SII no puede estar vacío.',
    'The agent run, name and email attributes are required.' =>
        'Los atributos run, nombre y email del mandatario son obligatorios.',
    'The authenticated RUT has no permission to send on behalf of the assigning company.' =>
        'El RUT autenticado no tiene permiso para enviar en la empresa cedente.',
    'The book XML does not declare the xsi:schemaLocation attribute.' =>
        'El XML del libro no declara el atributo xsi:schemaLocation.',
    'The book type is not defined in class "{class}".' =>
        'El tipo de libro no está definido en la clase "{class}".',
    'The company is not authorized as a DTE issuer.' =>
        'La empresa no está autorizada como emisor de DTE.',
    'The company is not authorized to send XML files to the SII. {detail}' =>
        'La empresa no está autorizada a enviar archivos XML al SII. {detail}',
    'The document XML does not include an assigned document number.' =>
        'El documento no tiene un folio asignado en el XML.',
    'The document XML does not include an assigned issue date.' =>
        'El documento no tiene una fecha de emisión asignada en el XML.',
    'The document XML does not include an assigned issuer RUT.' =>
        'El documento no tiene un RUT de emisor asignado en el XML.',
    'The document XML does not include an assigned issuer.' =>
        'El documento no tiene un emisor asignado en el XML.',
    'The document XML does not include an assigned recipient RUT.' =>
        'El documento no tiene un RUT de receptor asignado en el XML.',
    'The document XML does not include an assigned recipient.' =>
        'El documento no tiene un receptor asignado en el XML.',
    'The document XML does not include an assigned total amount.' =>
        'El documento no tiene un monto total asignado en el XML.',
    'The document XML does not include an exchange rate assigned for currency {currency}.' =>
        'El documento no tiene un tipo de cambio asignado para la moneda {currency} en el XML.',
    'The document XML does not include assigned totals.' =>
        'El documento no tiene los totales asignados en el XML.',
    'The document XML root node must be the "DTE" tag. Inside this root node there must be a "Documento", "Exportaciones" or "Liquidacion" tag. That inner node must contain the document data.' =>
        'El nodo raíz del XML del documento debe ser el tag "DTE". Dentro de este nodo raíz debe existir un tag "Documento", "Exportaciones" o "Liquidacion". Este segundo nodo es el que debe contener los datos del documento.',
    'The document detail (Detalle) must be indicated: there must be at least one item.' =>
        'Falta indicar el detalle del documento (Detalle): debe tener al menos un ítem.',
    'The document number must be an integer greater than 0.' =>
        'El folio del documento debe ser un número entero mayor a 0.',
    'Must indicate the document number.' =>
        'Falta indicar el folio del documento.',
    'The document type (TipoDTE) must be indicated in the DTE data.' =>
        'Falta indicar el tipo de documento (TipoDTE) en los datos del DTE.',
    'Must indicate the document type to issue.' =>
        'Falta indicar el tipo de documento a emitir.',
    'The envelope cover is not defined.' =>
        'La carátula del sobre no está definida.',
    'The envelope to send has no documents.' =>
        'El sobre que se desea enviar no posee documentos.',
    'The exchange bag to send has no envelopes.' =>
        'La bolsa de intercambio que se desea enviar no posee sobres.',
    'The document number cannot be assigned after the XML document has been generated.' =>
        'No es posible asignar el folio si ya se generó el documento XML.',
    'The document number cannot be assigned when there are no parsed or normalized data.' =>
        'No es posible asignar el folio si no existen datos parseados o normalizados.',
    'The input data is not valid JSON.' =>
        'Los datos de entrada no son un JSON válido.',
    'The item quantity cannot be 0 when the price is not 0.' =>
        'La cantidad del ítem no puede ser 0 si el precio es distinto de 0.',
    'The operation type "{operation}" is not valid.' =>
        'El tipo de operación "{operation}" no es válido.',
    'The provided YAML is invalid: {detail}' =>
        'El YAML proporcionado es inválido: {detail}',
    'The recipient must include at least one identifier with EMAIL scheme.' =>
        'Se debe especificar al menos un identificador con esquema EMAIL en el destinatario.',
    'The reference document date must be in YYYY-MM-DD format.' =>
        'La fecha del documento de referencia debe ser en formato AAAA-MM-DD.',
    'The reference document number cannot be empty.' =>
        'El folio del documento de referencia no puede estar vacío.',
    'The reference document type cannot be empty.' =>
        'El tipo del documento de referencia no puede estar vacío.',
    'The row includes an item or a reference, but there is no previous document to attach them to.' =>
        'La fila trae un ítem o una referencia, pero no hay un documento anterior al que agregarlos.',
    'The sender must include an identifier with EMAIL scheme.' =>
        'Se debe especificar el identificador con esquema EMAIL en el remitente.',
    'The shipping envelope XML does not include an assigned agent RUN.' =>
        'El sobre de envío no tiene un RUN de mandatario asignado en el XML.',
    'The shipping envelope XML does not include an assigned issuer RUT.' =>
        'El sobre de envío no tiene un RUT de emisor asignado en el XML.',
    'The shipping envelope XML does not include an assigned recipient RUT.' =>
        'El sobre de envío no tiene un RUT de receptor asignado en el XML.',
    'The shipping envelope XML does not include an assigned resolution date.' =>
        'El sobre de envío no tiene una fecha de resolución asignada en el XML.',
    'The shipping envelope XML does not include an assigned resolution number.' =>
        'El sobre de envío no tiene un número de resolución asignado en el XML.',
    'The shipping envelope XML does not include an assigned signature date.' =>
        'El sobre de envío no tiene una fecha de firma asignada en el XML.',
    'The shipping envelope XML does not include an assigned summary.' =>
        'El sobre de envío no tiene un resumen asignado en el XML.',
    'The sii.boleta send strategy is not implemented.' =>
        'La estrategia de envío sii.boleta no está implementada.',
    'The sii.dte send strategy is not implemented.' =>
        'La estrategia de envío sii.dte no está implementada.',
    'The token key must allow assigning the ID associated with the token.' =>
        'La clave del token debe permitir asignar el ID asociado al token.',
    'There is an error in the XML document signature. {detail}' =>
        'Existe un error en la firma del documento XML. {detail}',
    'There is no digital certificate associated with the request.' =>
        'No hay certificado digital asociado a la solicitud.',
    'Uploaded XML file size error: too large or too small. {detail}' =>
        'Error en el tamaño del archivo enviado con el XML, muy grande o muy chico. {detail}',
    'Uploaded file is truncated; size differs from the "size" parameter. {detail}' =>
        'El archivo enviado está cortado, el tamaño es diferente al parámetro "size". {detail}',
    'Uploaded file size error.' =>
        'Error en el tamaño del archivo enviado.',
    'User {user} does not have permission to send XML to the SII. {detail}' =>
        'El usuario {user} no tiene permisos para enviar XML al SII. {detail}',
    'User {user} is not authenticated (token may be expired). {detail}' =>
        'El usuario {user} no está autenticado (posible token expirado). {detail}',
    'When creating a CAF, the starting document number ({from}) cannot be greater than the ending document number ({to}).' =>
        'Al crear un CAF el folio desde ({from}) no puede ser mayor que el folio hasta ({to}).',
];

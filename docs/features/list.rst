Funcionalidades y características
=================================

1. **Documentos XML**

   1. ✓ Clase `XmlDocument` que extiende `DomDocument` para proveer funcionalidades adicionales.

   2. ✓ Clase `XmlConverter` para manejar la conversión desde:

      1. ✓ Arreglo PHP a string XML (en `XmlDocument`).
      2. ✓ String XML (en `XmlDocument`) a Arreglo PHP.

   3. ✓ Clase `XmlValidator` para la validación del esquema del XML.

2. **Firma Electrónica**

   1. ✓ Clase `Certificate` para representar un certificado digital y sus atributos. Permite obtener la clave pública y privada, junto con otros datos del certificado como el nombre de la persona asociada, su ID (RUN) o el período de validez del certificado.

   2. ✓ Clase `CertificateFaker` para generar certificados falsos, para pruebas o demostraciones.

   3. ✓ Clases `SignatureGenerator`, `SignatureValidator` y `XmlSignatureNode` para lo relacionado con XML DSIG, incluyendo los ajustes requeridos por el SII.

3. **Cliente HTTP y API SOAP SII**

   1. ✓ Clase `WsdlConsumer` para el consumo de servicios web (API) SOAP mediante WSDL.

   2. ✓ Clase `TokenManager` para la gestión del ciclo de vida del token de sesión de los servicios web del SII.

   3. ✓ Clase `DocumentUploader` para enviar un XML de un DTE al SII y obtener su número de seguimiento o Track ID.

   4. ✓ Clase `DocumentValidator` que permite interactuar con la API para:

      1. ✓ Consultar el estado del envío de un XML al SII, sin los detalles en caso de problemas.

      2. ✓ Solicitar el correo con el estado del envío de un XML al SII, con los detalles en caso de problemas.

      3. ✓ Verificar la validez de un DTE enviando ciertos datos al SII (como su folio o total) y corroborar que es un DTE legalmente emitido. También es posible realizar la verificación avanzada y consultar al SII si la firma electrónica del DTE es la que el SII recibió.

4. **Autorización de Folios (archivos CAF)**

   1. ✓ Clase `Caf` para representar un XML de un CAF solicitado al SII y sus atributos. Permite obtener la clave pública y privada, junto con otros datos del CAF como el tipo de documento, rango de folios o su período de validez.

   2. ✓ Clase `CafFaker` para generar archivos CAF falsos, para pruebas o demostraciones.

5. **Documentos Tributarios Electrónicos (DTE)**

   1. ✓ Constructores, o *builders*, de documentos tributarios que tienen una responsabilidad: normalizar los datos de un DTE. Existe un constructor, o *builder*, por cada tipo de documento. Estos realizan las estandarizaciones de campos y datos para luego generar el XML. Las normalizaciones de los constructores son apoyadas por diferentes *traits* que se pueden encontrar en el directorio `Normalization`.

   2. ✓ Clases de documentos tributarios para representar un DTE específico, que se identifica de manera única por su tipo y folio. Estas clases heredan de `AbstractDocumento`.

   3. ✓ Clase `AbstractDocumento` que contiene la base de las clases del punto previo. Esta clase abstracta tiene los métodos comunes, por ejemplo, los métodos necesarios para timbrar, firmar un DTE, validar el timbre, la firma o el esquema del XML.

   4. ✓ Clase `DocumentoTipo` representa un tipo de DTE, por ejemplo, "Factura Afecta". Esta clase es la responsable de entregar la información relacionada a un tipo de documento, pero no a uno de un folio específico.

   5. ✓ Clase `SobreEnvio` para representar un grupo de documentos tributarios. En estricto rigor representa al tag XML `EnvioDTE` o `EnvioBOLETA`. Tiene métodos necesarios para firmar el envío, validar su firma y validar el esquema del XML del envío.

6. **Impuestos adicionales y retenciones**

   1. ✓ Existe soporte para todos los impuestos adicionales y retenciones, excepto los asociados a combustibles.

7. **PDF del DTE**

   1. ☐ A partir del XML del DTE se puede generar un PDF en diferentes formatos.

   2. ☐ Formatos disponibles:

      1. ☐ Estándar: con tamaño de hoja carta y papel contínuo.

8. **Proceso de Intercambio de DTE**

   1. ☐ Generación de los archivos XML de respuesta para enviar con:

      1. ☐ Acuse de recibo.

      2. ☐ Recibo de mercaderías y servicios prestados.

      3. ☐ Resultado validación.

9. **Registro de Compra y Venta (RCV)**

   1. ☐ Se permite realizar las siguientes acciones mediante la API del SII:

      1. ☐ Ingresar aceptación o reclamo.

      2. ☐ Listar eventos del DTE.

      3. ☐ Consultar posibilidad de cesión del DTE.

      4. ☐ Consultar fecha de recepción en SII del DTE.

10. **Libros y registros**

    1. ☐ Libros de compras y ventas (IECV). Incluye generación en PDF.

    2. ☐ Libro de guías de despacho.

    3. ☐ Libro de boletas.

    4. ☐ Reporte de Ventas Diarias (RVD) o Reporte de Consumo de Folios (RCOF).

11. **Cesión Electrónica (*factoring*)**

    1. ☐ Generación del AEC.

    2. ☐ Envío del AEC al Registro de Transferencias de Créditos (RTC) del SII.

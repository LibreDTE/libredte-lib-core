# LibreDTE: Biblioteca Estándar en PHP (Núcleo)

[![CI Workflow](https://github.com/LibreDTE/libredte-lib/actions/workflows/ci.yml/badge.svg?branch=libredte_lib_2024&event=push)](https://github.com/LibreDTE/libredte-lib/actions/workflows/ci.yml?query=branch%3Alibredte_lib_2024)
[![Descargas Totales](https://poser.pugx.org/libredte/libredte-lib/downloads)](https://packagist.org/packages/libredte/libredte-lib)
[![Descargas Mensuales](https://poser.pugx.org/libredte/libredte-lib/d/monthly)](https://packagist.org/packages/libredte/libredte-lib)
[![Licencia](https://poser.pugx.org/libredte/libredte-lib/license)](https://packagist.org/packages/libredte/libredte-lib)

LibreDTE es un proyecto que tiene por objetivo proveer Facturación Electrónica Libre para Chile.

Aquí podrás encontrar la biblioteca escrita en lenguaje PHP para la integración con el Servicio de Impuestos Internos (SII) asociada a los Documentos Tributarios Electrónicos (DTE).

Esta biblioteca se construye, originalmente, para ser usada en la [Aplicación Web de LibreDTE Edición Comunidad](https://github.com/LibreDTE/libredte-app-community). En esta biblioteca solo estará lo básico, sin interfaces de usuario, para que un desarrollador pueda construir su propia aplicación.

Si deseas una aplicación "llegar y usar" sin preocuparte por instalaciones o servidores, revisa la [Aplicación Web de LibreDTE Edición Enterprise](https://www.libredte.cl). Podrás registrarte y usar la plataforma web de facturación junto a otros módulos.

## Términos y condiciones de uso

Al utilizar este proyecto, total o parcialmente, automáticamente se acepta cumplir con los [términos y condiciones de uso](https://www.libredte.cl/legal) que rigen a LibreDTE. La [Licencia Pública General Affero de GNU (AGPL)](https://raw.githubusercontent.com/LibreDTE/libredte-lib/master/COPYING) solo aplica para quienes respeten los términos y condiciones de uso. No existe una licencia comercial de LibreDTE, por lo cual no es posible usar el proyecto si no aceptas cumplir dichos términos y condiciones.

La versión resumida de los términos y condiciones de uso de LibreDTE que permiten utilizar el proyecto, son los siguientes:

1. Tienes la libertad de: usar, estudiar, distribuir y cambiar LibreDTE.
2. Si utilizas LibreDTE en tu software, el código fuente de dicho software deberá ser liberado de manera pública bajo licencia AGPL.
3. Si haces cambios a LibreDTE deberás liberar de manera pública el código fuente de dichos cambios bajo licencia AGPL.
4. Debes hacer referencia de manera pública en tu software al proyecto y autor original de LibreDTE, tanto si usas LibreDTE sin modificar o realizando cambios al código.

Es obligación de quienes quieran usar el proyecto leer y aceptar por completo los [términos y condiciones de uso](https://www.libredte.cl/legal).

## Instalación

Directamente desde la terminal con:

```shell
composer require libredte/libredte-lib
```

## Ejemplo

```php
$data = [ /* aquí los datos del DTE */ ];

$emisor = new Contribuyente($data['Encabezado']['Emisor']['RUTEmisor']);
$certificate = $emisor->getFakeCertificate();
$caf = $emisor->getFakeCaf();

$factory = new DocumentoFactory();
$documento = $factory->createFromArray($data);
$documento->timbrar($caf);
$documento->firmar($certificate);

$sobre = new SobreEnvio();
$sobre->agregar($documento);
$sobre->setCaratula([
    'FchResol' => '2019-12-23',
    'NroResol' => 0,
    'RutEnvia' => $certificate->getID(),
]);
$xml = $sobre->firmar($certificate);
echo $xml , "\n\n";
```

## Casos de Uso

En el directorio `tests/resources/yaml/documentos_ok` se encuentran los casos de uso más comunes que se han visto en casi 10 años de historia de LibreDTE. Se recomienda mucho que el programador revise dichos ejemplos antes de intentar una integración.

Los casos de uso representan una situación específica que se puede dar al crear un DTE.

## Funcionalidades y características

1. **Documentos XML**
   1. [x] Clase `XmlDocument` que extiende `DomDocument` para proveer funcionalidades adicionales para el trabajo con XML con los ajustes requeridos por el SII.
   2. [x] Clase `XmlConverter` para manejar la conversión desde:
      1. [x] Arreglo PHP a string XML (en `XmlDocument`).
      2. [x] String XML (en `XmlDocument`) a Arreglo PHP.
   3. [x] Clase `XmlValidator` para la validación del esquema del XML.

2. **Firma Electrónica**
   1. [x] Clase `Certificate` para representar un certificado digital y sus atributos. Permite obtener la clave pública y privada, junto con otros datos del certificado como el nombre de la persona asociada, su ID (RUN) o el período de validez del certificado.
   2. [x] Clase `CertificateFaker` para generar certificados falsos, para pruebas o demostraciones.
   3. [x] Clases `SignatureGenerator`, `SignatureValidator` y `XmlSignatureNode` para lo relacionado con XML DSIG, incluyendo los ajustes requeridos por el SII.

3. **Cliente HTTP y API SOAP SII**
   1. [x] Clase `WsdlConsumer` para el consumo de servicios web (API) SOAP mediante WSDL.
   2. [x] Clase `TokenManager` para la gestión del ciclo de vida del token de sesión de los servicios web del SII.
   3. [x] Clase `DocumentUploader` para enviar un XML de un DTE al SII y obtener su número de seguimiento o Track ID.
   4. [x] Clase `DocumentValidator` que permite interactuar con la API para:
      1. [x] Consultar el estado del envío de un XML al SII, sin los detalles en caso de problemas.
      2. [x] Solicitar el correo con el estado del envío de un XML al SII, con los detalles en caso de problemas.
      3. [x] Verificar la validez de un DTE enviando ciertos datos al SII (como su folio o total) y corroborar que es un DTE legalmente emitido. También es posible realizar la verificación avanzada y consultar al SII si la firma electrónica del DTE es la que el SII recibió.

4. **Autorización de Folios (archivos CAF)**
   1. [x] Clase `Caf` para representar un XML de un CAF solicitado al SII y sus atributos. Permite obtener la clave pública y privada, junto con otros datos del CAF como el tipo de documento, rango de folios o su período de validez.
   2. [x] Clase `CafFaker` para generar archivos CAF falsos, para pruebas o demostraciones.

5. **Documentos Tributarios Electrónicos (DTE)**
   1. [x] Constructores, o *builders*, de documentos tributarios que tienen una responsabilidad: normalizar los datos de un DTE. Existe un constructor, o *builder*, por cada tipo de documento. Estos realizan las estandarizaciones de campos y datos para luego generar el XML. Las normalizaciones de los constructores son apoyadas por diferentes *traits* que se pueden encontrar en el directorio `Normalization`.
   2. [x] Clases de documentos tributarios para representar un DTE específico, que se identifica de manera única por su tipo y folio. Estas clases heredan de `AbstractDocumento`.
   3. [x] Clase `AbstractDocumento` que contiene la base de las clases del punto previo. Esta clase abstracta tiene los métodos comúnes, por ejemplo los métodos necesarios para timbrar, firmar un DTE, validar el timbre, la firma o el esquema del XML.
   4. [x] Clase `DocumentoTipo` representa un tipo de DTE, por ejemplo "Factura Afecta". Esta clase es la responsable de entregar la información relacionada a un tipo de documento, pero no a uno de un folio específico.
   5. [x] Clase `SobreEnvio` para representar un grupo de documentos tributarios. En estricto rigor representa al tag XML `EnvioDTE` o `EnvioBOLETA`. Tiene métodos necesarios para firmar el envío, validar su firma y validar el esquema del XML del envío.

6. **Impuestos adicionales y retenciones**
   1. [x] Existe soporte para todos los impuestos adicionales y retenciones, excepto los asociados a combustibles.

7. **PDF del DTE**
   1. [ ] A partir del XML del DTE se puede generar un PDF en diferentes formatos.
   2. [ ] Formatos disponibles:
      1. [ ] Estándar: con tamaño de hoja carta y papel contínuo.

8. **Proceso de Intercambio de DTE**
   1. [ ] Generación de los archivos XML de respuesta para enviar con:
      1. [ ] Acuse de recibo.
      2. [ ] Recibo de mercaderías y servicios prestados.
      3. [ ] Resultado validación.

9. **Registro de Compra y Venta (RCV)**
   1. [ ] Se permite realizar las siguientes acciones mediante la API del SII:
      1. [ ] Ingresar aceptación o reclamo.
      2. [ ] Listar eventos del DTE.
      3. [ ] Consultar posibilidad de cesión del DTE.
      4. [ ] Consultar fecha de recepción en SII del DTE.

10. **Libros y registros**
    1. [ ] Libros de compras y ventas (IECV). Incluye generación en PDF.
    2. [ ] Libro de guías de despacho.
    3. [ ] Libro de boletas.
    4. [ ] Reporte de Ventas Diarias (RVD) o Reporte de Consumo de Folios (RCOF).

11. **Cesión Electrónica (*factoring*)**
    1. [ ] Generación del AEC.
    2. [ ] Envío del AEC al Registro de Transferencias de Créditos (RTC) del SII.

### Documentos tributarios electrónicos

| Código | Documento                      | Clase / *builder*        | Soporte oficial   |
|--------|--------------------------------|--------------------------|-------------------|
| 33     | Factura Afecta                 | `FacturaAfecta`          | Si                |
| 34     | Factura Exenta                 | `FacturaExenta`          | Si                |
| 39     | Boleta Afecta                  | `BoletaAfecta`           | Si                |
| 41     | Boleta Exenta                  | `BoletaExenta`           | Si                |
| 43     | Liquidación de Factura         | `LiquidacionFactura`     | No (próximamente) |
| 46     | Factura de Compra              | `FacturaCompra`          | Si                |
| 52     | Guía de Despacho               | `GuiaDespacho`           | Si                |
| 56     | Nota de Débito                 | `NotaDebito`             | Si                |
| 61     | Nota de Crédito                | `NotaCredito`            | Si                |
| 110    | Factura de Exportación         | `FacturaExportacion`     | Si                |
| 111    | Nota de Débito de Exportación  | `NotaDebitoExportacion`  | Si                |
| 112    | Nota de Crédito de Exportación | `NotaCreditoExportacion` | Si                |

**Nota**: que el documento tributario electrónico (DTE) tenga soporte oficial no significa que estén todos los posibles casos de uso permitidos por el SII disponibles. Solo significa que oficialmente LibreDTE le da soporte a dicho documento tributario mediante el proceso de normalización de los datos del DTE que cumple, al menos, con lo necesario para que las pruebas (*tests*) de esta biblioteca pasen. En ningún caso el soporte oficial a un tipo de documento significa que LibreDTE permita todas las opciones del DTE oficialmente soportado.

Enlaces
-------

- [Sitio web LibreDTE](https://www.libredte.cl).
- [Código fuente en GitHub](https://github.com/LibreDTE/libredte-lib).
- [Paquete en Packagist](https://packagist.org/packages/libredte/libredte-lib).

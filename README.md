LibreDTE: Biblioteca Estándar en PHP (Núcleo)
=============================================

[![Total Downloads](https://poser.pugx.org/libredte/lib/downloads)](https://packagist.org/packages/libredte/lib)
[![Monthly Downloads](https://poser.pugx.org/libredte/lib/d/monthly)](https://packagist.org/packages/libredte/lib)
[![License](https://poser.pugx.org/libredte/lib/license)](https://packagist.org/packages/libredte/lib)

LibreDTE es un proyecto que tiene por objetivo proveer Facturación Electrónica
Libre para Chile.

Aquí podrás encontrar la biblioteca escrita en lenguaje PHP para la integración
con el Servicio de Impuestos Internos (SII) asociada a los Documentos Tributarios
Electrónicos (DTE).

Esta biblioteca se construye originalmente para ser usada en la
[Aplicación Web de LibreDTE Edición Comunidad](https://github.com/LibreDTE/libredte-webapp).
En esta biblioteca solo estará lo básico, sin interfaces de usuario, para que un
desarrollador pueda construir su propia aplicación.

Si deseas una aplicación "llegar y usar" sin preocuparte por instalaciones o
servidores, revisa la [Aplicación Web de LibreDTE Edición Enterprise](https://www.libredte.cl).
Podrás registrarte y usar la plataforma web de facturación junto a otros módulos.

**Importante**: LibreDTE no provee soporte oficial para integraciones usando esta
Biblioteca de manera directa. LibreDTE solo provee el soporte para integraciones mediante
servicios web de la [Aplicación Web de LibreDTE Edición Enterprise](https://www.libredte.cl).

Funcionalidades implementadas
-----------------------------

- Conexión a maullin (servidor de certificación/pruebas) o palena (servidor de producción).
- Obtención de token para autenticación automática usando firma electrónica simple.
- Obtención de estado de un DTE a través del folio, fecha y monto.
- Generación de XML, timbraje y firma del DTE.
- Generación, y envío, de XML EnvioDTE firmado.
- Consulta del estado de envío de DTE a través de su Track ID.
- Consulta del estado de envío de un Libro a través de su Track ID.
- Documentos oficialmente soportados (sets certificados):
    - Factura electrónica (33).
    - Factura exenta electrónica (34).
    - Boleta electrónica (39).
    - Boleta exenta electrónica (41).
    - Factura de compra electrónica (46).
    - Guía de despacho electrónica (52).
    - Nota de débito electrónica (56).
    - Nota de crédito electrónica (61).
    - Factura de exportación electrónica (110).
    - Nota de débito exportación electrónica (111).
    - Nota de crédito exportación electrónica (112).
- Información electrónica de compras y ventas (IECV).
- Libro de guías de despacho electrónico.
- Libro de boletas y reporte de consumo de folios (RVD, ex RCOF).
- Etapa de intercambio con otros contribuyentes:
    - Acuse de recibo.
    - Recibo de mercaderías y servicios prestados.
    - Resultado validación.
- Generación de DTE en PDF a partir de su XML, hoja carta y papel contínuo.
- Generación de IECV en PDF a partir de su XML.
- Impuestos adicionales (excepto combustibles).
- Cesión electrónica (factoring).
- Acciones para el Registro de Compras y Ventas (RCV).
    - Ingresar aceptación o reclamo.
    - Listar eventos del DTE.
    - Consultar posibilidad de cesión del DTE.
    - Consultar fecha de recepción en SII del DTE.

**Nota**: no existe soporte oficial para el documento "Liquidación de Factura".

Instalación
-----------

Directamente desde la terminal con:

```
composer require libredte/libredte-lib
```

Ejemplos
--------

Los ejemplos están disponibles en el directorio
[examples](https://github.com/LibreDTE/libredte-lib/tree/master/examples).
Los ejemplos que requieren archivos XML son casos donde el XML es entregado
por el SII o bien casos de XML generados por la misma biblioteca.

Para la generación de los DTE se utiliza por defecto la estructura oficial del
SII a través de arreglos asociativos de PHP.

Términos y condiciones de uso
-----------------------------

Al utilizar este proyecto, total o parcialmente, automáticamente se acepta
cumplir con los [términos y condiciones de uso](https://www.libredte.cl/legal)
que rigen a LibreDTE. La [Licencia Pública General Affero de GNU (AGPL)](https://raw.githubusercontent.com/LibreDTE/libredte-lib/master/COPYING)
solo aplica para quienes respeten los términos y condiciones de uso. No existe
una licencia comercial de LibreDTE, por lo cual no es posible usar el proyecto
si no aceptas cumplir dichos términos y condiciones.

La versión resumida de los términos y condiciones de uso de LibreDTE que
permiten utilizar el proyecto, son los siguientes:

- Tienes la libertad de: usar, estudiar, distribuir y cambiar LibreDTE.
- Si utilizas LibreDTE en tu software, el código fuente de dicho software deberá
  ser liberado de manera pública bajo licencia AGPL.
- Si haces cambios a LibreDTE deberás liberar de manera pública el código fuente
  de dichos cambios bajo licencia AGPL.
- Debes hacer referencia de manera pública en tu software al proyecto y autor
  original de LibreDTE, tanto si usas LibreDTE sin modificar o realizando
  cambios al código.

Es obligación de quienes quieran usar el proyecto leer y aceptar por completo
los [términos y condiciones de uso](https://www.libredte.cl/legal).

Contribuir al proyecto
----------------------

Si deseas contribuir con el proyecto, especialmente resolviendo alguna de las
[*issues* abiertas](https://github.com/LibreDTE/libredte-lib/issues) o creando nuevos
[ejemplos](https://github.com/LibreDTE/libredte-lib/tree/master/examples) sobre el
uso de la biblioteca, debes:

1. Hacer fork del proyecto en [GitHub](https://github.com/LibreDTE/libredte-lib)
2. Crear una *branch* para los cambios: git checkout -b nombre-branch
3. Modificar código: git commit -am 'Se agrega...'
4. Publicar cambios: git push origin nombre-branch
5. Crear un *pull request* para unir la nueva *branch* con LibreDTE.

**IMPORTANTE**: antes de hacer un *pull request* verificar que el código
cumpla con los estándares [PSR-1](http://www.php-fig.org/psr/psr-1),
[PSR-2](http://www.php-fig.org/psr/psr-2) y
[PSR-4](http://www.php-fig.org/psr/psr-4).

Enlaces
-------

- [Sitio web LibreDTE](https://www.libredte.cl).
- [Código fuente en GitHub](https://github.com/LibreDTE/libredte-lib).
- [Paquete en Packagist](https://packagist.org/packages/libredte/lib).

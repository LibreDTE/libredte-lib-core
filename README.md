LibreDTE: Biblioteca Estándar en PHP
====================================

[![Build Status](https://travis-ci.org/LibreDTE/libredte-lib.svg?branch=master)](https://travis-ci.org/LibreDTE/libredte-lib)
[![Total Downloads](https://poser.pugx.org/sasco/libredte/downloads)](https://packagist.org/packages/sasco/libredte)
[![Monthly Downloads](https://poser.pugx.org/sasco/libredte/d/monthly)](https://packagist.org/packages/sasco/libredte)
[![License](https://poser.pugx.org/sasco/libredte/license)](https://packagist.org/packages/sasco/libredte)

LibreDTE es un proyecto que tiene por objetivo proveer Facturación Electrónica
Libre para Chile.

Aquí podrás encontrar la biblioteca escrita en lenguaje PHP para la integración
con el Servicio de Impuestos Internos (SII) asociada a los Documentos Tributarios
Electrónicos (DTE).

La biblioteca se construye originalmente para ser usada en la
[Aplicación Web de LibreDTE Versión Comunidad](https://github.com/LibreDTE/libredte-webapp).
En esta biblioteca sólo estará lo básico, sin interfaces de usuario, para que un
desarrollador pueda construir su propia aplicación.

Si deseas una aplicación "llegar y usar" sin preocuparte por instalaciones o
servidores, revisa la [Aplicación Web de LibreDTE Versión Oficial](https://libredte.cl).
Podrás registrarte y usar la plataforma web de facturación junto a otros módulos.

**Importante**: SASCO SpA, empresa autora y dueña de LibreDTE, no provee soporte
oficial para integraciones usando esta Biblioteca de manera directa. SASCO SpA
sólo provee el soporte para integraciones mediante servicios web de la
plataforma web de LibreDTE, normalmente en [www.libredte.cl](https://libredte.cl).

Funcionalidades implementadas
-----------------------------

- Conexión a maullin o palena.
- Obtención de token para autenticación automática.
- Obtención de estado de un DTE a través del folio, fecha y monto.
- Parser para set de pruebas.
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
- Libro de boletas y reporte de consumo de folios (RCOF).
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

### Funcionalidades independientes

- Conversión de arreglos PHP a XML.
- Firma electrónica de un XML.
- Internacionalización.
- Sistema de logs.

### Formatos soportados de entrada de datos de DTE

- Formatos oficiales con estructura del SII:
  - JSON
  - XML
  - YAML

Instalación
-----------

Directamente desde la terminal con:

	$ composer require sasco/libredte

O editando el archivo *composer.json* y agregando:

	{
		"require": {
			 "sasco/libredte": "21.10.*"
		}
	}

Los ejemplos están disponibles en el directorio
[examples](https://github.com/LibreDTE/libredte-lib/tree/master/examples).
Los ejemplos que requieren archivos XML son casos donde el XML es entregado
por el SII o bien casos de XML generados por la misma biblioteca.

Términos y condiciones de uso
-----------------------------

Al utilizar este proyecto, total o parcialmente, automáticamente se acepta
cumplir con los [términos y condiciones de uso](https://legal.libredte.cl)
que rigen a LibreDTE. La [Licencia Pública General Affero de GNU (AGPL)](https://raw.githubusercontent.com/LibreDTE/libredte-lib/master/COPYING)
sólo aplica para quienes respeten los términos y condiciones de uso. No existe
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
los [términos y condiciones de uso](https://legal.libredte.cl).

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

Contacto y redes sociales
-------------------------

- Sitio web: <https://libredte.cl>
- Youtube: <https://www.youtube.com/libredtecl>
- Facebook: <https://www.facebook.com/libredte>
- Instagram: <https://www.instagram.com/libredte>
- Twitter: <https://twitter.com/libredte>

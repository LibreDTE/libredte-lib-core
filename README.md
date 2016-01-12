LibreDTE: Biblioteca PHP
========================

LibreDTE es un proyecto que tiene por objetivo proveer facturación electrónica
libre para Chile.

Aquí podrás encontrar la biblioteca escrita en lenguaje PHP para la interacción
con el Servicio de Impuestos Internos (SII).

La biblioteca se construye originalmente para ser usada en el módulo
[Dte](https://github.com/LibreDTE/libredte-sowerphp) del framework
[SowerPHP](http://sowerphp.org). En esta biblioteca sólo estará lo básico, sin
interfaces de usuario, para que un desarrollador pueda construir su propia
aplicación.

Si deseas una aplicación ya construída puedes revisar el
[sitio web oficial de LibreDTE](http://libredte.cl) donde podrás registrarte
gratuitamente y usar la plataforma web de facturación. Si deseas acceder al
código fuente de la aplicación web puedes hacerlo
[aquí](https://github.com/LibreDTE/libredte-webapp).

Funcionalidades implementadas
-----------------------------

- Obtención de token para autenticación automática.
- Obtención de estado de un DTE a través del folio, fecha y monto.
- Envío automático de archivo XML de DTE al SII.
- Generación, y envío, de XML EnvioDTE firmado.
- Consulta del estado de envío de DTE a través de su Track ID.
- Consulta del estado de envío de un Libro a través de su Track ID.
- Generación de XML, timbraje y firma del DTE. Documentos de sets de pruebas y
  etapa simulación aceptados por el SII:
    - Factura electrónica (set de pruebas básico)
    - Factura exenta electrónica (set de pruebas factura exenta)
    - Nota de débito electrónica (set de pruebas básico y set de pruebas factura exenta)
    - Nota de crédito electrónica (set de pruebas básico y set de pruebas factura exenta)
    - Libro de ventas electrónico (set de pruebas básico)
    - Libro de compras electrónico (set de pruebas de compras)
    - Guía de despacho electrónica
    - Libro de guías de despacho electrónico
- Etapa de intercambio con otros contribuyentes:
    - Acuse de recibo
    - Recibo de mercaderías y servicios prestados
    - Resultado validación
- Generación de documentos en PDF, con muestras aceptadas por el SII.

Instalación
-----------

Directamente desde la terminal con:

	$ composer require sasco/libredte dev-master

O editando el archivo *composer.json* y agregando:

	{
		"require": {
			 "sasco/libredte": "dev-master"
		}
	}

Por el momento, la única versión disponible es la de desarrollo.

Documentación
-------------

La documentación está disponible en formato HTML generada con Doxygen en:
<http://libredte.cl/doxygen>.

Los ejemplos están disponibles en el directorio
[examples](https://github.com/LibreDTE/libredte-lib/tree/master/examples).

Licencia
--------

Este software se encuentra bajo los términos de la licencia GPL 3 que puedes
encontrar en el archivo
[COPYING](https://raw.githubusercontent.com/LibreDTE/libredte-lib/master/COPYING).

En resumen:

- Tienes la libertad de: usar, estudiar, distribuir y cambiar LibreDTE.
- Si utilizas LibreDTE en tu software, dicho software deberá ser publicado bajo
  licencia GPL o bien alguna otra licencia compatible con la GPL.
- Si haces cambios a LibreDTE, deberás publicar dichos cambios bajo licencia GPL
  o bien alguna otra licencia compatible con la GPL.
- Debes hacer referencia al proyecto y autor original de LibreDTE, tanto si usas
  LibreDTE sin modificar o realizando cambios al código.

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

- Sitio web: <http://libredte.cl>
- Twitter: <https://twitter.com/LibreDTE>
- Facebook: <https://www.facebook.com/LibreDTE>
- Google+: <https://plus.google.com/u/0/101078963971350176990/about>
- Linkedin: <https://www.linkedin.com/grp/home?gid=8403251>
- Youtube: <https://www.youtube.com/channel/UCnh5duQUXmo4l8AD28PakiQ>

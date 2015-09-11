LibreDTE
========

LibreDTE corresponde a una biblioteca escrita en lenguaje PHP para la
interacción con el SII en Chile.

La biblioteca se construye originalmente para el módulo Dte de la extensión
[empresa](https://github.com/SowerPHP/extension-empresa) del framework
[SowerPHP](http://sowerphp.org). En esta biblioteca sólo estará lo básico para
que un desarrollador usando PHP pueda construir su aplicación, si se requiere
de una base ya construída se recomienda revisar el framework y módulo antes
mencionado.

Funcionalidades implementadas
-----------------------------

- Obtención de token para autenticación automática.
- Obtención de estado de un DTE a través del folio, fecha y monto.
- Envío de archivo XML de DTE al SII.
- Consulta del estado de envío de DTE a través de su Track ID.
- Generación, y envío, de XML EnvioDTE firmado.
- Generación de XML, timbraje y firma del DTE. Documentos de sets de pruebas y
  etapa simulación aceptados por el SII:
    - Factura electrónica (set de pruebas básico)
    - Factura exenta electrónica (set de pruebas factura exenta)
    - Nota de débito electrónica (set de pruebas básico y set de pruebas factura exenta)
    - Nota de crédito electrónica (set de pruebas básico y set de pruebas factura exenta)
    - Libro de ventas electrónico (set de pruebas básico)
    - Libro de compras electrónico (set de pruebas de compras)
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
[examples](https://github.com/sascocl/LibreDTE/tree/master/examples).

Licencia
--------

Este software se encuentra bajo los términos de la licencia GPL 3 que puedes
encontrar en el archivo
[COPYING](https://raw.githubusercontent.com/sascocl/LibreDTE/master/COPYING).

Contribuir al proyecto
----------------------

Si deseas contribuir con el proyecto, especialmente resolviendo alguna de las
[*issues* abiertas](https://github.com/sascocl/LibreDTE/issues) o creando nuevos
[ejemplos](https://github.com/sascocl/LibreDTE/tree/master/examples) sobre el
uso de la biblioteca, debes:

1. Hacer fork del proyecto en [GitHub](https://github.com/sascocl/LibreDTE)
2. Crear una *branch* para los cambios: git checkout -b nombre-branch
3. Modificar código: git commit -am 'Se agrega...'
4. Publicar cambios: git push origin nombre-branch
5. Crear un *pull request* para unir la nueva *branch* con LibreDTE.

**IMPORTANTE**: antes de hacer un *pull request* verificar que el código
cumpla con los estándares [PSR-1](http://www.php-fig.org/psr/psr-1),
[PSR-2](http://www.php-fig.org/psr/psr-2) y
[PSR-4](http://www.php-fig.org/psr/psr-4).

Contacto
--------

- Sitio web: <http://libredte.cl>
- Twitter: <https://twitter.com/LibreDTE>

LibreDTE
========

LibreDTE corresponde a una biblioteca escrita en lenguaje PHP para la
interacción con el SII en Chile.

La biblioteca se construye originalmente para el módulo Dte de la extensión
[empresa](https://github.com/SowerPHP/extension-empresa) del framework
[SowerPHP](http://sowerphp.org). En esta biblioteca solo estará lo básico para
que un desarrollador usando PHP pueda construir su aplicación, si se requiere
de una base ya construída se recomienda revisar el framework y módulo antes
mencionado.

**Biblioteca en desarrollo**: se irán publicando en el repositorio
funcionalidades a medida que se vayan completando y probando.

Funcionalidades implementadas
-----------------------------

- Obtención de token para autenticación automática.
- Obtención de estado de un DTE.
- Envío de archivo XML de DTE a SII.
- Consulta de estado de envío de DTE.
- Generación de XML DTE timbraje y firma (probado DTE 33).
- Generación (y envío) de XML EnvioDTE firmado.

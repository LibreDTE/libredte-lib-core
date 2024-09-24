Archivos WSDL certificación
===========================

Algunos WSDL del ambiente de certificación no funcionan tal cual los provee SII. Lo anterior ya que apuntan a un servidor llamado `nogal.sii.cl` el cual no es accesible desde Internet. Quizás es un servidor local del SII para desarrollo.

Por lo que se han modificado los WSDL para el ambiente de certificación del SII y usar el servidor `maullin.sii.cl` que es el correcto.

Estos WSDL se usarán automáticamente al solicitar el WSDL del ambiente de certificación a través de LibreDTE. Los WSDL para el ambiente de producción son directamente los proporcionados por SII.

Referencias
-----------

- http://stackoverflow.com/a/28464354/3333009

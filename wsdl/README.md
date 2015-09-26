WSDL ambiente certificación
===========================

Algunos WSDL del ambiente de certificación no funcionan tal cual los provee SII.
Lo anterior ya que apuntan a un servidor llamado nogal.sii.cl el cual no es
accesible desde Internet (¿servidor local del SII para desarrollo?).
Aquí se entregarán WSDL modificados para el servidor maullin.sii.cl

Estos WSDL se usarán automáticamente al solicitar el WSDL del ambiente de
certificación a través de LibreDTE. Los WSDL para el ambiente de producción son
directamente los proporcionados por SII.

Modificaciones
--------------

- QueryEstUp: http://stackoverflow.com/a/28464354/3333009
- QueryEstDte: basado en modificación a QueryEstUp
- CrSeed: basado en modificación a QueryEstUp
- GetTokenFromSeed: basado en modificación a QueryEstUp
- wsDTECorreo: basado en modificación a QueryEstUp

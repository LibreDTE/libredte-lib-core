Ejemplos de DTEs en formato JSON
================================

Se adjuntan distintos ejemplos en formato JSON para diferentes tipos de
documentos tributarios electrónicos. Estos ejemplos requieren normalización y
son los datos mínimos que deberían entregarse para poder emitir el DTE según
cada ejemplo.

Estos ejemplos sirven como guía para quienes deseen aprender cómo generar los
distintos documentos, ya sea consumiento los servicios web de la aplicación web
de LibreDTE o bien para ser usados directamente con la biblioteca.

Adicionalmente estos ejemplos son utilizados en un *test* de PHPUnit para
verificar que el proceso de normalización es correcto. El archivo
*montos_esperados.json* contiene cada uno de lo ejemplos con los montos que se
esperan del proceso de normalización. En caso de querer agregar un ejemplo es
obligatorio también agregar su monto esperado, ya que en caso de no existir el
test de PHPUnit fallará.

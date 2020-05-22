Comandos de LibreDTE
====================

Aquí podrá encontrar comandos escritos en PHP que utilizan LibreDTE y pueden ser
llamados desde la terminal directamnte para utilizar la biblioteca. Lo anterior
permitirá que otros lenguajes de programación puedan hacer uso de la biblioteca
a través de llamadas del estilo os.system() (pensando en Python por ejemplo).

Se recomienda agregar el directorio bin de los comandos al PATH del usuario. En .bashrc agregar:

    LIBREDTE=/ruta/a/libredte-lib
    PATH=$LIBREDTE/bin:$PATH

**Importante**: con la entrada en vigencia de [api.libredte.cl](https://api.libredte.cl)
es poco probable que se sigan actualizando estos comandos. Ya que dicha API se
construyó específicamente para realizar las tareas que realiza la Biblioteca de
PHP vía servicios web. Así cualquier programador puede consumir las
funcionalidades de LibreDTE desde cualquier lenguaje.

Si estás interesado en que se creen nuevos comandos, puedes [contactarnos](https://libredte.cl/contacto)
y lo revisamos.

libredte_set2json
-----------------

Permite crear objeto JSON con los datos en TXT de los casos de los set de
pruebas para el proceso de certificación.

Primero se debe editar el archivo TXT con los casos del set de prueba para que
quede sólo con los datos que el comando requiere. Se debe eliminar todo lo que
no tiene que ver con los casos de prueba. Puedes ver un ejemplo de un archivo
TXT ya preparado [aquí](https://github.com/LibreDTE/libredte-lib/blob/master/examples/set_pruebas/001-basico.txt).

Generar JSON y mostrar por la terminal:

    $ libredte_set2json.php --set set_basico.txt

Generar JSON y guardar en archivo:

    $ libredte_set2json.php --set set_basico.txt --json set_basico.json

libredte_pdf
------------

Generación de archivos PDF de muestras impresas a partir del XML de EnvioDTE o
EnvioBoleta.


PDF en hoja carta, sin logo y sin copia cedible:

    $ libredte_pdf.php --xml EnvioDTE.xml --dir salida

PDF en hoja carta, con logo y con copia cedible:

    $ libredte_pdf.php --xml EnvioDTE.xml --dir salida --logo logo.png --cedible

PDF en papel continuo de 75 mm, sin logo y sin copia cedible:

    $ libredte_pdf.php --xml EnvioDTE.xml --dir salida --papel 75

PDF en hoja carta, sin logo, sin copia cedible y con web de verificación personalizada (sólo para boletas):

    $ libredte_pdf.php --xml EnvioDTE.xml --dir salida --web libredte.cl/boletas

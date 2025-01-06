Ejemplo
=======

El siguiente es un ejemplo básico de cómo generar el XML de un DTE.

.. code-block:: php
    // Iniciar aplicación.
    $app = \libredte\lib\Core\Application::getInstance();

    // Preparar datos del DTE.
    $data = [ /* aquí los datos del DTE */ ];

    // Cargar contenido del archivo CAF.
    $caf = $app
        ->getBillingPackage()
        ->getIdentifierComponent()
        ->getCafLoaderWorker()
        ->load(file_get_contents($cafFile))
        ->getCaf()
    ;

    // Cargar el certificado digital.
    $certificate = $app
        ->getPrimePackage()
        ->getCertificateComponent()
        ->getLoaderWorker()
        ->createFromFile($certificateFile, $certificatePass)
    ;

    // Crear DTE.
    $document = $app
        ->getBillingPackage()
        ->getDocumentComponent()
        ->bill($data, $caf, $certificate)
        ->getDocument()
    ;

    // Mostrar el XML del DTE generado.
    echo $document->saveXml() , "\n";

.. seealso::

    Revisa los `casos de uso <use-cases>`_ para ejemplos detallados de cómo construir los datos del DTE según diferentes situaciones.

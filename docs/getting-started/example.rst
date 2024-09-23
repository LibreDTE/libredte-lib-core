Ejemplo
=======

El siguiente es un ejemplo básico de cómo generar un DTE en su sobre para subir al SII.

.. code-block:: php

    $data = [ /* aquí los datos del DTE */ ];

    $emisor = new Contribuyente($data['Encabezado']['Emisor']['RUTEmisor']);
    $certificate = $emisor->getFakeCertificate();
    $caf = $emisor->getFakeCaf();

    $factory = new DocumentoFactory();
    $documento = $factory->createFromArray($data);
    $documento->timbrar($caf);
    $documento->firmar($certificate);

    $sobre = new SobreEnvio();
    $sobre->agregar($documento);
    $sobre->setCaratula([
        'FchResol' => '2019-12-23',
        'NroResol' => 0,
        'RutEnvia' => $certificate->getID(),
    ]);
    $xml = $sobre->firmar($certificate);
    echo $xml, "\n\n";

.. seealso::

    Revisa los `casos de uso <use-cases>`_ para ejemplos detallados de cómo construir los datos del DTE según diferentes situaciones.

Documentos tributarios electrónicos
===================================

Listado de documentos tributarios electrónicos (DTE) disponibles en LibreDTE.

.. list-table::
   :width: 100%
   :widths: 15 35 35 15
   :header-rows: 1

   * - Código
     - Documento
     - Clase / *builder*
     - Soporte oficial
   * - 33
     - Factura Afecta
     - ``FacturaAfecta``
     - Si
   * - 34
     - Factura Exenta
     - ``FacturaExenta``
     - Si
   * - 39
     - Boleta Afecta
     - ``BoletaAfecta``
     - Si
   * - 41
     - Boleta Exenta
     - ``BoletaExenta``
     - Si
   * - 43
     - Liquidación de Factura
     - ``LiquidacionFactura``
     - No (próximamente)
   * - 46
     - Factura de Compra
     - ``FacturaCompra``
     - Si
   * - 52
     - Guía de Despacho
     - ``GuiaDespacho``
     - Si
   * - 56
     - Nota de Débito
     - ``NotaDebito``
     - Si
   * - 61
     - Nota de Crédito
     - ``NotaCredito``
     - Si
   * - 110
     - Factura de Exportación
     - ``FacturaExportacion``
     - Si
   * - 111
     - Nota de Débito de Exportación
     - ``NotaDebitoExportacion``
     - Si
   * - 112
     - Nota de Crédito de Exportación
     - ``NotaCreditoExportacion``
     - Si

.. note::

  Que el documento tributario electrónico (DTE) tenga soporte oficial no significa que estén todos los posibles casos de uso permitidos por el SII disponibles. Solo significa que oficialmente LibreDTE le da soporte a dicho documento tributario mediante el proceso de normalización de los datos del DTE que cumple, al menos, con lo necesario para que las pruebas (*tests*) de esta biblioteca pasen.

.. warning::

  En ningún caso el soporte oficial a un tipo de documento significa que LibreDTE permita todas las opciones del DTE oficialmente soportado.

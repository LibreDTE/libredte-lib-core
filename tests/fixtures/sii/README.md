# Respuestas del SII (ambiente de certificación)

XML de las respuestas del SII, tal como los recibe cada job de `Integration` antes de
pasarlos a las clases `*Response`. Se obtuvieron consultando el ambiente de certificación;
los datos que identifican (RUT del remitente, TrackID, token, semilla, número de atención,
fechas y nombres de archivo) son valores inventados. Los códigos de estado y las glosas
son los del SII.

- `lazy/`: autenticación (semilla y token).
- `dte/`: envío del XML y consulta de estados de envío y de documentos.
- `rtc/`: registro de cesiones (AEC).
- `rcv/`: registro de reclamos; es el XML que arma el job a partir del objeto SOAP
  (`<return>` con `codResp` y `descResp`, o `<data>` cuando la respuesta es un texto).

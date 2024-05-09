<?php

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Sii\Base;

/**
 * Clase base para los documentos XML.
 */
abstract class Envio extends Documento
{

    /**
     * Método que realiza el envío del documento al SII.
     * @return int|false Track ID del envío o =false si hubo algún problema al enviar el documento.
     */
    public function enviar(?int $retry = null, bool $gzip = false)
    {
        // generar XML que se enviará
        if (!$this->xml_data) {
            $this->xml_data = $this->generar();
        }
        if (!$this->xml_data) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::DOCUMENTO_ERROR_GENERAR_XML,
                \libredte\lib\Estado::get(
                    \libredte\lib\Estado::DOCUMENTO_ERROR_GENERAR_XML,
                    substr(get_class($this), strrpos(get_class($this), '\\')+1)
                )
            );
            return false;
        }
        // validar schema del documento antes de enviar
        if (!$this->schemaValidate()) {
            return false;
        }
        // si no se debe enviar no continuar
        if ($retry === 0) {
            return false;
        }
        // solicitar token
        $token = \libredte\lib\Sii\Autenticacion::getToken($this->Firma);
        if (!$token) {
            return false;
        }
        // enviar DTE
        $envia = $this->caratula['RutEnvia'];
        $emisor = !empty($this->caratula['RutEmisor']) ? $this->caratula['RutEmisor'] : $this->caratula['RutEmisorLibro'];
        $result = \libredte\lib\Sii::enviar($envia, $emisor, $this->xml_data, $token, $gzip, $retry);
        if ($result===false) {
            return false;
        }
        // retornar track id del SII
        if (!is_numeric((string)$result->TRACKID)) {
            return false;
        }
        return (int)(string)$result->TRACKID;
    }

}

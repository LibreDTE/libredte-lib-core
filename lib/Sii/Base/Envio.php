<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
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

namespace sasco\LibreDTE\Sii\Base;

/**
 * Clase base para los documentos XML
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-15
 */
abstract class Envio extends Documento
{

    /**
     * Método que realiza el envío del documento al SII
     * @return Track ID del envío o =false si hubo algún problema al enviar el documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-12-10
     */
    public function enviar($retry = null, $gzip = false)
    {
        // generar XML que se enviará
        if (!$this->xml_data) {
            $this->xml_data = $this->generar();
        }
        if (!$this->xml_data) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DOCUMENTO_ERROR_GENERAR_XML,
                \sasco\LibreDTE\Estado::get(
                    \sasco\LibreDTE\Estado::DOCUMENTO_ERROR_GENERAR_XML,
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
        $token = \sasco\LibreDTE\Sii\Autenticacion::getToken($this->Firma);
        if (!$token) {
            return false;
        }
        // enviar DTE
        $envia = $this->caratula['RutEnvia'];
        $emisor = !empty($this->caratula['RutEmisor']) ? $this->caratula['RutEmisor'] : $this->caratula['RutEmisorLibro'];
        $result = \sasco\LibreDTE\Sii::enviar($envia, $emisor, $this->xml_data, $token, $gzip, $retry);
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

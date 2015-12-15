<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

namespace sasco\LibreDTE\Sii\Base;

/**
 * Clase base para los documentos XML
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-14
 */
abstract class Documento
{

    protected $xml_data; ///< String con el documento XML
    protected $caratula; ///< arreglo con la caratula del envío
    protected $Firma; ///< objeto de la firma electrónica
    protected $id; ///< ID del documento (se usa como referencia en la firma del XML)

    /**
     * Método para asignar la caratula
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol, etc
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    abstract public function setCaratula(array $caratula);

    /**
     * Método que genera el XML del documento
     * @return XML con el documento firmado o =false si no se pudo generar el documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    abstract public function generar();

    /**
     * Método que entrega el ID del documento
     * @return ID del libro
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Método para asignar la caratula
     * @param Firma Objeto con la firma electrónica
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    public function setFirma(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $this->Firma = $Firma;
    }

    /**
     * Método que valida el XML del documento
     * @return =true si el schema del documento del envío es válido, =null si no se pudo determinar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-14
     */
    public function schemaValidate()
    {
        if (!$this->xml_data) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DOCUMENTO_FALTA_XML,
                \sasco\LibreDTE\Estado::get(
                    \sasco\LibreDTE\Estado::DOCUMENTO_FALTA_XML,
                    substr(get_class($this), strrpos(get_class($this), '\\')+1)
                )
            );
            return null;
        }
        $this->xml = new \sasco\LibreDTE\XML();
        $this->xml->loadXML($this->xml_data);
        $xsd = dirname(dirname(dirname(dirname(__FILE__)))).'/schemas/'.$this->xml->getSchema();
        if (!is_readable($xsd))
            return null;
        $result = $this->xml->schemaValidate($xsd);
        if (!$result) {
            \sasco\LibreDTE\Log::write(
                \sasco\LibreDTE\Estado::DOCUMENTO_ERROR_SCHEMA,
                \sasco\LibreDTE\Estado::get(
                    \sasco\LibreDTE\Estado::DOCUMENTO_ERROR_SCHEMA,
                    substr(get_class($this), strrpos(get_class($this), '\\')+1),
                    implode("\n", $this->xml->getErrors())
                )
            );
        }
        return $result;
    }

}

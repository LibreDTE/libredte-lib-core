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

namespace sasco\LibreDTE;

/**
 * Clase con códigos y glosas de estados (generalmente errores) de LibreDTE
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-15
 */
class Estado
{

    // códigos de error para \sasco\LibreDTE\Sii::enviar()
    const ENVIO_OK = 0;
    const ENVIO_USUARIO_INCORRECTO = 1;
    const ENVIO_TAMANIO_ARCHIVO = 2;
    const ENVIO_ARCHIVO_CORTADO = 3;
    const ENVIO_NO_AUTENTICADO = 5;
    const ENVIO_EMPRESA_NO_AUTORIZADA = 6;
    const ENVIO_ESQUEMA_INVALIDO = 7;
    const ENVIO_ERROR_FIRMA = 8;
    const ENVIO_SISTEMA_BLOQUEADO = 9;
    const ENVIO_SSL_SIN_VERIFICAR = 51;
    const ENVIO_ERROR_CURL = 52;
    const ENVIO_ERROR_500 = 53;
    const ENVIO_ERROR_XML = 54;

    // códigos de error para \sasco\LibreDTE\Sii::request()
    const REQUEST_ERROR_SOAP = 61;
    const REQUEST_ERROR_BODY = 62;

    // códigos de error para \sasco\LibreDTE\Sii::cert()
    const SII_ERROR_CERTIFICADO = 71;

    // códigos de error para \sasco\LibreDTE\FirmaElectronica
    const FIRMA_ERROR = 101;

    // códigos de error para \sasco\LibreDTE\File::compress()
    const COMPRESS_ERROR_READ = 201;
    const COMPRESS_ERROR_ZIP = 202;

    // códigos de error para \sasco\LibreDTE\Sii\Autenticacion
    const AUTH_ERROR_SEMILLA = 301;
    const AUTH_ERROR_FIRMA_SOLICITUD_TOKEN = 302;
    const AUTH_ERROR_TOKEN = 303;

    // códigos de error para \sasco\LibreDTE\Sii\Dte
    const DTE_ERROR_GETDATOS = 401;
    const DTE_ERROR_TIPO = 402;
    const DTE_ERROR_RANGO_FOLIO = 403;
    const DTE_FALTA_FCHEMIS = 404;
    const DTE_FALTA_MNTTOTAL = 405;
    const DTE_ERROR_TIMBRE = 406;
    const DTE_ERROR_FIRMA = 407;

    // códigos de error para \sasco\LibreDTE\Sii\EnvioDte
    const ENVIODTE_DTE_MAX = 501;
    const ENVIODTE_TIPO_DTE_MAX = 502;
    const ENVIODTE_FALTA_DTE = 503;
    const ENVIODTE_GETDOCUMENTOS_FALTA_XML = 504;

    // códigos de error para \sasco\LibreDTE\Sii\EnvioRecibos
    const ENVIORECIBOS_FALTA_RECIBO = 601;
    const ENVIORECIBOS_FALTA_CARATULA = 602;

    // códigos de error para \sasco\LibreDTE\Sii\Folios
    const FOLIOS_ERROR_CHECK = 701;
    const FOLIOS_ERROR_FIRMA = 702;
    const FOLIOS_ERROR_ENCRIPTAR = 703;
    const FOLIOS_ERROR_DESENCRIPTAR = 704;

    // códigos de error para \sasco\LibreDTE\Sii\RespuestaEnvio
    const RESPUESTAENVIO_FALTA_RESPUESTA = 801;
    const RESPUESTAENVIO_FALTA_CARATULA = 802;

    // códigos de error para \sasco\LibreDTE\Sii\Base\Documento
    const DOCUMENTO_ERROR_GENERAR_XML = 901;
    const DOCUMENTO_FALTA_XML = 902;
    const DOCUMENTO_ERROR_SCHEMA = 903;

    // glosas de los estados
    private static $glosas = [
        // códigos de error para \sasco\LibreDTE\Sii::enviar()
        self::ENVIO_OK => 'Envío ok',
        self::ENVIO_USUARIO_INCORRECTO => 'Usuario no tiene permiso para enviar',
        self::ENVIO_TAMANIO_ARCHIVO => 'Error en tamaño del archivo (muy grande o muy chico)',
        self::ENVIO_ARCHIVO_CORTADO => 'Archivo cortado (tamaño es diferente al parámetro size)',
        self::ENVIO_NO_AUTENTICADO => 'No está autenticado',
        self::ENVIO_EMPRESA_NO_AUTORIZADA => 'Empresa no está autorizada a enviar archivos',
        self::ENVIO_ESQUEMA_INVALIDO => 'Esquema inválido',
        self::ENVIO_ERROR_FIRMA => 'Error en firma del documento',
        self::ENVIO_SISTEMA_BLOQUEADO => 'Sistema bloqueado',
        self::ENVIO_SSL_SIN_VERIFICAR => '¡No se está verificando el certificado SSL del SII en ambiente de producción!',
        self::ENVIO_ERROR_CURL => 'Falló el envío automático al SII. %s',
        self::ENVIO_ERROR_500 => 'Falló el envío automático al SII con error 500',
        self::ENVIO_ERROR_XML => 'Error al convertir respuesta de envío automático del SII a XML: %s',
        // códigos de error para \sasco\LibreDTE\Sii::request()
        self::REQUEST_ERROR_SOAP => 'Error al ejecutar consulta a webservice soap. %s',
        self::REQUEST_ERROR_BODY => 'No se obtuvo respuesta para %s en %d intentos',
        // códigos de error para \sasco\LibreDTE\Sii::cert()
        self::SII_ERROR_CERTIFICADO => 'No se pudo leer el certificado X.509 del SII número %d',
        // códigos de error para \sasco\LibreDTE\FirmaElectronica
        self::FIRMA_ERROR => '%s',
        // códigos de error para \sasco\LibreDTE\File::compress()
        self::COMPRESS_ERROR_READ => 'No se puede leer el archivo que se desea comprimir',
        self::COMPRESS_ERROR_ZIP => 'No fue posible crear el archivo ZIP',
        // códigos de error para \sasco\LibreDTE\Sii\Autenticacion
        self::AUTH_ERROR_SEMILLA => 'No fue posible obtener la semilla',
        self::AUTH_ERROR_FIRMA_SOLICITUD_TOKEN => 'No fue posible firmar getToken',
        self::AUTH_ERROR_TOKEN => 'No fue posible obtener el token de autenticacion',
        // códigos de error para \sasco\LibreDTE\Sii\Dte
        self::DTE_ERROR_GETDATOS => 'No fue posible convertir el XML a arreglo para extraer los datos del DTE',
        self::DTE_ERROR_TIPO => 'No existe la definición del tipo de documento para el código %d',
        self::DTE_ERROR_RANGO_FOLIO => 'Folio del DTE %s está fuera de rango',
        self::DTE_FALTA_FCHEMIS => 'Falta FchEmis del DTE %s',
        self::DTE_FALTA_MNTTOTAL => 'Falta MntTotal del DTE %s',
        self::DTE_ERROR_TIMBRE => 'No se pudo generar el timbre del DTE %s',
        self::DTE_ERROR_FIRMA => 'No se pudo generar la firma del DTE %s',
        // códigos de error para \sasco\LibreDTE\Sii\EnvioDte
        self::ENVIODTE_DTE_MAX => 'No es posible adjuntar más de %d DTEs al envío',
        self::ENVIODTE_TIPO_DTE_MAX => 'No puede adjuntar más de %d tipos de DTE diferentes al envío',
        self::ENVIODTE_FALTA_DTE => 'No hay ningún DTE agregado al envío',
        self::ENVIODTE_GETDOCUMENTOS_FALTA_XML => 'No hay XML, no es posible generar DTEs',
        // códigos de error para \sasco\LibreDTE\Sii\EnvioRecibos
        self::ENVIORECIBOS_FALTA_RECIBO => 'No hay recibos para generar',
        self::ENVIORECIBOS_FALTA_CARATULA => 'No se ha definido la carátula de EnvioRecibos',
        // códigos de error para \sasco\LibreDTE\Sii\Folios
        self::FOLIOS_ERROR_CHECK => 'Archivo de folios no pudo ser verificado',
        self::FOLIOS_ERROR_FIRMA => 'No fue posible validar firma del CAF',
        self::FOLIOS_ERROR_ENCRIPTAR => 'No fue posible encriptar con clave privada del CAF',
        self::FOLIOS_ERROR_DESENCRIPTAR => 'No fue posible desencriptar con clave pública del CAF',
        // códigos de error para \sasco\LibreDTE\Sii\RespuestaEnvio
        self::RESPUESTAENVIO_FALTA_RESPUESTA => 'No hay respuesta de envío ni documentos para generar',
        self::RESPUESTAENVIO_FALTA_CARATULA => 'No se ha asignado la carátula de RespuestaEnvio',
        // códigos de error para \sasco\LibreDTE\Sii\LibroGuia
        self::DOCUMENTO_ERROR_GENERAR_XML => 'No fue posible generar XML del %s',
        self::DOCUMENTO_FALTA_XML => 'No hay XML de %s que validar',
        self::DOCUMENTO_ERROR_SCHEMA => 'Error schema %s. %s',
    ];

    /**
     * Método que recupera la glosa del estado
     * @param codigo Código del error que se desea recuperar
     * @param args Argumentos que se usarán para reemplazar "máscaras" en glosa
     * @return Glosa del estado si existe o bien el mismo código del estado si no hay glosa
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-24
     */
    public static function get($codigo, $args = null)
    {
        // si no hay glosa asociada al código se entrega el mismo código
        if (!isset(self::$glosas[(int)$codigo]))
            return (int)$codigo;
        // si los argumentos no son un arreglo se obtiene arreglo a partir
        // de los argumentos pasados a la función
        if (!is_array($args))
            $args = array_slice(func_get_args(), 1);
        // entregar glosa
        return vsprintf(I18n::translate(self::$glosas[(int)$codigo], 'estados'), $args);
    }

}

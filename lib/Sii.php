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

namespace sasco\LibreDTE;

/**
 * Clase para acciones genéricas asociadas al SII de Chile
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-01-17
 */
class Sii
{

    private static $config = [
        'wsdl' => [
            '*' => 'https://{servidor}.sii.cl/DTEWS/{servicio}.jws?WSDL',
            'QueryEstDteAv' => 'https://{servidor}.sii.cl/DTEWS/services/{servicio}?WSDL',
            'wsDTECorreo' => 'https://{servidor}.sii.cl/DTEWS/services/{servicio}?WSDL',
        ],
        'servidor' => ['palena', 'maullin'], ///< servidores 0: producción, 1: certificación
        'certs' => [300, 100], ///< certificados 0: producción, 1: certificación
    ];

    const PRODUCCION = 0; ///< Constante para indicar ambiente de producción
    const CERTIFICACION = 1; ///< Constante para indicar ambiente de desarrollo

    const IVA = 19; ///< Tasa de IVA

    private static $retry = 10; ///< Veces que se reintentará conectar a SII al usar el servicio web
    private static $verificar_ssl = true; ///< Indica si se deberá verificar o no el certificado SSL del SII
    private static $ambiente = self::PRODUCCION; ///< Ambiente que se utilizará

    private static $direcciones_regionales = [
        'ARICA' => 'ARICA',
        'CAMARONES' => 'ARICA',
        'PUTRE' => 'ARICA',
        'GENERAL LAGOS' => 'ARICA',
        'IQUIQUE' => 'IQUIQUE',
        'PICA' => 'IQUIQUE',
        'POZO ALMONTE' => 'IQUIQUE',
        'HUARA' => 'IQUIQUE',
        'CAMIÑA' => 'IQUIQUE',
        'COLCHANE' => 'IQUIQUE',
        'ALTO HOSPICIO' => 'IQUIQUE',
        'ANTOFAGASTA' => 'ANTOFAGASTA',
        'MEJILLONES' => 'ANTOFAGASTA',
        'SIERRA GORDA' => 'ANTOFAGASTA',
        'CALAMA' => 'CALAMA',
        'SAN PEDRO DE ATACAMA' => 'CALAMA',
        'OLLAGUE' => 'CALAMA',
        'TOCOPILLA' => 'TOCOPILLA',
        'MARÍA ELENA' => 'TOCOPILLA',
        'TALTAL' => 'TALTAL',
        'COPIAPÓ' => 'COPIAPÓ',
        'CALDERA' => 'COPIAPÓ',
        'TIERRA AMARILLA' => 'COPIAPÓ',
        'CHAÑARAL' => 'CHAÑARAL',
        'DIEGO DE ALMAGRO' => 'CHAÑARAL',
        'VALLENAR' => 'VALLENAR',
        'FREIRINA' => 'VALLENAR',
        'HUASCO' => 'VALLENAR',
        'ALTO DEL CARMEN' => 'VALLENAR',
        'LA SERENA' => 'LA SERENA',
        'LA HIGUERA' => 'LA SERENA',
        'PAIHUANO' => 'LA SERENA',
        'ANDACOLLO' => 'LA SERENA',
        'VICUÑA' => 'LA SERENA',
        'OVALLE' => 'OVALLE',
        'MONTE PATRIA' => 'OVALLE',
        'PUNITAQUI' => 'OVALLE',
        'COMBARBALÁ' => 'OVALLE',
        'RÍO HURTADO' => 'OVALLE',
        'ILLAPEL' => 'ILLAPEL',
        'SALAMANCA' => 'ILLAPEL',
        'LOS VILOS' => 'ILLAPEL',
        'CANELA' => 'ILLAPEL',
        'COMQUIMBO' => 'COQUIMBO',
        'VALPARAÍSO' => 'VALPARAÍSO',
        'CASABLANCA' => 'VALPARAÍSO',
        'JUAN FERNÁNDEZ' => 'VALPARAÍSO',
        'ISLA DE PASCUA' => 'VALPARAÍSO',
        'CONCÓN' => 'VIÑA DEL MAR',
        'QUINTERO' => 'VIÑA DEL MAR',
        'PUCHUNCAVÍ' => 'VIÑA DEL MAR',
        'VIÑA DEL MAR' => 'VIÑA DEL MAR',
        'LA LIGUA' => 'LA LIGUA',
        'PETORCA' => 'LA LIGUA',
        'CABILDO' => 'LA LIGUA',
        'ZAPALLAR' => 'LA LIGUA',
        'PAPUDO' => 'LA LIGUA',
        'SAN ANTONIO' => 'SAN ANTONIO',
        'SANTO DOMINGO' => 'SAN ANTONIO',
        'CARTAGENA' => 'SAN ANTONIO',
        'EL TABO' => 'SAN ANTONIO',
        'EL QUISCO' => 'SAN ANTONIO',
        'ALGARROBO' => 'SAN ANTONIO',
        'QUILLOTA' => 'QUILLOTA',
        'NOGALES' => 'QUILLOTA',
        'HIJUELAS' => 'QUILLOTA',
        'LA CALERA' => 'QUILLOTA',
        'LA CRUZ' => 'QUILLOTA',
        'LIMACHE' => 'QUILLOTA',
        'OLMUÉ' => 'QUILLOTA',
        'SAN FELIPE' => 'SAN FELIPE',
        'PANQUEHUE' => 'SAN FELIPE',
        'CATEMU' => 'SAN FELIPE',
        'PUTAENDO' => 'SAN FELIPE',
        'SANTA MARÍA' => 'SAN FELIPE',
        'LLAY LLAY' => 'SAN FELIPE',
        'LOS ANDES' => 'LOS ANDES',
        'CALLE LARGA' => 'LOS ANDES',
        'SAN ESTEBAN' => 'LOS ANDES',
        'RINCONADA' => 'LOS ANDES',
        'VILLA ALEMANA' => 'VILLA ALEMANA',
        'QUILPUÉ' => 'VILLA ALEMANA',
        'RANCAGUA' => 'RANCAGUA',
        'MACHALÍ' => 'RANCAGUA',
        'GRANEROS' => 'RANCAGUA',
        'SAN FRANCISCO DE MOSTAZAL' => 'RANCAGUA',
        'DOÑIHUE' => 'RANCAGUA',
        'CODEGUA' => 'RANCAGUA',
        'RENGO' => 'RANCAGUA',
        'COLTAUCO' => 'RANCAGUA',
        'REQUINOA' => 'RANCAGUA',
        'OLIVAR' => 'RANCAGUA',
        'MALLOA' => 'RANCAGUA',
        'COINCO' => 'RANCAGUA',
        'QUINTA DE TILCOCO' => 'RANCAGUA',
        'SAN FERNANDO' => 'SAN FERNANDO',
        'CHIMBARONGO' => 'SAN FERNANDO',
        'NANCAGUA' => 'SAN FERNANDO',
        'PLACILLA' => 'SAN FERNANDO',
        'SANTA CRUZ' => 'SANTA CRUZ',
        'LOLOL' => 'SANTA CRUZ',
        'PALMILLA' => 'SANTA CRUZ',
        'PERALILLO' => 'SANTA CRUZ',
        'CHÉPICA' => 'SANTA CRUZ',
        'PUMANQUE' => 'SANTA CRUZ',
        'SAN VICENTE' => 'SAN VICENTE TAGUA TAGUA',
        'LAS CABRAS' => 'SAN VICENTE TAGUA TAGUA',
        'PEUMO' => 'SAN VICENTE TAGUA TAGUA',
        'PICHIDEGUA' => 'SAN VICENTE TAGUA TAGUA',
        'PICHILEMU' => 'PICHILEMU',
        'PAREDONES' => 'PICHILEMU',
        'MARCHIGUE' => 'PICHILEMU',
        'LITUECHE' => 'PICHILEMU',
        'LA ESTRELLA' => 'PICHILEMU',
        'TALCA' => 'TALCA',
        'SAN CLEMENTE' => 'TALCA',
        'PELARCO' => 'TALCA',
        'RÍO CLARO' => 'TALCA',
        'PENCAHUE' => 'TALCA',
        'MAULE' => 'TALCA',
        'CUREPTO' => 'TALCA',
        'SAN JAVIER' => 'TALCA',
        'LINARES' => 'LINARES',
        'YERBAS BUENAS' => 'LINARES',
        'COLBÚN' => 'LINARES',
        'LONGAVÍ' => 'LINARES',
        'VILLA ALEGRE' => 'LINARES',
        'CONSTITUCIÓN' => 'CONSTITUCIÓN',
        'EMPEDRADO' => 'CONSTITUCIÓN',
        'CAUQUENES' => 'CAUQUENES',
        'PELLUHUE' => 'CAUQUENES',
        'CHANCO' => 'CAUQUENES',
        'PARRAL' => 'PARRAL',
        'RETIRO' => 'PARRAL',
        'CURICÓ' => 'CURICÓ',
        'TENO' => 'CURICÓ',
        'ROMERAL' => 'CURICÓ',
        'MOLINA' => 'CURICÓ',
        'HUALAÑE' => 'CURICÓ',
        'SAGRADA FAMILIA' => 'CURICÓ',
        'LICANTÉN' => 'CURICÓ',
        'VICHUQUÉN' => 'CURICÓ',
        'RAUCO' => 'CURICÓ',
        'CONCEPCIÓN' => 'CONCEPCIÓN',
        'CHIGUAYANTE' => 'CONCEPCIÓN',
        'SAN PEDRO DE LA PAZ' => 'CONCEPCIÓN',
        'PENCO' => 'CONCEPCIÓN',
        'HUALQUI' => 'CONCEPCIÓN',
        'FLORIDA' => 'CONCEPCIÓN',
        'TOMÉ' => 'CONCEPCIÓN',
        'CORONEL' => 'CONCEPCIÓN',
        'LOTA' => 'CONCEPCIÓN',
        'SANTA JUANA' => 'CONCEPCIÓN',
        'ARAUCO' => 'CONCEPCIÓN',
        'CHILLÁN' => 'CHILLÁN',
        'PINTO' => 'CHILLÁN',
        'EL CARMEN' => 'CHILLÁN',
        'SAN IGNACIO' => 'CHILLÁN',
        'PEMUCO' => 'CHILLÁN',
        'YUNGAY' => 'CHILLÁN',
        'BULNES' => 'CHILLÁN',
        'QUILLÓN' => 'CHILLÁN',
        'RANQUIL' => 'CHILLÁN',
        'PORTEZUELO' => 'CHILLÁN',
        'COELEMU' => 'CHILLÁN',
        'TREHUACO' => 'CHILLÁN',
        'QUIRIHUE' => 'CHILLÁN',
        'COBQUECURA' => 'CHILLÁN',
        'NINHUE' => 'CHILLÁN',
        'CHILLÁN VIEJO' => 'CHILLÁN',
        'LOS ÁNGELES' => 'LOS ÁNGELES',
        'SANTA BARBARA' => 'LOS ÁNGELES',
        'LAJA' => 'LOS ÁNGELES',
        'QUILLECO' => 'LOS ÁNGELES',
        'NACIMIENTO' => 'LOS ÁNGELES',
        'NEGRETE' => 'LOS ÁNGELES',
        'MULCHÉN' => 'LOS ÁNGELES',
        'QUILACO' => 'LOS ÁNGELES',
        'YUMBEL' => 'LOS ÁNGELES',
        'CABRERO' => 'LOS ÁNGELES',
        'SAN ROSENDO' => 'LOS ÁNGELES',
        'TUCAPEL' => 'LOS ÁNGELES',
        'ANTUCO' => 'LOS ÁNGELES',
        'ALTO BÍO-BÍO' => 'LOS ÁNGELES',
        'SAN CARLOS' => 'SAN CARLOS',
        'SAN GREGORIO DE ÑINQUEN' => 'SAN CARLOS',
        'SAN NICOLÁS' => 'SAN CARLOS',
        'SAN FABIÁN DE ALICO' => 'SAN CARLOS',
        'TALCAHUANO' => 'TALCAHUANO',
        'HUALPÉN' => 'TALCAHUANO',
        'LEBU' => 'LEBU',
        'CURANILAHUE' => 'LEBU',
        'LOS ALAMOS' => 'LEBU',
        'CAÑETE' => 'LEBU',
        'CONTULMO' => 'LEBU',
        'TIRÚA' => 'LEBU',
        'TEMUCO' => 'TEMUCO',
        'VILCÚN' => 'TEMUCO',
        'FREIRE' => 'TEMUCO',
        'CUNCO' => 'TEMUCO',
        'LAUTARO' => 'TEMUCO',
        'PERQUENCO' => 'TEMUCO',
        'GALVARINO' => 'TEMUCO',
        'NUEVA IMPERIAL' => 'TEMUCO',
        'CARAHUE' => 'TEMUCO',
        'PUERTO SAAVEDRA' => 'TEMUCO',
        'PITRUFQUÉN' => 'TEMUCO',
        'GORBEA' => 'TEMUCO',
        'TOLTÉN' => 'TEMUCO',
        'LONCOCHE' => 'TEMUCO',
        'MELIPEUCO' => 'TEMUCO',
        'TEODORO SCHMIDT' => 'TEMUCO',
        'PADRE LAS CASAS' => 'TEMUCO',
        'CHOLCHOL' => 'TEMUCO',
        'ANGOL' => 'ANGOL',
        'PURÉN' => 'ANGOL',
        'LOS SAUCES' => 'ANGOL',
        'REINACO' => 'ANGOL',
        'COLLIPULLI' => 'ANGOL',
        'ERCILLA' => 'ANGOL',
        'VICTORIA' => 'VICTORIA',
        'TRAIGUÉN' => 'VICTORIA',
        'LUMACO' => 'VICTORIA',
        'CURACAUTÍN' => 'VICTORIA',
        'LONQUIMAY' => 'VICTORIA',
        'VILLARRICA' => 'VILLARRICA',
        'PUCÓN' => 'VILLARRICA',
        'CURARREHUE' => 'VILLARRICA',
        'VALDIVIA' => 'VALDIVIA',
        'MARIQUINA' => 'VALDIVIA',
        'LANCO' => 'LANCO',
        'MÁFIL' => 'VALDIVIA',
        'CORRAL' => 'VALDIVIA',
        'LOS LAGOS' => 'VALDIVIA',
        'PAILLACO' => 'VALDIVIA',
        'PANGUIPULLI' => 'PANGUIPULLI',
        'LA UNIÓN' => 'LA UNIÓN',
        'FUTRONO' => 'VALDIVIA',
        'RÍO BUENO' => 'LA UNIÓN',
        'LAGO RANCO' => 'LA UNIÓN',
        'PUERTO MONTT' => 'PUERTO MONTT',
        'CALBUCO' => 'PUERTO MONTT',
        'MAULLÍN' => 'PUERTO MONTT',
        'LOS MUERMOS' => 'PUERTO MONTT',
        'HUALAIHUÉ' => 'PUERTO MONTT',
        'PUERTO VARAS' => 'PUERTO VARAS',
        'COCHAMÓ' => 'PUERTO VARAS',
        'FRESIA' => 'PUERTO VARAS',
        'LLANQUIHUE' => 'PUERTO VARAS',
        'FRUTILLAR' => 'PUERTO VARAS',
        'ANCUD' => 'ANCUD',
        'QUEMCHI' => 'ANCUD',
        'OSORNO' => 'OSORNO',
        'PUYEHUE' => 'OSORNO',
        'PURRANQUE' => 'OSORNO',
        'RÍO NEGRO' => 'OSORNO',
        'SAN PABLO' => 'OSORNO',
        'SAN JUAN DE LA COSTA' => 'OSORNO',
        'PUERTO OCTAY' => 'OSORNO',
        'CASTRO' => 'CASTRO',
        'CURACO DE VÉLEZ' => 'CASTRO',
        'CHOCHI' => 'CASTRO',
        'DALCAHUE' => 'CASTRO',
        'PUQUELDÓN' => 'CASTRO',
        'QUEILÉN' => 'CASTRO',
        'QUELLÓN' => 'CASTRO',
        'CHAITÉN' => 'CHAITÉN',
        'PALENA' => 'CHAITÉN',
        'FUTALEUFÚ' => 'CHAITÉN',
        'COYHAIQUE' => 'COYHAIQUE',
        'RÍO IBAÑEZ' => 'COYHAIQUE',
        'O`HIGGINS' => 'COCHRANE',
        'TORTEL' => 'COCHRANE',
        'AYSÉN' => 'AYSÉN',
        'CISNES' => 'AYSÉN',
        'LAGO VERDE' => 'AYSÉN',
        'GUAITECAS' => 'AYSÉN',
        'CHILE CHICO' => 'CHILE CHICO',
        'COCHRANE' => 'COCHRANE',
        'GUADAL' => 'COCHRANE',
        'PUERTO BELTRAND' => 'COCHRANE',
        'PUNTA ARENAS' => 'PUNTA ARENAS',
        'RÍO VERDE' => 'PUNTA ARENAS',
        'SAN GREGORIO' => 'PUNTA ARENAS',
        'LAGUNA BLANCA' => 'PUNTA ARENAS',
        'CABO DE HORNOS' => 'PUNTA ARENAS',
        'PUERTO NATALES' => 'PUERTO NATALES',
        'TORRES DEL PAINE' => 'PUERTO NATALES',
        'PORVENIR' => 'PORVENIR',
        'PRIMAVERA' => 'PORVENIR',
        'TIMAUKEL' => 'PORVENIR',
        'INDEPENDENCIA' => 'SANTIAGO NORTE',
        'RECOLETA' => 'SANTIAGO NORTE',
        'HUECHURABA' => 'SANTIAGO NORTE',
        'CONCHALÍ' => 'SANTIAGO NORTE',
        'QUILICURA' => 'SANTIAGO NORTE',
        'COLINA' => 'SANTIAGO NORTE',
        'LAMPA' => 'SANTIAGO NORTE',
        'TILTIL' => 'SANTIAGO NORTE',
        'SANTIAGO' => 'SANTIAGO CENTRO',
        'CERRO NAVIA' => 'SANTIAGO PONIENTE',
        'CURACAVÍ' => 'SANTIAGO PONIENTE',
        'ESTACIÓN CENTRAL' => 'SANTIAGO PONIENTE',
        'LO PRADO' => 'SANTIAGO PONIENTE',
        'PUDAHUEL' => 'SANTIAGO PONIENTE',
        'QUINTA NORMAL' => 'SANTIAGO PONIENTE',
        'RENCA' => 'SANTIAGO PONIENTE',
        'MELIPILLA' => 'MELIPILLA',
        'SAN PEDRO' => 'MELIPILLA',
        'ALHUÉ' => 'MELIPILLA',
        'MARÍA PINTO' => 'MELIPILLA',
        'MAIPÚ' => 'MAIPÚ',
        'CERRILLOS' => 'MAIPÚ',
        'PADRE HURTADO' => 'MAIPÚ',
        'PEÑAFLOR' => 'MAIPÚ',
        'TALAGANTE' => 'MAIPÚ',
        'EL MONTE' => 'MAIPÚ',
        'ISLA DE MAIPO' => 'MAIPÚ',
        'LAS CONDES' => 'SANTIAGO ORIENTE',
        'VITACURA' => 'SANTIAGO ORIENTE',
        'LO BARNECHEA' => 'SANTIAGO ORIENTE',
        'ÑUÑOA' => 'ÑUÑOA',
        'LA REINA' => 'ÑUÑOA',
        'MACUL' => 'ÑUÑOA',
        'PEÑALOLÉN' => 'ÑUÑOA',
        'PROVIDENCIA' => 'PROVIDENCIA',
        'SAN MIGUEL' => 'SANTIAGO SUR',
        'LA CISTERNA' => 'SANTIAGO SUR',
        'SAN JOAQUÍN' => 'SANTIAGO SUR',
        'PEDRO AGUIRRE CERDA' => 'SANTIAGO SUR',
        'LO ESPEJO' => 'SANTIAGO SUR',
        'LA GRANJA' => 'SANTIAGO SUR',
        'LA PINTANA' => 'SANTIAGO SUR',
        'SAN RAMÓN' => 'SANTIAGO SUR',
        'LA FLORIDA' => 'LA FLORIDA',
        'PUENTE ALTO' => 'LA FLORIDA',
        'PIRQUE' => 'LA FLORIDA',
        'SAN JOSÉ DE MAIPO' => 'LA FLORIDA',
        'SAN BERNARDO' => 'SAN BERNARDO',
        'CALERA DE TANGO' => 'SAN BERNARDO',
        'EL BOSQUE' => 'SAN BERNARDO',
        'BUIN' => 'BUIN',
        'PAINE' => 'BUIN',
    ]; /// Direcciones regionales del SII según la comuna

    /**
     * Método que permite asignar el nombre del servidor del SII que se
     * usará para las consultas al SII
     * @param servidor Servidor que se usará: maullin (certificación) o palena (producción)
     * @param certificacion Permite definir si se está cambiando el servidor de certificación o el de producción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-14
     */
    public static function setServidor($servidor = 'maullin', $certificacion = Sii::CERTIFICACION)
    {
        self::$config['servidor'][$certificacion] = $servidor;
    }

    /**
     * Método que entrega el nombre del servidor a usar según el ambiente
     * @param ambiente Ambiente que se desea obtener el servidor, si es null se autodetectará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-01
     */
    public static function getServidor($ambiente = null)
    {
        return self::$config['servidor'][self::getAmbiente($ambiente)];
    }

    /**
     * Método que entrega la URL de un recurso en el SII según el ambiente que se esté usando
     * @param recurso Recurso del sitio del SII que se desea obtener la URL
     * @param ambiente Ambiente que se desea obtener el servidor, si es null se autodetectará
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-05-18
     */
    public static function getURL($recurso, $ambiente = null)
    {
        $ambiente = self::getAmbiente($ambiente);
        // si es anulación masiva de folios
        if ($recurso=='/anulacionMsvDteInternet') {
            $servidor = $ambiente ? 'www4c' : 'www4';
        }
        // servidor estandar (maullin o palena)
        else {
            $servidor = self::getServidor($ambiente);
        }
        // entregar URL
        return 'https://'.$servidor.'.sii.cl'.$recurso;
    }

    /**
     * Método para obtener el WSDL
     *
     * \code{.php}
     *   $wsdl = \sasco\LibreDTE\Sii::wsdl('CrSeed'); // WSDL para pedir semilla
     * \endcode
     *
     * Para forzar el uso del WSDL de certificación hay dos maneras, una es
     * pasando un segundo parámetro al método get con valor Sii::CERTIFICACION:
     *
     * \code{.php}
     *   $wsdl = \sasco\LibreDTE\Sii::wsdl('CrSeed', \sasco\LibreDTE\Sii::CERTIFICACION);
     * \endcode
     *
     * La otra manera, para evitar este segundo parámetro, es asignar el valor a
     * través de la configuración:
     *
     * \code{.php}
     *   \sasco\LibreDTE\Sii::setAmbiente(\sasco\LibreDTE\Sii::CERTIFICACION);
     * \endcode
     *
     * @param servicio Servicio por el cual se está solicitando su WSDL
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION o null (para detección automática)
     * @return URL del WSDL del servicio según ambiente solicitado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-06-11
     */
    public static function wsdl($servicio, $ambiente = null)
    {
        // determinar ambiente que se debe usar
        $ambiente = self::getAmbiente($ambiente);
        // entregar WSDL local (modificados para ambiente de certificación)
        if ($ambiente==self::CERTIFICACION) {
            $wsdl = dirname(dirname(__FILE__)).'/wsdl/'.self::$config['servidor'][$ambiente].'/'.$servicio.'.jws';
            if (is_readable($wsdl))
                return $wsdl;
        }
        // entregar WSDL oficial desde SII
        $location = isset(self::$config['wsdl'][$servicio]) ? self::$config['wsdl'][$servicio] : self::$config['wsdl']['*'];
        $wsdl = str_replace(
            ['{servidor}', '{servicio}'],
            [self::$config['servidor'][$ambiente], $servicio],
            $location
        );
        // entregar wsdl
        return $wsdl;
    }

    /**
     * Método para realizar una solicitud al servicio web del SII
     * @param wsdl Nombre del WSDL que se usará
     * @param request Nombre de la función que se ejecutará en el servicio web
     * @param args Argumentos que se pasarán al servicio web
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Objeto SimpleXMLElement con la espuesta del servicio web consultado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-02-12
     */
    public static function request($wsdl, $request, $args = null, $retry = null)
    {
        if (is_numeric($args)) {
            $retry = (int)$args;
            $args = null;
        }
        if (!$retry) {
            $retry = self::$retry;
        }
        if ($args and !is_array($args)) {
            $args = [$args];
        }
        $options = ['cache_wsdl' => WSDL_CACHE_DISK, 'keep_alive' => false];
        if (!self::$verificar_ssl) {
            if (self::getAmbiente()==self::PRODUCCION) {
                $msg = Estado::get(Estado::ENVIO_SSL_SIN_VERIFICAR);
                \sasco\LibreDTE\Log::write(Estado::ENVIO_SSL_SIN_VERIFICAR, $msg, LOG_WARNING);
            }
            $options['stream_context'] = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
        }
        try {
            $soap = new \SoapClient(self::wsdl($wsdl), $options);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (isset($e->getTrace()[0]['args'][1]) and is_string($e->getTrace()[0]['args'][1])) {
                $msg .= ': '.$e->getTrace()[0]['args'][1];
            }
            \sasco\LibreDTE\Log::write(Estado::REQUEST_ERROR_SOAP, Estado::get(Estado::REQUEST_ERROR_SOAP, $msg));
            return false;
        }
        for ($i=0; $i<$retry; $i++) {
            try {
                if ($args) {
                    $body = call_user_func_array([$soap, $request], $args);
                } else {
                    $body = $soap->$request();
                }
                break;
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                if (isset($e->getTrace()[0]['args'][1]) and is_string($e->getTrace()[0]['args'][1])) {
                    $msg .= ': '.$e->getTrace()[0]['args'][1];
                }
                \sasco\LibreDTE\Log::write(Estado::REQUEST_ERROR_SOAP, Estado::get(Estado::REQUEST_ERROR_SOAP, $msg));
                $body = null;
                usleep(200000); // pausa de 0.2 segundos antes de volver a intentar el envío
            }
        }
        if ($body===null) {
            \sasco\LibreDTE\Log::write(Estado::REQUEST_ERROR_BODY, Estado::get(Estado::REQUEST_ERROR_BODY, $wsdl, $retry));
            return false;
        }
        return new \SimpleXMLElement($body, LIBXML_COMPACT);
    }

    /**
     * Método que permite indicar si se debe o no verificar el certificado SSL
     * del SII
     * @param verificar =true si se quiere verificar certificado, =false en caso que no (por defecto se verifica)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-16
     */
    public static function setVerificarSSL($verificar = true)
    {
        self::$verificar_ssl = $verificar;
    }

    /**
     * Método que indica si se está o no verificando el SSL en las conexiones al SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-05-11
     */
    public static function getVerificarSSL()
    {
        return self::$verificar_ssl;
    }

    /**
     * Método que realiza el envío de un DTE al SII
     * Referencia: http://www.sii.cl/factura_electronica/factura_mercado/envio.pdf
     * @param usuario RUN del usuario que envía el DTE
     * @param empresa RUT de la empresa emisora del DTE
     * @param dte Documento XML con el DTE que se desea enviar a SII
     * @param token Token de autenticación automática ante el SII
     * @param gzip Permite enviar el archivo XML comprimido al servidor
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Respuesta XML desde SII o bien null si no se pudo obtener respuesta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-10-23
     */
    public static function enviar($usuario, $empresa, $dte, $token, $gzip = false, $retry = null)
    {
        // definir datos que se usarán en el envío
        list($rutSender, $dvSender) = explode('-', str_replace('.', '', $usuario));
        list($rutCompany, $dvCompany) = explode('-', str_replace('.', '', $empresa));
        if (strpos($dte, '<?xml')===false) {
            $dte = '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n".$dte;
        }
        do {
            $file = sys_get_temp_dir().'/dte_'.md5(microtime().$token.$dte).'.'.($gzip?'gz':'xml');
        } while (file_exists($file));
        if ($gzip) {
            $dte = gzencode($dte);
            if ($dte===false) {
                \sasco\LibreDTE\Log::write(Estado::ENVIO_ERROR_GZIP, Estado::get(Estado::ENVIO_ERROR_GZIP));
                return false;
            }
        }
        file_put_contents($file, $dte);
        $data = [
            'rutSender' => $rutSender,
            'dvSender' => $dvSender,
            'rutCompany' => $rutCompany,
            'dvCompany' => $dvCompany,
            'archivo' => curl_file_create(
                $file,
                $gzip ? 'application/gzip' : 'application/xml',
                basename($file)
            ),
        ];
        // definir reintentos si no se pasaron
        if (!$retry) {
            $retry = self::$retry;
        }
        // crear sesión curl con sus opciones
        $curl = curl_init();
        $header = [
            'User-Agent: Mozilla/4.0 (compatible; PROG 1.0; LibreDTE)',
            'Referer: https://libredte.cl',
            'Cookie: TOKEN='.$token,
        ];
        $url = 'https://'.self::$config['servidor'][self::getAmbiente()].'.sii.cl/cgi_dte/UPL/DTEUpload';
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // si no se debe verificar el SSL se asigna opción a curl, además si
        // se está en el ambiente de producción y no se verifica SSL se
        // generará una entrada en el log
        if (!self::$verificar_ssl) {
            if (self::getAmbiente()==self::PRODUCCION) {
                $msg = Estado::get(Estado::ENVIO_SSL_SIN_VERIFICAR);
                \sasco\LibreDTE\Log::write(Estado::ENVIO_SSL_SIN_VERIFICAR, $msg, LOG_WARNING);
            }
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        // enviar XML al SII
        for ($i=0; $i<$retry; $i++) {
            $response = curl_exec($curl);
            if ($response and $response!='Error 500') {
                break;
            }
        }
        unlink($file);
        // verificar respuesta del envío y entregar error en caso que haya uno
        if (!$response or $response=='Error 500') {
            if (!$response) {
                \sasco\LibreDTE\Log::write(Estado::ENVIO_ERROR_CURL, Estado::get(Estado::ENVIO_ERROR_CURL, curl_error($curl)));
            }
            if ($response=='Error 500') {
                \sasco\LibreDTE\Log::write(Estado::ENVIO_ERROR_500, Estado::get(Estado::ENVIO_ERROR_500));
            }
            return false;
        }
        // cerrar sesión curl
        curl_close($curl);
        // crear XML con la respuesta y retornar
        try {
            $xml = new \SimpleXMLElement($response, LIBXML_COMPACT);
        } catch (Exception $e) {
            \sasco\LibreDTE\Log::write(Estado::ENVIO_ERROR_XML, Estado::get(Estado::ENVIO_ERROR_XML, $e->getMessage()));
            return false;
        }
        if ($xml->STATUS!=0) {
            \sasco\LibreDTE\Log::write(
                $xml->STATUS,
                Estado::get($xml->STATUS).(isset($xml->DETAIL)?'. '.implode("\n", (array)$xml->DETAIL->ERROR):'')
            );
        }
        return $xml;
    }

    /**
     * Método para obtener la clave pública (certificado X.509) del SII
     *
     * \code{.php}
     *   $pub_key = \sasco\LibreDTE\Sii::cert(100); // Certificado IDK 100 (certificación)
     * \endcode
     *
     * @param idk IDK de la clave pública del SII. Si no se indica se tratará de determinar con el ambiente que se esté usando
     * @return Contenido del certificado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-16
     */
    public static function cert($idk = null)
    {
        // si se pasó un idk y existe el archivo asociado se entrega
        if ($idk) {
            $cert = dirname(dirname(__FILE__)).'/certs/'.$idk.'.cer';
            if (is_readable($cert)) {
                return file_get_contents($cert);
            }
        }
        // buscar certificado y entregar si existe o =false si no
        $ambiente = self::getAmbiente();
        $cert = dirname(dirname(__FILE__)).'/certs/'.self::$config['certs'][$ambiente].'.cer';
        if (!is_readable($cert)) {
            \sasco\LibreDTE\Log::write(Estado::SII_ERROR_CERTIFICADO, Estado::get(Estado::SII_ERROR_CERTIFICADO, self::$config['certs'][$ambiente]));
            return false;
        }
        return file_get_contents($cert);
    }

    /**
     * Método que asigna el ambiente que se usará por defecto (si no está
     * asignado con la constante _LibreDTE_CERTIFICACION_)
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION
     * @warning No se está verificando SSL en ambiente de certificación
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-28
     */
    public static function setAmbiente($ambiente = self::PRODUCCION)
    {
        $ambiente = $ambiente ? self::CERTIFICACION : self::PRODUCCION;
        if ($ambiente==self::CERTIFICACION) {
            self::setVerificarSSL(false);
        }
        self::$ambiente = $ambiente;
    }

    /**
     * Método que determina el ambiente que se debe utilizar: producción o
     * certificación
     * @param ambiente Ambiente a usar: Sii::PRODUCCION o Sii::CERTIFICACION o null (para detección automática)
     * @return Ambiente que se debe utilizar
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-07
     */
    public static function getAmbiente($ambiente = null)
    {
        if ($ambiente===null) {
            if (defined('_LibreDTE_CERTIFICACION_'))
                $ambiente = (int)_LibreDTE_CERTIFICACION_;
            else
                $ambiente = self::$ambiente;
        }
        return $ambiente;
    }

    /**
     * Método que entrega la tasa de IVA vigente
     * @return Tasa de IVA vigente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-03
     */
    public static function getIVA()
    {
        return self::IVA;
    }

    /**
     * Método que entrega la dirección regional según la comuna que se esté
     * consultando
     * @param comuna de la sucursal del emior o bien código de la sucursal del SII
     * @return Dirección regional del SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-11-07
     */
    public static function getDireccionRegional($comuna)
    {
        if (!$comuna) {
            return 'N.N.';
        }
        if (!is_numeric($comuna)) {
            $direccion = mb_strtoupper($comuna, 'UTF-8');
            return isset(self::$direcciones_regionales[$direccion]) ? self::$direcciones_regionales[$direccion] : $direccion;
        }
        return 'SUC '.$comuna;
    }

}

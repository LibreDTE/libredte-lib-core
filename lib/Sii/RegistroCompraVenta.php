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

namespace sasco\LibreDTE\Sii;

/**
 * Clase con las acciones asociadas al registro de compras y ventas del SII
 * Este registro reemplaza a LibroCompraVenta (IECV)
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2017-09-11
 */
class RegistroCompraVenta
{

    public static $dtes = [
        33 => 'Factura electrónica',
        34 => 'Factura no afecta o exenta electrónica',
        43 => 'Liquidación factura electrónica',
    ]; ///< Documentos que tienen acuse de recibo

    public static $acciones = [
        'ERM' => 'Otorga recibo de mercaderías o servicios',
        'ACD' => 'Acepta contenido del documento',
        'RCD' => 'Reclamo al contenido del documento',
        'RFP' => 'Reclamo por falta parcial de mercaderías',
        'RFT' => 'Reclamo por falta total de mercaderías',
    ]; ///< Posibles acciones a que tiene asociadas un DTE

    public static $eventos = [
        'A' => 'No reclamado en plazo (recepción automática)',
        'C' => 'Recibo otorgado por el receptor',
        'P' => 'Forma de pago al contado',
        'R' => 'Reclamado',
    ];

    public static $tipo_transacciones = [
        1 => 'Compras del giro',
        2 => 'Compras en supermercados o comercios similares',
        3 => 'Adquisición de bienes raíces',
        4 => 'Compra de activo fijo',
        5 => 'Compras con IVA uso común',
        6 => 'Compras sin derecho a crédito',
    ]; ///< Tipos de transacciones o caracterizaciones/clasificaciones de las compras

    public static $estados_ok = [
        7,  // Evento registrado previamente
        8,  // Pasados 8 días después de la recepción no es posible registrar reclamos o eventos
        27, // No se puede registrar un evento (acuse de recibo, reclamo o aceptación de contenido) de un DTE pagado al contado o gratuito
    ]; ///< Código de estado de respuesta de la asignación de estado que son considerados como OK

    private static $wsdl = [
        'https://ws1.sii.cl/WSREGISTRORECLAMODTE/registroreclamodteservice?wsdl',
        'https://ws2.sii.cl/WSREGISTRORECLAMODTECERT/registroreclamodteservice?wsdl',
    ]; ///< Rutas de los WSDL de producción y certificación

    private $token; ///< Token que se usará en la sesión de consultas al RCV

    /**
     * Constructor, obtiene el token de la sesión y lo guarda
     * @param Firma Objeto con la firma electrónica
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-08-28
     */
    public function __construct(\sasco\LibreDTE\FirmaElectronica $Firma)
    {
        $this->token = \sasco\LibreDTE\Sii\Autenticacion::getToken($Firma);
        if (!$this->token) {
            throw new \Exception('No fue posible obtener el token para la sesión del RCV');
        }
    }

    /**
     * Método que ingresa una acción al registro de compr/venta en el SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-08-29
     */
    public function ingresarAceptacionReclamoDoc($rut, $dv, $dte, $folio, $accion)
    {
        // ingresar acción al DTE
        $r = $this->request('ingresarAceptacionReclamoDoc', [
            'rutEmisor' => $rut,
            'dvEmisor' => $dv,
            'tipoDoc' => $dte,
            'folio' => $folio,
            'accionDoc' => $accion,
        ]);
        // si no se pudo recuperar error
        if ($r===false) {
            return false;
        }
        // entregar resultado del ingreso
        return [
            'codigo' => $r->codResp,
            'glosa' => $r->descResp,
        ];
    }

    /**
     * Método que entrega los eventos asociados a un DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-08-28
     */
    public function listarEventosHistDoc($rut, $dv, $dte, $folio)
    {
        // consultar eventos del DTE
        $r = $this->request('listarEventosHistDoc', [
            'rutEmisor' => $rut,
            'dvEmisor' => $dv,
            'tipoDoc' => $dte,
            'folio' => $folio,
        ]);
        // si no se pudo recuperar error
        if ($r===false) {
            return false;
        }
        // si hubo error informar
        if (!in_array($r->codResp, [8, 15, 16])) {
            throw new \Exception($r->descResp);
        }
        // entregar eventos del DTE
        $eventos = [];
        if (!empty($r->listaEventosDoc)) {
            if (!is_array($r->listaEventosDoc)) {
                $r->listaEventosDoc = [$r->listaEventosDoc];
            }
            foreach ($r->listaEventosDoc as $evento) {
                $eventos[] = [
                    'codigo' => $evento->codEvento,
                    'glosa' => $evento->descEvento,
                    'responsable' => $evento->rutResponsable.'-'.$evento->dvResponsable,
                    'fecha' => $evento->fechaEvento,
                ];
            }
        }
        return $eventos;
    }

    /**
     * Entrega información de cesión para el DTE, si es posible o no cederlo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-08-28
     */
    public function consultarDocDteCedible($rut, $dv, $dte, $folio)
    {
        // consultar eventos del DTE
        $r = $this->request('consultarDocDteCedible', [
            'rutEmisor' => $rut,
            'dvEmisor' => $dv,
            'tipoDoc' => $dte,
            'folio' => $folio,
        ]);
        // si no se pudo recuperar error
        if ($r===false) {
            return false;
        }
        // entregar información de cesión para el DTE
        return [
            'codigo' => $r->codResp,
            'glosa' => $r->descResp,
        ];
    }

    /**
     *
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-21
     */
    public function consultarFechaRecepcionSii($rut, $dv, $dte, $folio)
    {
        // consultar eventos del DTE
        $r = $this->request('consultarFechaRecepcionSii', [
            'rutEmisor' => $rut,
            'dvEmisor' => $dv,
            'tipoDoc' => $dte,
            'folio' => $folio,
        ]);
        // si no se pudo recuperar error
        if (!$r) {
            return false;
        }
        // armar y entregar fecha
        list($dia, $hora) = explode(' ', $r);
        list($d, $m, $Y) = explode('-', $dia);
        return $Y.'-'.$m.'-'.$d.' '.$hora;
    }

    /**
     * Método para realizar una solicitud al servicio web del SII
     * @param request Nombre de la función que se ejecutará en el servicio web
     * @param args Argumentos que se pasarán al servicio web
     * @param retry Intentos que se realizarán como máximo para obtener respuesta
     * @return Objeto o String con la respuesta (depende servicio web)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-08-28
     */
    private function request($request, $args, $retry = 10)
    {
        if (!\sasco\LibreDTE\Sii::getVerificarSSL()) {
            if (\sasco\LibreDTE\Sii::getAmbiente()==\sasco\LibreDTE\Sii::PRODUCCION) {
                $msg = \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::ENVIO_SSL_SIN_VERIFICAR);
                \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::ENVIO_SSL_SIN_VERIFICAR, $msg, LOG_WARNING);
            }
            $options = ['stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ])];
        } else {
            $options = [];
        }
        try {
            $wsdl = self::$wsdl[\sasco\LibreDTE\Sii::getAmbiente()];
            $soap = new \SoapClient($wsdl, $options);
            $soap->__setCookie('TOKEN', $this->token);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (isset($e->getTrace()[0]['args'][1]) and is_string($e->getTrace()[0]['args'][1])) {
                $msg .= ': '.$e->getTrace()[0]['args'][1];
            }
            \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::REQUEST_ERROR_SOAP, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::REQUEST_ERROR_SOAP, $msg));
            return false;
        }
        for ($i=0; $i<$retry; $i++) {
            try {
                $body = call_user_func_array([$soap, $request], $args);
                break;
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                if (isset($e->getTrace()[0]['args'][1]) and is_string($e->getTrace()[0]['args'][1])) {
                    $msg .= ': '.$e->getTrace()[0]['args'][1];
                }
                \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::REQUEST_ERROR_SOAP, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::REQUEST_ERROR_SOAP, $msg));
                $body = null;
            }
        }
        if ($body===null) {
            \sasco\LibreDTE\Log::write(\sasco\LibreDTE\Estado::REQUEST_ERROR_BODY, \sasco\LibreDTE\Estado::get(\sasco\LibreDTE\Estado::REQUEST_ERROR_BODY, $wsdl, $retry));
            return false;
        }
        return $body;
    }

}

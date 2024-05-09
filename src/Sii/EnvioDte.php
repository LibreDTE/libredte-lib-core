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

namespace libredte\lib\Sii;

/**
 * Clase que representa el envío de un DTE.
 * El envío puede ser al SII, al receptor, u a otro destinatario.
 * Comúnmente este envío se le llama "sobre" porque permite agregar
 * múltiples DTE en su interior.
 */
class EnvioDte extends \libredte\lib\Sii\Base\Envio
{

    private $tipo = null; ///< =0 DTE, =1 boleta
    private $config = [
        //                  0: DTE              1: boleta
        // máxima cantidad de tipos de documentos en el envío
        'SubTotDTE_max' => [20,                 2],
        // máxima cantidad de DTE en un envío
        'DTE_max'       => [2000,               1000],
        // Tag para el envío, según si son Boletas o no
        'tipos'         => ['EnvioDTE',         'EnvioBOLETA'],
        // Schema (XSD) que se deberá usar para validar según si son boletas o no
        'schemas'       => ['EnvioDTE_v10',     'EnvioBOLETA_v11'],
    ]; ///< Configuración/reglas para el documento XML
    private $dtes = []; ///< Objetos con los DTE que se enviarán en el "sobre".

    /**
     * Método que agrega un DTE al listado que se enviará.
     * @param DTE Objeto del DTE.
     * @return bool =true si se pudo agregar el DTE o =false si no se agregó por exceder el límite de un envío o porque no coincide el tipo del DTE con tipo del envío.
     */
    public function agregar(Dte $DTE): bool
    {
        $tipoDte = (int)$DTE->esBoleta();
        // determinar el tipo del envío (DTE o boleta)
        if ($this->tipo === null) {
            $this->tipo = $tipoDte;
        }
        // validar que el tipo de documento sea del tipo que se espera
        else if ($this->tipo != $tipoDte) {
            return false;
        }
        //
        if (isset($this->dtes[$this->config['DTE_max'][$this->tipo] - 1])) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::ENVIODTE_DTE_MAX,
                \libredte\lib\Estado::get(
                    \libredte\lib\Estado::ENVIODTE_DTE_MAX,
                    $this->config['DTE_max'][$this->tipo]
                )
            );
            return false;
        }
        $this->dtes[] = $DTE;
        return true;
    }

    /**
     * Método para asignar la caratula.
     * @param caratula Arreglo con datos del envío: RutEnvia, FchResol y NroResol.
     * @return bool =true si se logró asignar la carátula.
     */
    public function setCaratula(array $caratula): bool
    {
        // si no hay DTEs para generar entregar falso
        if (!isset($this->dtes[0])) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::ENVIODTE_FALTA_DTE,
                \libredte\lib\Estado::get(\libredte\lib\Estado::ENVIODTE_FALTA_DTE)
            );
            return false;
        }
        // si se agregaron demasiados DTE error
        $SubTotDTE = $this->getSubTotDTE();
        if (isset($SubTotDTE[$this->config['SubTotDTE_max'][$this->tipo]])) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::ENVIODTE_TIPO_DTE_MAX,
                \libredte\lib\Estado::get(
                    \libredte\lib\Estado::ENVIODTE_TIPO_DTE_MAX,
                    $this->config['SubTotDTE_max'][$this->tipo]
                )
            );
            return false;
        }
        // generar caratula
        $this->caratula = array_merge([
            '@attributes' => [
                'version' => '1.0'
            ],
            'RutEmisor' => $this->dtes[0]->getEmisor(),
            'RutEnvia' => isset($this->Firma) ? $this->Firma->getID() : false,
            'RutReceptor' => $this->dtes[0]->getReceptor(),
            'FchResol' => '',
            'NroResol' => '',
            'TmstFirmaEnv' => date('Y-m-d\TH:i:s'),
            'SubTotDTE' => $SubTotDTE,
        ], $caratula);
        return true;
    }

    /**
     * Método que realiza el envío del sobre con el o los DTEs al SII.
     * @return int|false Track ID del envío o =false si hubo algún problema al enviar el documento.
     */
    public function enviar(?int $retry = null, bool $gzip = false)
    {
        // si es boleta no se envía al SII
        if ($this->tipo) {
            return false;
        }
        // enviar al SII
        return parent::enviar($retry, $gzip);
    }

    /**
     * Método que genera el XML para el envío del DTE al SII.
     * @return string|false XML con el envio del DTE firmado o =false si no se pudo generar o firmar el envío.
     */
    public function generar()
    {
        // si ya se había generado se entrega directamente
        if ($this->xml_data) {
            return $this->xml_data;
        }
        // si no hay DTEs para generar entregar falso
        if (!isset($this->dtes[0])) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::ENVIODTE_FALTA_DTE,
                \libredte\lib\Estado::get(\libredte\lib\Estado::ENVIODTE_FALTA_DTE)
            );
            return false;
        }
        // genear XML del envío
        $xmlEnvio = (new \libredte\lib\XML())->generate([
            $this->config['tipos'][$this->tipo] => [
                '@attributes' => [
                    'xmlns' => 'http://www.sii.cl/SiiDte',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'http://www.sii.cl/SiiDte '
                        . $this->config['schemas'][$this->tipo] . '.xsd',
                    'version' => '1.0'
                ],
                'SetDTE' => [
                    '@attributes' => [
                        'ID' => 'LibreDTE_SetDoc'
                    ],
                    'Caratula' => $this->caratula,
                    'DTE' => null,
                ]
            ]
        ])->saveXML();
        // generar XML de los DTE que se deberán incorporar
        $DTEs = [];
        foreach ($this->dtes as &$DTE) {
            $DTEs[] = trim(str_replace(
                [
                    '<?xml version="1.0" encoding="ISO-8859-1"?>',
                    '<?xml version="1.0"?>'
                ],
                '',
                $DTE->saveXML()
            ));
        }
        // firmar XML del envío y entregar
        $xml = str_replace('<DTE/>', implode("\n", $DTEs), $xmlEnvio);
        $this->xml_data = $this->Firma
            ? $this->Firma->signXML($xml, '#LibreDTE_SetDoc', 'SetDTE', true)
            : $xml
        ;
        return $this->xml_data;
    }

    /**
     * Método que obtiene los datos para generar los tags SubTotDTE.
     * @return array Arreglo con los datos para generar los tags SubTotDTE.
     */
    private function getSubTotDTE(): array
    {
        $SubTotDTE = [];
        $subtotales = [];
        foreach ($this->dtes as &$DTE) {
            if (!isset($subtotales[$DTE->getTipo()])) {
                $subtotales[$DTE->getTipo()] = 0;
            }
            $subtotales[$DTE->getTipo()]++;
        }
        foreach ($subtotales as $tipo => $subtotal) {
            $SubTotDTE[] = [
                'TpoDTE' => $tipo,
                'NroDTE' => $subtotal,
            ];
        }
        return $SubTotDTE;
    }

    /**
     * Método que carga un XML de EnvioDte y asigna el objeto XML correspondiente
     * para poder obtener los datos del envío.
     * @param xml_data XML con tag inicial: EnvioDTE, EnvioBOLETA, DTE o SetDTE.
     * @return object|false Objeto XML.
     */
    public function loadXML($xml_data)
    {
        if (!parent::loadXML($xml_data)) {
            return false;
        }
        $tagName = $this->xml->documentElement->tagName;
        if ($tagName == 'DTE' || $tagName == 'SetDTE') {
            // obtener documentos
            if ($tagName == 'DTE') {
                $dtes = [$xml_data];
            } else {
                $dtes = [];
                $aux = $this->xml->documentElement->getElementsByTagName('DTE');
                foreach ($aux as $a) {
                    $dtes[] = $a;
                }
                unset($aux);
            }
            unset($xml_data);
            // reiniciar datos leídos
            $this->xml = null;
            $this->xml_data = null;
            $this->arreglo = null;
            // agregar documentos
            foreach ($dtes as $dte) {
                $Dte = new Dte(is_string($dte) ? $dte : $dte->C14N(), false);
                $this->agregar($Dte);
            }
            // crear carátula falta
            $this->setCaratula([
                'RutEnvia' => $Dte->getEmisor(),
                'RutReceptor' => $Dte->getReceptor(),
                'FchResol' => date('Y-m-d'),
                'NroResol' => ($Dte->getCertificacion() ? '0' :'') . '9999',
            ]);
            // cargar nuevo XML con datos completos
            if (!parent::loadXML($this->generar())) {
                return false;
            }
            $tagName = $this->xml->documentElement->tagName;
        }
        if ($tagName == 'EnvioDTE') {
            $this->tipo = 0;
            return $this->xml;
        }
        if ($tagName == 'EnvioBOLETA') {
            $this->tipo = 1;
            return $this->xml;
        }
        return false;
    }

    /**
     * Método que entrega un arreglo con los datos de la carátula del envío.
     * @return array Arreglo con datos de carátula.
     * @return array|false
     */
    public function getCaratula()
    {
        $tipo = $this->config['tipos'][$this->tipo];
        return isset($this->arreglo[$tipo]['SetDTE']['Caratula'])
            ? $this->arreglo[$tipo]['SetDTE']['Caratula']
            : false
        ;
    }

    /**
     * Método que entrega el ID de SetDTE.
     * @return string|false
     */
    public function getID()
    {
        $tipo = $this->config['tipos'][$this->tipo];
        return isset($this->arreglo[$tipo]['SetDTE']['@attributes']['ID'])
            ? $this->arreglo[$tipo]['SetDTE']['@attributes']['ID']
            : false
        ;
    }

    /**
     * Método que entrega el DigestValue de la firma del envío.
     * @return string|false
     */
    public function getDigest()
    {
        $tipo = $this->config['tipos'][$this->tipo];
        return isset($this->arreglo[$tipo]['Signature']['SignedInfo']['Reference']['DigestValue'])
            ? $this->arreglo[$tipo]['Signature']['SignedInfo']['Reference']['DigestValue']
            : false
        ;
    }

    /**
     * Método que entrega el rut del emisor del envío.
     * @return string|false
     */
    public function getEmisor()
    {
        $Caratula = $this->getCaratula();
        return $Caratula ? $Caratula['RutEmisor'] : false;
    }

    /**
     * Método que entrega el rut del receptor del envío.
     * @return string|false
     */
    public function getReceptor()
    {
        $Caratula = $this->getCaratula();
        return $Caratula ? $Caratula['RutReceptor'] : false;
    }

    /**
     * Método que entrega la fecha del DTE más antiguo del envio.
     * @return string Fecha del DTE más antiguo del envío.
     */
    public function getFechaEmisionInicial(): string
    {
        $fecha = '9999-12-31';
        foreach ($this->getDocumentos() as $Dte) {
            if ($Dte->getFechaEmision() < $fecha) {
                $fecha = $Dte->getFechaEmision();
            }
        }
        return $fecha;
    }

    /**
     * Método que entrega la fecha del DTE más nuevo del envio.
     * @return string Fecha del DTE más nuevo del envío.
     */
    public function getFechaEmisionFinal(): string
    {
        $fecha = '0000-01-01';
        foreach ($this->getDocumentos() as $Dte) {
            if ($Dte->getFechaEmision() > $fecha) {
                $fecha = $Dte->getFechaEmision();
            }
        }
        return $fecha;
    }

    /**
     * Método que entrega el arreglo con los objetos DTE del envío.
     * @return array|false Arreglo de objetos DTE.
     */
    public function getDocumentos($c14n = true)
    {
        // si no hay documentos se deben crear
        if (!$this->dtes) {
            // si no hay XML no se pueden crear los documentos
            if (!$this->xml) {
                \libredte\lib\Log::write(
                    \libredte\lib\Estado::ENVIODTE_GETDOCUMENTOS_FALTA_XML,
                    \libredte\lib\Estado::get(\libredte\lib\Estado::ENVIODTE_GETDOCUMENTOS_FALTA_XML)
                );
                return false;
            }
            // crear documentos a partir del XML
            $DTEs = $this->xml->getElementsByTagName('DTE');
            foreach ($DTEs as $nodo_dte) {
                $xml = $c14n ? $nodo_dte->C14N() : $this->xml->saveXML($nodo_dte);
                $this->dtes[] = new Dte($xml, false); // cargar DTE sin normalizar porque ya está normalizado
            }
        }
        return $this->dtes;
    }

    /**
     * Método que entrega el objeto DTE solicitado del envío
     * @return Dte Objeto DTE.
     */
    public function getDocumento($emisor, $dte, $folio)
    {
        $emisor = str_replace('.', '', $emisor);
        // si no hay XML no se pueden crear los documentos
        if (!$this->xml) {
            \libredte\lib\Log::write(
                \libredte\lib\Estado::ENVIODTE_GETDOCUMENTOS_FALTA_XML,
                \libredte\lib\Estado::get(\libredte\lib\Estado::ENVIODTE_GETDOCUMENTOS_FALTA_XML)
            );
            return false;
        }
        // buscar documento
        $DTEs = $this->xml->getElementsByTagName('DTE');
        foreach ($DTEs as $nodo_dte) {
            $e = $nodo_dte->getElementsByTagName('RUTEmisor')->item(0)->nodeValue;
            if (is_numeric($emisor)) {
                $e = substr($e, 0, -2);
            }
            $d = (int)$nodo_dte->getElementsByTagName('TipoDTE')->item(0)->nodeValue;
            $f = (int)$nodo_dte->getElementsByTagName('Folio')->item(0)->nodeValue;
            if ($folio == $f and $dte == $d and $emisor == $e) {
                // cargar y entregar DTE sin normalizar porque ya está normalizado
                return new Dte($nodo_dte->C14N(), false);
            }
        }
        return false;
    }

    /**
     * Método que indica si es EnvioDTE o EnvioBOLETA.
     * @return bool|null =true si es boleta, =false si no es boleta, =null si no se pudo determinar.
     */
    public function esBoleta()
    {
        return $this->tipo !== null ? (bool)$this->tipo : null;
    }

    /**
     * Método que determina el estado de validación sobre el envío.
     * @param datos Arreglo con datos para hacer las validaciones.
     * @return int Código del estado de la validación.
     */
    public function getEstadoValidacion(array $datos = null): int
    {
        if (!$this->schemaValidate()) {
            return 1;
        }
        if (!$this->checkFirma()) {
            return 2;
        }
        if ($datos && $this->getReceptor() != $datos['RutReceptor']) {
            return 3;
        }
        return 0;
    }

    /**
     * Método que indica si la firma del documento es o no válida.
     * @return bool|null =true si la firma del documento del envío es válida, =null si no se pudo determinar.
     */
    public function checkFirma()
    {
        if (!$this->xml) {
            return null;
        }
        // listado de firmas del XML
        $Signatures = $this->xml->documentElement->getElementsByTagName('Signature');
        // verificar firma de SetDTE
        $SetDTE = $this->xml->documentElement->getElementsByTagName('SetDTE')->item(0)->C14N();
        $SignedInfo = $Signatures->item($Signatures->length-1)->getElementsByTagName('SignedInfo')->item(0);
        $DigestValue = $Signatures->item($Signatures->length-1)->getElementsByTagName('DigestValue')->item(0)->nodeValue;
        $SignatureValue = trim(str_replace(
            ["\n", ' ', "\t"],
            '',
            $Signatures->item($Signatures->length - 1)->getElementsByTagName('SignatureValue')->item(0)->nodeValue
        ));
        $X509Certificate = trim(str_replace(
            ["\n", ' ', "\t"],
            '',
            $Signatures->item($Signatures->length - 1)->getElementsByTagName('X509Certificate')->item(0)->nodeValue
        ));
        $X509Certificate = '-----BEGIN CERTIFICATE-----' . "\n"
            . wordwrap($X509Certificate, 64, "\n", true) ."\n"
            . '-----END CERTIFICATE-----'
        ;
        $valid = openssl_verify($SignedInfo->C14N(), base64_decode($SignatureValue), $X509Certificate) === 1 ? true : false;
        return $valid && $DigestValue === base64_encode(sha1($SetDTE, true));
    }

}

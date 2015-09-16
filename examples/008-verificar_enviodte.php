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

/**
 * @file 008-verificar_enviodte.php
 * Referencias: http://www.cryptosys.net/pki/xmldsig-ChileSII.html
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-16
 */

// respuesta en texto plano
header('Content-type: text/plain; charset=ISO-8859-1');

// incluir archivos php de la biblioteca y configuraciones
include 'inc.php';

// crear objeto con XML del DTE que se desea validar
$XML = new \sasco\LibreDTE\XML();
$XML->loadXML(file_get_contents('xml/archivoFirmado.xml'));

// listado de firmas del XML
$Signatures = $XML->documentElement->getElementsByTagName('Signature');

// verificar firma de SetDTE
$SetDTE = $XML->documentElement->getElementsByTagName('SetDTE')->item(0)->C14N();
$SignedInfo = $Signatures->item($Signatures->length-1)->getElementsByTagName('SignedInfo')->item(0);
$DigestValue = $Signatures->item($Signatures->length-1)->getElementsByTagName('DigestValue')->item(0)->nodeValue;
$SignatureValue = $Signatures->item($Signatures->length-1)->getElementsByTagName('SignatureValue')->item(0)->nodeValue;
$X509Certificate = $Signatures->item($Signatures->length-1)->getElementsByTagName('X509Certificate')->item(0)->nodeValue;
$X509Certificate = '-----BEGIN CERTIFICATE-----'."\n".wordwrap(trim($X509Certificate), 64, "\n", true)."\n".'-----END CERTIFICATE----- ';
//$pub_key = openssl_pkey_get_details(openssl_pkey_get_public($X509Certificate))['key'];
/*$pub_key = getPublicKey(
    $Signatures->item($Signatures->length-1)->getElementsByTagName('Modulus')->item(0)->nodeValue,
    $Signatures->item($Signatures->length-1)->getElementsByTagName('Exponent')->item(0)->nodeValue
);*/
$valid = openssl_verify($SignedInfo->C14N(), base64_decode($SignatureValue), $X509Certificate) === 1 ? true : false;
echo 'Verificando SetDTE:',"\n";
echo '  Digest SetDTE: ',base64_encode(sha1($SetDTE, true)),"\n";
echo '  Digest SignedInfo: ',base64_encode(sha1($SignedInfo->C14N(), true)),"\n";
echo '  Digest SignedInfo: ',bin2hex(sha1($SignedInfo->C14N(), true)),"\n";
echo '  Digest SetDTE valido: ',($DigestValue===base64_encode(sha1($SetDTE, true))?'si':'no'),"\n";
echo '  Digest SignedInfo valido: ',($valid?'si':'no'),"\n\n";

// verificar firma de documentos
$i = 0;
$documentos = $XML->documentElement->getElementsByTagName('Documento');
foreach ($documentos as $D) {
    $Documento = new \sasco\LibreDTE\XML();
    $Documento->loadXML($D->C14N());
    $Documento->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
    $Documento->documentElement->removeAttributeNS('http://www.sii.cl/SiiDte', '');
    $SignedInfo = new \sasco\LibreDTE\XML();
    $SignedInfo->loadXML($Signatures->item($i)->getElementsByTagName('SignedInfo')->item(0)->C14N());
    $SignedInfo->documentElement->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi');
    $DigestValue = $Signatures->item($i)->getElementsByTagName('DigestValue')->item(0)->nodeValue;
    $SignatureValue = $Signatures->item($i)->getElementsByTagName('SignatureValue')->item(0)->nodeValue;
    $X509Certificate = $Signatures->item($i)->getElementsByTagName('X509Certificate')->item(0)->nodeValue;
    $X509Certificate = '-----BEGIN CERTIFICATE-----'."\n".wordwrap(trim($X509Certificate), 64, "\n", true)."\n".'-----END CERTIFICATE----- ';
    //$pub_key = openssl_pkey_get_details(openssl_pkey_get_public($X509Certificate))['key'];
    /*$pub_key = getPublicKey(
        $Signatures->item($i)->getElementsByTagName('Modulus')->item(0)->nodeValue,
        $Signatures->item($i)->getElementsByTagName('Exponent')->item(0)->nodeValue
    );*/
    $valid = openssl_verify($SignedInfo->C14N(), base64_decode($SignatureValue), $X509Certificate) === 1 ? true : false;
    echo 'Verificando Documento:',"\n";
    echo '  Digest Documento: ',base64_encode(sha1($Documento->C14N(), true)),"\n";
    echo '  Digest SignedInfo: ',base64_encode(sha1($SignedInfo->C14N(), true)),"\n";
    echo '  Digest Documento valido: ',($DigestValue===base64_encode(sha1($Documento->C14N(), true))?'si':'no'),"\n";
    echo '  Digest SignedInfo valido: ',($valid?'si':'no'),"\n\n";
    $i++;
}

// si hubo errores mostrar
foreach (\sasco\LibreDTE\Log::readAll() as $error)
    echo $error,"\n";

// para el XML de ejemplo del SII la clave pública obtenida desde el certificado
// no sirvió para validar las firmas del documento, se probó obteniendo la clave
// desde el módulo y exponente y ahí si funcionó. Sin embargo para otros DTEs
// revisados (generados por otras bibliotecas y por LibreDTE) el certificado
// entregó una clave correcta, por eso es que se deja este código comentado y
// por el momento no se incluye la biblioteca phpseclib como parte del proyecto
/*function getPublicKey($modulus, $exponent)
{
    include_once '../vendor/autoload.php';
    $rsa = new \phpseclib\Crypt\RSA();
    $modulus = new \phpseclib\Math\BigInteger(base64_decode($modulus), 256);
    $exponent = new \phpseclib\Math\BigInteger(base64_decode($exponent), 256);
    $rsa->loadKey(['n' => $modulus, 'e' => $exponent]);
    $rsa->setPublicKey();
    return $rsa->getPublicKey();
}*/

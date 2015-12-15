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

// inicializar comando
include 'share/bootstrap.php';

/**
 * Comando para generar el versión en PDF de un DTE a partir del EnvioDTE
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-12-15
 */
class generar_pdf extends Command
{

    protected $config = [
        'args' => [
            'xml:' => [
                'description' => 'Archivo XML con el EnvioDTE o EnvioBoleta',
                'default' => false,
            ],
            'logo:' => [
                'description' => 'Logo en formato PNG para incluir en el PDF',
                'default' => false,
            ],
            'cedible' => [
                'description' => 'Flag para indicar si se debe generar copia cedible',
            ],
            'papel:' => [
                'description' => 'Para papel contínuo, se indica el ancho del papel en mm',
                'default' => false,
            ],
            'web:' => [
                'description' => 'Web de verificación para boletas electrónicas',
                'default' => 'www.sii.cl',
            ],
            'dir:' => [
                'description' => 'Directorio donde se desean dejar los archivos PDF generados',
                'default' => false,
            ],
        ],
    ]; ///< Configuración del comando

    /**
     * Método que valida los argumentos pasados al comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    public function args_check()
    {
        // archivo
        if (!$this->args['xml'] or !is_readable($this->args['xml'])) {
            $this->error('Debe especificar archivo XML de entrada válido');
        }
        // directorio de salida
        if (!$this->args['dir'] or (!is_dir($this->args['dir']) and !mkdir($this->args['dir']))) {
            $this->error('Debe especificar directorio válido para dejar los archivos PDF generados');
        }
    }

    /**
     * Método principal del comando
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-12-15
     */
    public function main()
    {
        // cargar XML y extraer arreglo con datos de carátula y DTEs
        $EnvioDte = new \sasco\LibreDTE\Sii\EnvioDte();
        $EnvioDte->loadXML(file_get_contents($this->args['xml']));
        $Caratula = $EnvioDte->getCaratula();
        $Documentos = $EnvioDte->getDocumentos();
        // procesar cada DTEs e ir creando los archivos PDF
        foreach ($Documentos as $DTE) {
            if (!$DTE->getDatos())
                $this->error('No se pudieron obtener los datos de uno de los DTE del XML');
            $this->out('Generando PDF para DTE '.$DTE->getID());
            $pdf = new \sasco\LibreDTE\Sii\PDF\Dte($this->args['papel']);
            $pdf->setFooterText();
            if ($this->args['logo'])
                $pdf->setLogo($this->args['logo']);
            $pdf->setResolucion(['FchResol'=>$Caratula['FchResol'], 'NroResol'=>$Caratula['NroResol']]);
            $pdf->setWebVerificacion($this->args['web']);
            $pdf->agregar($DTE->getDatos(), $DTE->getTED());
            if (isset($this->args['cedible'])) {
                $pdf->setCedible(true);
                $pdf->agregar($DTE->getDatos(), $DTE->getTED());
            }
            $pdf->Output($this->args['dir'].'/dte_'.$Caratula['RutEmisor'].'_'.$DTE->getID().'.pdf', 'F');
        }
        return 0;
    }

}

// lanzar comando
exit((new generar_pdf())->main());

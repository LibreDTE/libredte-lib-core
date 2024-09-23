<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada
 * por la Fundación para el Software Libre, ya sea la versión 3 de la Licencia,
 * o (a su elección) cualquier versión posterior de la misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero SIN
 * GARANTÍA ALGUNA; ni siquiera la garantía implícita MERCANTIL o de APTITUD
 * PARA UN PROPÓSITO DETERMINADO. Consulte los detalles de la Licencia Pública
 * General Affero de GNU para obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de
 * GNU junto a este programa.
 *
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace libredte\lib\Core\Sii\Dte\Documento\Builder;

use libredte\lib\Core\Helper\Arr;
use libredte\lib\Core\Sii\Dte\Documento\GuiaDespacho;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DetalleNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DescuentosRecargosNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\ImpuestoAdicionalRetencionNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\IvaMntTotalNormalizationTrait;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\TransporteNormalizationTrait;

/**
 * Constructor ("builder") del documento guía de despacho.
 */
class GuiaDespachoBuilder extends AbstractDocumentoBuilder
{
    // Traits usados por este "builder".
    use DetalleNormalizationTrait;
    use DescuentosRecargosNormalizationTrait;
    use ImpuestoAdicionalRetencionNormalizationTrait;
    use IvaMntTotalNormalizationTrait;
    use TransporteNormalizationTrait;

    /**
     * Clase del documento que este "builder" construirá.
     *
     * @var string
     */
    protected string $documentoClass = GuiaDespacho::class;

    /**
     * Normaliza los datos con reglas específicas para el tipo de documento.
     *
     * @param array $data Arreglo con los datos del documento a normalizar.
     * @return array Arreglo con los datos normalizados.
     */
    public function applyDocumentoNormalization(array $data): array
    {
        // Completar con nodos por defecto.
        $data = Arr::mergeRecursiveDistinct([
            'Encabezado' => [
                'IdDoc' => false,
                'Emisor' => false,
                'Receptor' => false,
                'RUTSolicita' => false,
                'Transporte' => false,
                'Totales' => [
                    'MntNeto' => 0,
                    'MntExe' => false,
                    'TasaIVA' => $this->getTipoDocumento()->getDefaultTasaIVA(),
                    'IVA' => 0,
                    'ImptoReten' => false,
                    'CredEC' => false,
                    'MntTotal' => 0,
                ]
            ],
        ], $data);

        // Si es traslado interno se copia el emisor en el receptor solo si el
        // receptor no está definido o bien si el receptor tiene RUT diferente
        // al emisor.
        if ($data['Encabezado']['IdDoc']['IndTraslado'] == 5) {
            if (
                !$data['Encabezado']['Receptor']
                || $data['Encabezado']['Receptor']['RUTRecep']
                    != $data['Encabezado']['Emisor']['RUTEmisor']
            ) {
                $data['Encabezado']['Receptor'] = [];
                $cols = [
                    'RUTEmisor'=>'RUTRecep',
                    'RznSoc'=>'RznSocRecep',
                    'GiroEmis'=>'GiroRecep',
                    'Telefono'=>'Contacto',
                    'CorreoEmisor'=>'CorreoRecep',
                    'DirOrigen'=>'DirRecep',
                    'CmnaOrigen'=>'CmnaRecep',
                ];
                foreach ($cols as $emisor => $receptor) {
                    if (!empty($data['Encabezado']['Emisor'][$emisor])) {
                        $data['Encabezado']['Receptor'][$receptor] =
                            $data['Encabezado']['Emisor'][$emisor]
                        ;
                    }
                }
                if (!empty($data['Encabezado']['Receptor']['GiroRecep'])) {
                    $data['Encabezado']['Receptor']['GiroRecep'] =
                        mb_substr($data['Encabezado']['Receptor']['GiroRecep'], 0, 40)
                    ;
                }
            }
        }

        // Normalizar datos.
        $data = $this->applyDetalleNormalization($data);
        $data = $this->applyDescuentosRecargosNormalization($data);
        $data = $this->applyImpuestoRetenidoNormalization($data);
        $data = $this->applyIvaMntTotalNormalization($data);
        $data = $this->applyTransporteNormalization($data);

        // Entregar los datos normalizados.
        return $data;
    }
}

<?php

declare(strict_types=1);

/**
 * LibreDTE: Library for invoicing in Chile (Core).
 * Copyright (C) 2026 LibreDTE <https://www.libredte.cl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Normalizer\Trait;

use Derafu\Array\ArrayWriter;
use Derafu\Value\ValueCaster;
use Derafu\Value\ValueType;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentBagInterface;

/**
 * Reglas de normalización para boletas.
 */
trait NormalizeBoletasTrait
{
    /**
     * Normaliza las boletas electrónicas.
     *
     * @param DocumentBagInterface $bag Bolsa con los datos a normalizar.
     * @return void
     */
    protected function normalizeBoletas(DocumentBagInterface $bag): void
    {
        $data = $bag->requireNormalizedData();
        $writer = ArrayWriter::fromReference($data);

        // Cambiar tags de DTE a boleta si se pasaron.
        $razonSocial = $writer->getReader()->get('Encabezado.Emisor.RznSoc', ValueType::mixed());
        if ($razonSocial) {
            $writer->set('Encabezado.Emisor.RznSocEmisor', $razonSocial);
            $writer->set('Encabezado.Emisor.RznSoc', null);
        }
        $giro = $writer->getReader()->get('Encabezado.Emisor.GiroEmis', ValueType::mixed());
        if ($giro) {
            $writer->set('Encabezado.Emisor.GiroEmisor', $giro);
            $writer->set('Encabezado.Emisor.GiroEmis', null);
        }
        $writer->set('Encabezado.Emisor.Acteco', null);
        $writer->set('Encabezado.Emisor.Telefono', null);
        $writer->set('Encabezado.Emisor.CorreoEmisor', null);
        $writer->set('Encabezado.Emisor.CdgVendedor', null);
        $writer->set('Encabezado.Receptor.GiroRecep', null);
        $correoRecep = $writer->getReader()->get('Encabezado.Receptor.CorreoRecep', ValueType::mixed());
        if (!empty($correoRecep)) {
            $referencia = $writer->getReader()->get('Referencia', ValueType::mixed());
            if ($referencia === null) {
                $writer->set('Referencia', []);
                $referencia = [];
            }
            $writer->append('Referencia', [
                'NroLinRef' => !empty($referencia)
                    ? (count(ValueType::array()->parse($referencia, 'Referencia')) + 1)
                    : 1,
                'RazonRef' => mb_substr(
                    sprintf(
                        'Email receptor: %s',
                        ValueCaster::toString($correoRecep)
                    ),
                    0,
                    90
                ),
            ]);
        }
        $writer->set('Encabezado.Receptor.CorreoRecep', null);

        // Quitar otros tags que no son parte de las boletas.
        $writer->set('Encabezado.IdDoc.FmaPago', null);
        $writer->set('Encabezado.IdDoc.FchCancel', null);
        $writer->set('Encabezado.IdDoc.MedioPago', null);
        $writer->set('Encabezado.IdDoc.TpoCtaPago', null);
        $writer->set('Encabezado.IdDoc.NumCtaPago', null);
        $writer->set('Encabezado.IdDoc.BcoPago', null);
        $writer->set('Encabezado.IdDoc.TermPagoGlosa', null);
        $writer->set('Encabezado.RUTSolicita', null);
        $writer->set('Encabezado.IdDoc.TpoTranCompra', null);
        $writer->set('Encabezado.IdDoc.TpoTranVenta', null);
        $writer->set('Encabezado.Transporte', null);

        // Ajustar las referencias si existen.
        $referencias = $writer->getReader()->get('Referencia', ValueType::mixed());
        if (!empty($referencias)) {
            $referencias = ValueType::array()->parse($referencias, 'Referencia');
            if (!isset($referencias[0])) {
                $writer->set('Referencia', [$referencias]);
            }
            $items = $writer->getReader()->get(
                'Referencia',
                ValueType::arrayOf(ValueType::map()),
                []
            );
            foreach ($items as $key => $r) {
                foreach (['FchRef'] as $c) {
                    if (isset($r[$c])) {
                        $writer->remove('Referencia.' . $key . '.' . $c);
                    }
                }
            }
        }

        // Actualizar los datos normalizados.
        $bag->setNormalizedData($writer->getReader()->toMap());
    }
}

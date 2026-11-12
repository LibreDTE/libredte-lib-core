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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Worker;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Operation;
use Derafu\Backbone\Attribute\Worker;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFakerWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Worker\CafFaker\Job\CreateXmlJob;
use libredte\lib\Core\Package\Billing\Component\TradingParties\Contract\EmisorInterface;

/**
 * Worker que permite crear CAF falsos para pruebas.
 */
#[Worker(name: 'caf_faker', component: 'identifier', package: 'billing')]
class CafFakerWorker extends AbstractWorker implements CafFakerWorkerInterface
{
    public function __construct(
        private CreateXmlJob $createXmlJob,
        private CafLoaderWorkerInterface $cafLoader
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'create',
        summary: 'Crea un CAF falso para pruebas',
        description: <<<'MD'
            Genera un CAF (Código de Autorización de Folios) falso, con llaves propias y sin la autorización del SII. Sirve para probar la emisión de documentos sin solicitar folios reales; el SII no lo reconoce, por lo que no sirve para emitir documentos válidos.

            ## Cómo se usa

            Se indica el emisor, el tipo de documento y el rango de folios. La respuesta es el CAF completo, que se puede enviar como `caf` a `billing.document.builder::build`. Si no se indica `folioHasta`, el CAF tiene un solo folio (el inicial); si el folio inicial es mayor que el final, la operación falla.

            En un CAF falso, `ambiente` y `certificacion` son `null`.
            MD,
        parameters: [
            'emisor' => [
                'description' => 'Emisor al que pertenece el CAF. Se usan su `rut` y su `razon_social`.',
                'example' => [
                    'rut' => '12345678-9',
                    'razon_social' => 'Empresa de Prueba SpA',
                ],
            ],
            'codigoDocumento' => [
                'description' => <<<'MD'
                    Código del tipo de documento que autoriza el CAF, por ejemplo `33` (factura electrónica) o `39` (boleta electrónica). Los códigos válidos están en el catálogo: operación `system.repository.catalog::find_all` con `repository` igual a `libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface`.
                    MD,
                'example' => 33,
            ],
            'folioDesde' => [
                'description' => 'Primer folio del rango. Por defecto `1`.',
                'example' => 1,
            ],
            'folioHasta' => [
                'description' => 'Último folio del rango. Si no se indica, el CAF tiene un solo folio (`folioDesde`).',
                'example' => 100,
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    CAF creado. Incluye el rango de folios (`folio_desde`, `folio_hasta`, `cantidad_folios`), las fechas de autorización y vencimiento, su vigencia, sus llaves (`public_key` y `private_key`) y el XML completo en base64 (`xml`).
                    MD,
                'example' => [
                    'id' => 'CAF33D1H100',
                    'emisor' => [
                        'rut' => '12345678-9',
                        'razon_social' => 'Empresa de Prueba SpA',
                    ],
                    'tipo_documento' => 33,
                    'folio_desde' => 1,
                    'folio_hasta' => 100,
                    'cantidad_folios' => 100,
                    'fecha_autorizacion' => '2024-01-15',
                    'fecha_vencimiento' => '2024-07-14',
                    'meses_autorizacion' => 0,
                    'vigente' => true,
                    'vence' => true,
                    'idk' => 666,
                    'ambiente' => null,
                    'certificacion' => null,
                    'public_key' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A...",
                    'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASC...",
                    'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pg...',
                ],
            ],
        ],
    )]
    public function create(
        EmisorInterface $emisor,
        int $codigoDocumento,
        int $folioDesde = 1,
        ?int $folioHasta = null
    ): CafInterface {
        $xml = $this->createXmlJob->create(
            $emisor,
            $codigoDocumento,
            $folioDesde,
            $folioHasta
        );

        return $this->cafLoader->load($xml);
    }
}

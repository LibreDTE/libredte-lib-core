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
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Value\ValueCaster;
use Derafu\Xml\Contract\XmlDocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\TipoDocumentoInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafLoaderWorkerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Exception\CafLoaderException;

/**
 * Worker que permite cargar archivos CAF.
 */
#[Worker(name: 'caf_loader', component: 'identifier', package: 'billing')]
class CafLoaderWorker extends AbstractWorker implements CafLoaderWorkerInterface
{
    protected string $cafClass = Caf::class;

    public function __construct(
        private RepositoryManagerInterface $repositoryManager
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Operation(
        name: 'load',
        summary: 'Carga un archivo CAF',
        description: <<<'MD'
            Carga el XML de un CAF (Código de Autorización de Folios) y entrega sus datos: emisor, tipo de documento, rango de folios, fechas, vigencia y llaves. Verifica que el tipo de documento del CAF exista en el catálogo, pero no valida su firma ni sus llaves; para eso se usa `billing.identifier.caf_validator::validate`.

            ## Cómo se usa

            Se envía en `xml` el contenido del archivo CAF codificado en base64.
            MD,
        parameters: [
            'xml' => [
                'description' => 'XML del CAF, codificado en base64.',
                'example' => '',
            ],
        ],
        results: [
            'success' => [
                'description' => <<<'MD'
                    CAF cargado. Incluye el rango de folios (`folio_desde`, `folio_hasta`, `cantidad_folios`), las fechas de autorización y vencimiento, su vigencia, sus llaves (`public_key` y `private_key`) y el XML completo en base64 (`xml`). `vence` indica si los folios de ese tipo de documento vencen. `certificacion` es `1` si el CAF es de certificación, `0` si es de producción y `null` si es un CAF falso.
                    MD,
                'example' => [
                    'id' => 'CAF33D1H100',
                    'emisor' => [
                        'rut' => '76192083-9',
                        'razon_social' => 'SASCO SpA',
                    ],
                    'tipo_documento' => 33,
                    'folio_desde' => 1,
                    'folio_hasta' => 100,
                    'cantidad_folios' => 100,
                    'fecha_autorizacion' => '2024-01-15',
                    'fecha_vencimiento' => '2024-07-14',
                    'meses_autorizacion' => 2.5,
                    'vigente' => true,
                    'vence' => true,
                    'idk' => 100,
                    'ambiente' => [
                        'name' => 'PRODUCTION',
                        'value' => 0,
                    ],
                    'certificacion' => 0,
                    'public_key' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A...",
                    'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASC...",
                    'xml' => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iSVNPLTg4NTktMSI/Pg...',
                ],
            ],
        ],
    )]
    public function load(string|XmlDocumentInterface $xml): CafInterface
    {
        $class = $this->cafClass;
        $caf = ValueCaster::requireObject(new $class($xml), CafInterface::class);

        $this->validateTipoDocumento($caf->getTipoDocumento());

        return $caf;
    }

    /**
     * Valida que el código de tipo de documento del CAF exista en el repositorio.
     *
     * @param int $codigoTipoDocumento
     * @return void
     * @throws CafLoaderException
     */
    private function validateTipoDocumento(int $codigoTipoDocumento): void
    {
        $tipoDocumento = $this->repositoryManager
            ->getRepository(TipoDocumentoInterface::class)
            ->find($codigoTipoDocumento)
        ;

        if (!$tipoDocumento) {
            throw new CafLoaderException([
                'Document type code {code} was not found to process the CAF.',
                'code' => $codigoTipoDocumento,
            ]);
        }
    }
}

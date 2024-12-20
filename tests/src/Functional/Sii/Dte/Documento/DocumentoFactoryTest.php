<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
 * Copyright (C) LibreDTE <https://www.libredte.cl>
 *
 * Este programa es software libre: usted puede redistribuirlo y/o modificarlo
 * bajo los términos de la Licencia Pública General Affero de GNU publicada por
 * la Fundación para el Software Libre, ya sea la versión 3 de la Licencia, o
 * (a su elección) cualquier versión posterior de la misma.
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

namespace libredte\lib\Tests\Functional\Sii\Dte\Documento;

use libredte\lib\Core\Helper\Arr;
use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Repository\DocumentoTipoRepository;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\PathManager;
use libredte\lib\Core\Sii\Dte\Documento\AbstractDocumento;
use libredte\lib\Core\Sii\Dte\Documento\Builder\AbstractDocumentoBuilder;
use libredte\lib\Core\Sii\Dte\Documento\Builder\DocumentoFactory;
use libredte\lib\Core\Sii\Dte\Documento\Builder\FacturaAfectaBuilder;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoException;
use libredte\lib\Core\Sii\Dte\Documento\DocumentoTipo;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoNormalizer;
use libredte\lib\Core\Sii\Dte\Documento\Normalization\DocumentoSanitizer;
use libredte\lib\Core\Sii\Dte\Documento\Parser\DocumentoParser;
use libredte\lib\Core\Sii\Dte\Documento\Parser\Sii\JsonParser;
use libredte\lib\Core\Sii\Dte\Documento\Parser\Sii\XmlParser;
use libredte\lib\Core\Sii\Dte\Documento\Parser\Sii\YamlParser;
use libredte\lib\Core\Xml\XmlConverter;
use libredte\lib\Core\Xml\XmlDecoder;
use libredte\lib\Core\Xml\XmlDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentoFactory::class)]
#[CoversClass(Arr::class)]
#[CoversClass(Rut::class)]
#[CoversClass(DocumentoTipoRepository::class)]
#[CoversClass(ArrayDataProvider::class)]
#[CoversClass(PathManager::class)]
#[CoversClass(AbstractDocumento::class)]
#[CoversClass(AbstractDocumentoBuilder::class)]
#[CoversClass(FacturaAfectaBuilder::class)]
#[CoversClass(DocumentoTipo::class)]
#[CoversClass(DocumentoNormalizer::class)]
#[CoversClass(DocumentoSanitizer::class)]
#[CoversClass(DocumentoParser::class)]
#[CoversClass(JsonParser::class)]
#[CoversClass(XmlParser::class)]
#[CoversClass(YamlParser::class)]
#[CoversClass(XmlConverter::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
class DocumentoFactoryTest extends TestCase
{
    public function testDocumentoFactoryFromArrayWithoutTipoDTE(): void
    {
        $this->expectException(DocumentoException::class);

        $data = [];

        $factory = new DocumentoFactory();
        $documento = $factory->createFromArray($data);
    }

    public function testDocumentoFactoryFromArrayEmptyTipoDTE(): void
    {
        $this->expectException(DocumentoException::class);

        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => '',
                ],
            ],
        ];

        $factory = new DocumentoFactory();
        $documento = $factory->createFromArray($data);
    }

    public function testDocumentoFactoryFromArrayWrongIntTipoDTE(): void
    {
        $this->expectException(DocumentoException::class);

        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 35,
                ],
            ],
        ];

        $factory = new DocumentoFactory();
        $documento = $factory->createFromArray($data);
    }

    public function testDocumentoFactoryFromArrayWrongStringTipoDTE(): void
    {
        $this->expectException(DocumentoException::class);

        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 'factura',
                ],
            ],
        ];

        $factory = new DocumentoFactory();
        $documento = $factory->createFromArray($data);
    }

    public function testDocumentoFactoryFromArrayOk(): void
    {
        $data = [
            'Encabezado' => [
                'IdDoc' => [
                    'TipoDTE' => 33,
                ],
                'Emisor' => [
                    'RUTEmisor' => '76192083-9',
                    'RznSoc' => 'SASCO SpA',
                    'GiroEmis' => 'Tecnología, Informática y Telecomunicaciones',
                    'DirOrigen' => 'Santiago',
                    'CmnaOrigen' => 'Santiago',
                ],
                'Receptor' => [
                    'RUTRecep' => '60803000-K',
                    'RznSocRecep' => 'Servicio de Impuestos Internos',
                    'GiroRecep' => 'Gobierno',
                    'DirRecep' => 'Santiago',
                    'CmnaRecep' => 'Santiago',
                ],
            ],
            'Detalle' => [
                'NmbItem' => 'Servicio Plus de LibreDTE',
                'QtyItem' => 12,
                'PrcItem' => 40000,
            ],
        ];

        $factory = new DocumentoFactory();
        $documento = $factory->createFromArray($data);
        $this->assertSame(571200, $documento->getMontoTotal());
    }

    public function testDocumentoFactoryFromXmlOk(): void
    {
        $data = '<DTE><Documento><Encabezado><IdDoc><TipoDTE>33</TipoDTE></IdDoc><Emisor><RUTEmisor>76192083-9</RUTEmisor><RznSoc>SASCO SpA</RznSoc><GiroEmis>Tecnología, Informática y Telecomunicaciones</GiroEmis><DirOrigen>Santiago</DirOrigen><CmnaOrigen>Santiago</CmnaOrigen></Emisor><Receptor><RUTRecep>60803000-K</RUTRecep><RznSocRecep>Servicio de Impuestos Internos</RznSocRecep><GiroRecep>Gobierno</GiroRecep><DirRecep>Santiago</DirRecep><CmnaRecep>Santiago</CmnaRecep></Receptor></Encabezado><Detalle><NmbItem>Servicio Plus de LibreDTE</NmbItem><QtyItem>12</QtyItem><PrcItem>40000</PrcItem></Detalle></Documento></DTE>';

        $factory = new DocumentoFactory();
        $documento = $factory->createFromXml($data);

        $this->assertSame(571200, $documento->getMontoTotal());
    }

    public function testDocumentoFactoryFromYamlOk(): void
    {
        $data = <<<YAML
        Encabezado:
            IdDoc:
                TipoDTE: 33
            Emisor:
                RUTEmisor: '76192083-9'
                RznSoc: 'SASCO SpA'
                GiroEmis: 'Tecnología, Informática y Telecomunicaciones'
                DirOrigen: 'Santiago'
                CmnaOrigen: 'Santiago'
            Receptor:
                RUTRecep: '60803000-K'
                RznSocRecep: 'Servicio de Impuestos Internos'
                GiroRecep: 'Gobierno'
                DirRecep: 'Santiago'
                CmnaRecep: 'Santiago'
        Detalle:
            NmbItem: 'Servicio Plus de LibreDTE'
            QtyItem: 12
            PrcItem: 40000
        YAML;

        $factory = new DocumentoFactory();
        $documento = $factory->createFromYaml($data);

        $this->assertSame(571200, $documento->getMontoTotal());
    }

    public function testDocumentoFactoryFromJsonOk(): void
    {
        $data = '{
            "Encabezado": {
                "IdDoc": {
                    "TipoDTE": 33
                },
                "Emisor": {
                    "RUTEmisor": "76192083-9",
                    "RznSoc": "SASCO SpA",
                    "GiroEmis": "Tecnología, Informática y Telecomunicaciones",
                    "DirOrigen": "Santiago",
                    "CmnaOrigen": "Santiago"
                },
                "Receptor": {
                    "RUTRecep": "60803000-K",
                    "RznSocRecep": "Servicio de Impuestos Internos",
                    "GiroRecep": "Gobierno",
                    "DirRecep": "Santiago",
                    "CmnaRecep": "Santiago"
                }
            },
            "Detalle": {
                "NmbItem": "Servicio Plus de LibreDTE",
                "QtyItem": 12,
                "PrcItem": 40000
            }
        }';

        $factory = new DocumentoFactory();
        $documento = $factory->createFromJson($data);

        $this->assertSame(571200, $documento->getMontoTotal());
    }
}

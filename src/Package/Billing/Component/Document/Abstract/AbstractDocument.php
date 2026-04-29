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

namespace libredte\lib\Core\Package\Billing\Component\Document\Abstract;

use Derafu\Repository\Entity;
use Derafu\Selector\Selector;
use Derafu\Support\Arr;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Exception\XmlQueryException;
use libredte\lib\Core\Package\Billing\Component\Document\Contract\DocumentInterface;
use libredte\lib\Core\Package\Billing\Component\Document\Enum\CodigoDocumento;
use LogicException;

/**
 * Clase abstracta (base) de la representación de un documento tributario
 * electrónico.
 */
abstract class AbstractDocument extends Entity implements DocumentInterface
{
    /**
     * Código del tipo de documento tributario al que está asociada esta
     * instancia de un documento.
     *
     * Este valor está definido en cada clase que hereda de esta.
     */
    protected CodigoDocumento $tipoDocumento;

    /**
     * Instancia del documento XML asociado a los datos.
     *
     * @var XmlDocumentInterface
     */
    protected readonly XmlDocumentInterface $xmlDocument;

    /**
     * Datos del documento tributario estandarizados.
     *
     * @var array
     */
    private array $data;

    /**
     * Constructor del documento tributario.
     *
     * @param XmlDocumentInterface $xmlDocument
     * @return void
     */
    public function __construct(XmlDocumentInterface $xmlDocument)
    {
        $this->xmlDocument = $xmlDocument;

        // Validar que el código que está en el XmlDocument sea el que la clase
        // de la estrategia espera.
        $xmlDocumentTipoDTE = (int) $xmlDocument->query('//Encabezado/IdDoc/TipoDTE');
        if ($xmlDocumentTipoDTE !== $this->getCodigo()) {
            throw new LogicException(sprintf(
                'El código %s del XmlDocument cargado en %s no corresponde con el esperado, debería ser código %d.',
                $xmlDocumentTipoDTE,
                static::class,
                $this->getCodigo()
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getXmlDocument(): XmlDocumentInterface
    {
        return $this->xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function saveXml(): string
    {
        return $this->xmlDocument->setEncoding('ISO-8859-1')->saveXml();
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        return $this->xmlDocument->setEncoding('ISO-8859-1')->getXml();
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return sprintf(
            '%s_T%03dF%09d',
            $this->getRutEmisor(),
            $this->getCodigo(),
            $this->getFolio()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCodigo(): int
    {
        return $this->tipoDocumento->getCodigo();
    }

    /**
     * {@inheritDoc}
     */
    public function getFolio(): int
    {
        $value = $this->xmlDocument->query('//Encabezado/IdDoc/Folio');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un folio asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/IdDoc/Folio'
            );
        }

        return (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getTipoDocumento(): CodigoDocumento
    {
        return $this->tipoDocumento;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmisor(): array
    {
        $value = $this->xmlDocument->query('//Encabezado/Emisor');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un emisor asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Emisor'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getRutEmisor(): string
    {
        $value = $this->xmlDocument->query('//Encabezado/Emisor/RUTEmisor');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un RUT de emisor asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Emisor/RUTEmisor'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getReceptor(): array
    {
        $value = $this->xmlDocument->query('//Encabezado/Receptor');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un receptor asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Receptor'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getRutReceptor(): string
    {
        $value = $this->xmlDocument->query('//Encabezado/Receptor/RUTRecep');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un RUT de receptor asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Receptor/RUTRecep'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getRazonSocialReceptor(): string
    {
        $value = $this->xmlDocument->query('//Encabezado/Receptor/RznSocRecep');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene una razón social de receptor asignada en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Receptor/RznSocRecep'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaEmision(): string
    {
        $value = $this->xmlDocument->query('//Encabezado/IdDoc/FchEmis');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene una fecha de emisión asignada en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/IdDoc/FchEmis'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getTotales(): array
    {
        $value = $this->xmlDocument->query('//Encabezado/Totales');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene los totales asignados en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Totales'
            );
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getMontoExento(): int|float
    {
        $value = $this->xmlDocument->query('//Encabezado/Totales/MntExe');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un monto exento asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Totales/MntExe'
            );
        }

        $monto = (float) $value;

        // Verificar si el monto es equivalente a un entero.
        if (floor($monto) == $monto) {
            return (int) $monto;
        }

        // Entregar como flotante.
        return $monto;
    }

    /**
     * {@inheritDoc}
     */
    public function getMontoNeto(): int
    {
        $value = $this->xmlDocument->query('//Encabezado/Totales/MntNeto');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un monto neto asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Totales/MntNeto'
            );
        }

        return (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getMontoIVA(): int
    {
        $value = $this->xmlDocument->query('//Encabezado/Totales/IVA');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un monto de IVA asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Totales/IVA'
            );
        }

        return (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getMontoTotal(): int|float
    {
        $value = $this->xmlDocument->query('//Encabezado/Totales/MntTotal');

        if ($value === null) {
            throw new XmlQueryException(
                'El documento no tiene un monto total asignado en el XML.',
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/Totales/MntTotal'
            );
        }

        $monto = (float) $value;

        // Verificar si el monto es equivalente a un entero.
        if (floor($monto) == $monto) {
            return (int) $monto;
        }

        // Entregar como flotante.
        return $monto;
    }

    /**
     * {@inheritDoc}
     */
    public function getMoneda(): string
    {
        $moneda = $this->xmlDocument->query('//Encabezado/Totales/TpoMoneda')
            ?? 'PESO CL'
        ;

        return (string) $moneda;
    }

    /**
     * {@inheritDoc}
     */
    public function getExento(): int
    {
        $value = $this->getMontoExento();

        $moneda = $this->getMoneda();
        if ($moneda === 'PESO CL') {
            return (int) round($value);
        }

        return (int) round($this->convertirAPesosCL($value, $moneda));
    }

    /**
     * {@inheritDoc}
     */
    public function getNeto(): int
    {
        return $this->getMontoNeto();
    }

    /**
     * {@inheritDoc}
     */
    public function getIVA(): int
    {
        return $this->getMontoIVA();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotal(): int
    {
        $value = $this->getMontoTotal();

        $moneda = $this->getMoneda();
        if ($moneda === 'PESO CL') {
            return (int) round($value);
        }

        return (int) round($this->convertirAPesosCL($value, $moneda));
    }

    /**
     * {@inheritDoc}
     */
    public function getTipoDeCambio(string $moneda = 'PESO CL'): float
    {
        $value = $this->xmlDocument->query('//Encabezado/OtraMoneda');

        if ($value === null) {
            throw new XmlQueryException(
                sprintf('El documento no tiene tipos de cambio asignados en el XML.'),
                xmlDocument: $this->xmlDocument,
                xpathExpression: '//Encabezado/OtraMoneda'
            );
        }

        if (!isset($value[0])) {
            $value = [$value];
        }

        foreach ($value as $OtraMoneda) {
            if ($OtraMoneda['TpoMoneda'] === $moneda) {
                return (float) $OtraMoneda['TpoCambio'];
            }
        }

        throw new XmlQueryException(
            sprintf('El documento no tiene un tipo de cambio asignado para la moneda %s en el XML.', $moneda),
            xmlDocument: $this->xmlDocument,
            xpathExpression: '//Encabezado/OtraMoneda'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function convertirAPesosCL(int|float $value, ?string $moneda = null): int|float
    {
        $moneda = $moneda ?? $this->getMoneda();
        if ($moneda === 'PESO CL') {
            return $value;
        }

        return $value * $this->getTipoDeCambio('PESO CL');
    }

    /**
     * {@inheritDoc}
     */
    public function getDetalle(?int $index = null): array
    {
        $detalle = $this->xmlDocument->query('//Detalle');

        if ($detalle === null) {
            return [];
        }

        if (!isset($detalle[0])) {
            $detalle = [$detalle];
        }

        return $index !== null ? ($detalle[$index] ?? []) : $detalle;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        // Si los datos del DTE no están determinados se crean de una manera
        // estandarizada compatible con los datos de entrada normalizados.
        if (!isset($this->data)) {
            $array = $this->xmlDocument->toArray();

            $array = $array['DTE']['Documento']
                ?? $array['DTE']['Exportaciones']
                ?? $array['DTE']['Liquidacion']
                ?? $array
            ;

            unset($array['TED'], $array['TmstFirma']);

            $arrayRequired = [
                'Encabezado.Totales.ImptoReten',
                'Detalle',
                'DscRcgGlobal',
                'Referencia',
            ];
            foreach ($arrayRequired as $path) {
                Arr::ensureArrayAtPath($array, $path);
            }

            $this->data = Arr::cast($array);
        }

        // Entregar los datos del DTE.
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function getTED(): ?string
    {
        try {
            return $this->xmlDocument
                ->setEncoding('ISO-8859-1')
                ->C14NEncodedFlattened('//TED')
            ;
        } catch (XmlException $e) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplateTED(): array
    {
        return [
            'TED' => [
                '@attributes' => [
                    'version' => '1.0',
                ],
                'DD' => [
                    'RE' => $this->getRutEmisor(),
                    'TD' => $this->getCodigo(),
                    'F' => $this->getFolio(),
                    'FE' => $this->getFechaEmision(),
                    'RR' => $this->getRutReceptor(),
                    'RSR' => mb_substr($this->getRazonSocialReceptor(), 0, 40),
                    'MNT' => $this->getMontoTotal(),
                    'IT1' => mb_substr(
                        $this->getDetalle(0)['NmbItem'] ?? '',
                        0,
                        40
                    ),
                    'CAF' => '', // Se deberá agregar al timbrar.
                    'TSTED' => '', // Se deberá agregar al timbrar.
                ],
                'FRMT' => [
                    '@attributes' => [
                        'algoritmo' => 'SHA1withRSA',
                    ],
                    '@value' => '', // Se deberá agregar al timbrar.
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $selector): mixed
    {
        return Selector::get($this->getData(), $selector);
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query, array $params = []): string|array|null
    {
        return $this->xmlDocument->query($query, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'datos' => $this->getData(),
            'ted' => $this->getTED(),
            'xml' => $this->saveXml(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        if ($array['ted'] !== null) {
            $array['ted'] = base64_encode($array['ted']);
        }
        $array['xml'] = base64_encode($array['xml']);

        return $array;
    }
}

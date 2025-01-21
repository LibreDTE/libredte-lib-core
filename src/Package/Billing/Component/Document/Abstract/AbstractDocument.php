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

use Derafu\Lib\Core\Helper\Arr;
use Derafu\Lib\Core\Helper\Selector;
use Derafu\Lib\Core\Package\Prime\Component\Entity\Entity\Entity;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Contract\XmlInterface;
use Derafu\Lib\Core\Package\Prime\Component\Xml\Exception\XmlException;
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
     * @var XmlInterface
     */
    protected readonly XmlInterface $xmlDocument;

    /**
     * Datos del documento tributario estandarizados.
     *
     * @var array
     */
    private array $datos;

    /**
     * Constructor del documento tributario.
     *
     * @param XmlInterface $xmlDocument
     * @return void
     */
    public function __construct(XmlInterface $xmlDocument)
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
    public function getXmlDocument(): XmlInterface
    {
        return $this->xmlDocument;
    }

    /**
     * {@inheritDoc}
     */
    public function saveXml(): string
    {
        return $this->xmlDocument->saveXml();
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        return $this->xmlDocument->getXml();
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
        return (int) $this->xmlDocument->query('//Encabezado/IdDoc/Folio');
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
        return $this->xmlDocument->query('//Encabezado/Emisor');
    }

    /**
     * {@inheritDoc}
     */
    public function getRutEmisor(): string
    {
        return $this->xmlDocument->query('//Encabezado/Emisor/RUTEmisor');
    }

    /**
     * {@inheritDoc}
     */
    public function getReceptor(): array
    {
        return $this->xmlDocument->query('//Encabezado/Receptor');
    }

    /**
     * {@inheritDoc}
     */
    public function getRutReceptor(): string
    {
        return $this->xmlDocument->query('//Encabezado/Receptor/RUTRecep');
    }

    /**
     * {@inheritDoc}
     */
    public function getRazonSocialReceptor(): string
    {
        return $this->xmlDocument->query('//Encabezado/Receptor/RznSocRecep');
    }

    /**
     * {@inheritDoc}
     */
    public function getFechaEmision(): string
    {
        return $this->xmlDocument->query('//Encabezado/IdDoc/FchEmis');
    }

    /**
     * {@inheritDoc}
     */
    public function getTotales(): array
    {
        return $this->xmlDocument->query('//Encabezado/Totales');
    }

    /**
     * {@inheritDoc}
     */
    public function getMontoTotal(): int|float
    {
        $monto = (float) $this->xmlDocument->query('//Encabezado/Totales/MntTotal');

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
        $moneda = $this->query('//Encabezado/Totales/TpoMoneda') ?? 'PESO CL';

        return (string) $moneda;
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
    public function getDatos(): array
    {
        // Si los datos del DTE no están determinados se crean de una manera
        // estandarizada compatible con los datos de entrada normalizados.
        if (!isset($this->datos)) {
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

            $this->datos = Arr::autoCastRecursive($array);
        }

        // Entregar los datos del DTE.
        return $this->datos;
    }

    /**
     * {@inheritDoc}
     */
    public function getTED(): ?string
    {
        try {
            return $this->getXmlDocument()->C14NWithIsoEncodingFlattened('//TED');
        } catch (XmlException $e) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPlantillaTED(): array
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
        return Selector::get($this->getDatos(), $selector);
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query, array $params = []): string|array|null
    {
        return $this->xmlDocument->query($query, $params);
    }
}

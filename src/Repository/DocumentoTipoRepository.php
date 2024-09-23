<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca Estándar en PHP (Núcleo).
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

namespace libredte\lib\Core\Repository;

use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;

/**
 * Repositorio para trabajar con los tipos de documentos.
 */
class DocumentoTipoRepository
{
    /**
     * Datos por defecto del documento.
     *
     * @var array
     */
    private array $defaultData = [
        'codigo' => null,
        'nombre' => null,
        'categoria' => null,
        'electronico' => null,
        'enviar' => null,
        'compra' => null,
        'venta' => null,
        'operacion' => null,
        'cedible' => null,
        'tag_xml' => null,
        'disponible' => false,
    ];

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    private DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase.
     *
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(?DataProviderInterface $dataProvider = null)
    {
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Entrega el listado de todos los documentos registrados en el repositorio
     * de datos.
     *
     * @return array
     */
    public function getDocumentos(): array
    {
        return $this->dataProvider->getData('documentos');
    }

    /**
     * Entrega el listado de documentos tributarios.
     *
     * Este listado incluirá:
     *
     *   - Documentos tributarios no electrónicos.
     *   - Documentos tributarios electrónicos.
     *   - Documentos tributarios adicionales (otros documentos, ej: rango 9XX).
     *
     * @return array
     */
    public function getDocumentosTributarios(): array
    {
        return $this->dataProvider->search('documentos', [
            'categoria' => 'T',
        ]);
    }

    /**
     * Entrega el listado de documentos informativos.
     *
     * Este listado incluirá:
     *
     *   - Documentos informativos oficiales del SII (categoría "I").
     *   - Documentos informativos no oficiales del SII (categoría "R").
     *
     * @return array
     */
    public function getDocumentosInformativos(): array
    {
        return $this->dataProvider->search('documentos', [
            'categoria' => ['I', 'R'],
        ]);
    }

    /**
     * Entrega el listado de documentos tributarios electrónicos del SII.
     *
     * @return array
     */
    public function getDocumentosTributariosElectronicos(): array
    {
        return $this->dataProvider->search('documentos', [
            'categoria' => 'T',
            'electronico' => true,
        ]);
    }

    /**
     * Entrega el listado de documentos tributarios electrónicos del SII.
     *
     * @return array
     */
    public function getDocumentosTributariosElectronicosCedibles(): array
    {
        return $this->dataProvider->search('documentos', [
            'categoria' => 'T',
            'electronico' => true,
            'cedible' => true,
        ]);
    }

    /**
     * Entrega el listado de documentos tributarios electrónicos que pueden ser
     * emitidos utilizando LibreDTE.
     *
     * @return array
     */
    public function getDocumentosDisponibles(): array
    {
        return $this->dataProvider->search('documentos', [
            'categoria' => 'T',
            'electronico' => true,
            'disponible' => true,
        ]);
    }

    /**
     * Obtiene los datos de un documento a partir de su código.
     *
     * @param string|int $codigo Código del documento para buscar sus datos.
     * @return array Arreglo con los datos asociados al documento.
     */
    public function getData(string|int $codigo): array
    {
        $data = $this->dataProvider->getValue('documentos', $codigo);
        $data = array_merge($this->defaultData, ['codigo' => $codigo], $data);

        return $data;
    }
}

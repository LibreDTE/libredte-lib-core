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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Service;

use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafFolioInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Contract\CafManagerInterface;
use libredte\lib\Core\Package\Billing\Component\Identifier\Entity\Caf;
use libredte\lib\Core\Package\Billing\Component\Identifier\Support\CafFolio;
use RuntimeException;

/**
 * Implementación de un gestor de archivos CAF.
 *
 * Agrupa múltiples CAFs por tipo de documento y entrega folios en orden,
 * cruzando de un CAF al siguiente cuando se agota el anterior.
 *
 * Soporta marcar folios como ya consumidos (emitidos previamente), lo que
 * permite especificar un folio de inicio distinto al primero del CAF o
 * incluso rangos con gaps: "1-6,10,14,16-20".
 */
class CafManager implements CafManagerInterface
{
    /**
     * Pool de CAFs agrupados por tipo de documento.
     *
     * Cada entrada es un array de CafInterface ordenado por folio desde.
     *
     * @var array<int, CafInterface[]>
     */
    private array $pool = [];

    /**
     * Conjunto de folios ya consumidos por tipo de documento.
     *
     * Usa el folio como clave para búsqueda O(1).
     *
     * @var array<int, array<int, true>>
     */
    private array $consumed = [];

    /**
     * {@inheritDoc}
     */
    public function add(string $xml): static
    {
        $caf = new Caf($xml);
        $dte = $caf->getTipoDocumento();

        if (!isset($this->pool[$dte])) {
            $this->pool[$dte] = [];
        }

        // Ignorar si ya existe un CAF con el mismo tipo y folio desde.
        foreach ($this->pool[$dte] as $existing) {
            if ($existing->getFolioDesde() === $caf->getFolioDesde()) {
                return $this;
            }
        }

        $this->pool[$dte][] = $caf;

        // Mantener ordenado por folio desde dentro del tipo.
        usort(
            $this->pool[$dte],
            fn (CafInterface $a, CafInterface $b) => $a->getFolioDesde() <=> $b->getFolioDesde()
        );

        // Mantener el pool ordenado por código de tipo de documento.
        ksort($this->pool);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function markConsumed(int $dte, string|array $folios): static
    {
        if (!isset($this->consumed[$dte])) {
            $this->consumed[$dte] = [];
        }

        foreach ($this->parseFolios($folios) as $folio) {
            $this->consumed[$dte][$folio] = true;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAvailableRange(int $dte, string|array $folios): static
    {
        if (!isset($this->pool[$dte])) {
            throw new RuntimeException(sprintf(
                'Debe agregar CAFs del tipo %d antes de llamar a setAvailable().',
                $dte
            ));
        }

        $available = array_flip($this->parseFolios($folios));

        // Resetear estado para este tipo y marcar como consumido todo lo que
        // no esté en la lista de disponibles.
        $this->consumed[$dte] = [];

        foreach ($this->pool[$dte] as $caf) {
            for ($f = $caf->getFolioDesde(); $f <= $caf->getFolioHasta(); $f++) {
                if (!isset($available[$f])) {
                    $this->consumed[$dte][$f] = true;
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableRange(int $dte): string
    {
        if (!isset($this->pool[$dte])) {
            return '';
        }

        // Recopilar todos los folios disponibles en orden.
        $available = [];
        foreach ($this->pool[$dte] as $caf) {
            for ($f = $caf->getFolioDesde(); $f <= $caf->getFolioHasta(); $f++) {
                if (!isset($this->consumed[$dte][$f])) {
                    $available[] = $f;
                }
            }
        }

        if (empty($available)) {
            return '';
        }

        // Compactar en rangos: [1,2,3,5,7,8,9] → "1-3,5,7-9"
        sort($available);
        $ranges = [];
        $start = $available[0];
        $prev = $available[0];

        for ($i = 1, $count = count($available); $i < $count; $i++) {
            if ($available[$i] === $prev + 1) {
                $prev = $available[$i];
            } else {
                $ranges[] = $start === $prev ? (string) $start : "$start-$prev";
                $start = $available[$i];
                $prev = $available[$i];
            }
        }
        $ranges[] = $start === $prev ? (string) $start : "$start-$prev";

        return implode(',', $ranges);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(int $dte, int $folioDesde): static
    {
        if (!isset($this->pool[$dte])) {
            throw new RuntimeException(sprintf(
                'No hay CAFs cargados para el tipo de documento %d.',
                $dte
            ));
        }

        $found = false;
        foreach ($this->pool[$dte] as $index => $caf) {
            if ($caf->getFolioDesde() === $folioDesde) {
                // Limpiar folios consumidos de este CAF.
                for ($f = $caf->getFolioDesde(); $f <= $caf->getFolioHasta(); $f++) {
                    unset($this->consumed[$dte][$f]);
                }

                // Eliminar del pool.
                unset($this->pool[$dte][$index]);
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new RuntimeException(sprintf(
                'No se encontró un CAF para el tipo %d con folio desde %d.',
                $dte,
                $folioDesde
            ));
        }

        // Reindexar el array.
        $this->pool[$dte] = array_values($this->pool[$dte]);

        // Si no quedan CAFs para este tipo, limpiar completamente.
        if (empty($this->pool[$dte])) {
            unset($this->pool[$dte]);
            unset($this->consumed[$dte]);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasEnoughFolios(int $dte, int $cantidad = 1): bool
    {
        if (!isset($this->pool[$dte])) {
            return false;
        }

        $available = 0;

        foreach ($this->pool[$dte] as $caf) {
            for ($f = $caf->getFolioDesde(); $f <= $caf->getFolioHasta(); $f++) {
                if (!isset($this->consumed[$dte][$f])) {
                    $available++;
                    if ($available >= $cantidad) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function consume(int $dte): CafFolioInterface
    {
        if (!$this->hasEnoughFolios($dte)) {
            throw new RuntimeException(sprintf(
                'No hay folios disponibles para el tipo de documento %d.',
                $dte
            ));
        }

        // Buscar el primer folio disponible en orden de los CAFs.
        foreach ($this->pool[$dte] as $caf) {
            for ($f = $caf->getFolioDesde(); $f <= $caf->getFolioHasta(); $f++) {
                if (!isset($this->consumed[$dte][$f])) {
                    $this->markConsumed($dte, [$f]);
                    return new CafFolio($f, $caf);
                }
            }
        }

        // No debería llegar aquí si hasEnoughFolios() es correcto.
        throw new RuntimeException(sprintf(
            'No se encontró folio disponible para el tipo de documento %d.',
            $dte
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getCafs(): array
    {
        $result = [];
        foreach ($this->pool as $cafs) {
            foreach ($cafs as $caf) {
                $result[] = $caf;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getCafForFolio(int $dte, int $folio): CafInterface
    {
        if (!isset($this->pool[$dte])) {
            throw new RuntimeException(sprintf(
                'No hay CAFs cargados para el tipo de documento %d.',
                $dte
            ));
        }

        foreach ($this->pool[$dte] as $caf) {
            if ($folio >= $caf->getFolioDesde() && $folio <= $caf->getFolioHasta()) {
                return $caf;
            }
        }

        throw new RuntimeException(sprintf(
            'No se encontró un CAF para el tipo %d con el folio %d.',
            $dte,
            $folio
        ));
    }

    /**
     * Parsea folios desde un string de rangos o un array de enteros.
     *
     * El string acepta rangos separados por coma: "1-6,10,14,16-20".
     * El array debe contener enteros: [1, 2, 3, 4, 5, 6, 10].
     *
     * @param string|int[] $folios
     * @return int[]
     */
    private function parseFolios(string|array $folios): array
    {
        if (is_array($folios)) {
            return $folios;
        }

        $result = [];

        foreach (explode(',', $folios) as $segment) {
            $segment = trim($segment);

            if (str_contains($segment, '-')) {
                [$from, $to] = explode('-', $segment, 2);
                for ($i = (int) $from; $i <= (int) $to; $i++) {
                    $result[] = $i;
                }
            } else {
                $result[] = (int) $segment;
            }
        }

        return $result;
    }
}

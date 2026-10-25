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

namespace libredte\lib\Core\Package\Billing\Component\Identifier\Contract;

/**
 * Interfaz para el gestor de archivos CAF.
 */
interface CafManagerInterface
{
    /**
     * Agrega un archivo CAF al pool.
     *
     * Puede llamarse N veces, mismo o distinto TpoDoc.
     *
     * @param string $xml
     * @return static
     */
    public function add(string $xml): static;

    /**
     * Marca folios como ya consumidos (estado previo al uso de este manager).
     *
     * Operación aditiva: puede llamarse N veces y los folios se acumulan.
     * Útil cuando se tiene un registro de folios ya emitidos antes de iniciar
     * el proceso actual.
     *
     * Casos de uso:
     *
     *   // Iniciar desde el folio 7 (los anteriores ya fueron emitidos):
     *   $manager->markConsumed(33, '1-6');
     *
     *   // Excluir folios específicos con gaps:
     *   $manager->markConsumed(33, '1-6,10,15');
     *
     *   // Llamadas acumulativas (equivale al ejemplo anterior):
     *   $manager->markConsumed(33, '1-6');
     *   $manager->markConsumed(33, '10,15');
     *
     *   // Desde un array de enteros:
     *   $manager->markConsumed(33, [1, 2, 3, 4, 5, 6]);
     *
     * Formato del string de rangos: segmentos separados por coma, cada
     * segmento es un folio individual ("10") o un rango inclusivo ("1-6").
     *
     * @param int $dte Tipo de documento.
     * @param string|int[] $folios Folios a marcar como consumidos.
     * @return static
     */
    public function markConsumed(int $dte, string|array $folios): static;

    /**
     * Define explícitamente qué folios están disponibles para consumo.
     *
     * Operación definitiva: reemplaza el estado de disponibilidad anterior
     * para el TpoDoc indicado. Todo folio del pool que no aparezca en la
     * lista se marca automáticamente como consumido.
     *
     * Requiere que los CAFs del tipo ya hayan sido cargados con add(), ya que
     * necesita conocer el pool completo para calcular el complemento.
     *
     * Útil cuando el sistema externo entrega directamente la lista de folios
     * disponibles (en lugar de la lista de usados).
     *
     * Casos de uso:
     *
     *   // Solo estos folios están disponibles (rangos con gaps):
     *   $manager->setAvailableRange(33, '7-9,11,14,16-20');
     *
     *   // Desde un array de enteros:
     *   $manager->setAvailableRange(33, [7, 8, 9, 11, 14, 16, 17, 18, 19, 20]);
     *
     * Formato del string de rangos: segmentos separados por coma, cada
     * segmento es un folio individual ("11") o un rango inclusivo ("7-9").
     *
     * @param int $dte Tipo de documento.
     * @param string|int[] $folios Folios que están disponibles para consumo.
     * @return static
     */
    public function setAvailableRange(int $dte, string|array $folios): static;

    /**
     * Obtiene los folios disponibles para un tipo de documento como string
     * de rangos compactos.
     *
     * Recorre todos los CAFs del tipo, identifica los folios no consumidos y
     * los devuelve en formato de rangos separados por coma.
     *
     * Ejemplo de retorno: "1-6,10,14,16-20"
     *
     * @param int $dte Tipo de documento.
     * @return string Rangos de folios disponibles. Vacío si no hay disponibles.
     */
    public function getAvailableRange(int $dte): string;

    /**
     * Resetea los folios consumidos de un tipo de documento.
     *
     * Deja todos los folios del pool del tipo indicado disponibles para
     * consumo, como si nunca se hubiera llamado a markConsumed() ni a
     * setAvailableRange() para ese tipo.
     *
     * Es una no-op si el tipo no tiene CAFs cargados.
     *
     * @param int $dte Tipo de documento.
     * @return static
     */
    public function resetConsumedFolios(int $dte): static;

    /**
     * Elimina un CAF específico del pool.
     *
     * Identifica el CAF por su tipo de documento y folio de inicio, que juntos
     * son únicos. Al eliminar un CAF, también se limpian de la lista de
     * consumidos los folios que pertenecían a ese CAF.
     *
     * @param int $dte Tipo de documento.
     * @param int $folioDesde Folio de inicio del CAF a eliminar.
     * @return static
     * @throws \RuntimeException Si no se encuentra el CAF especificado.
     */
    public function remove(int $dte, int $folioDesde): static;

    /**
     * ¿Hay suficientes folios disponibles para este tipo?
     *
     * @param int $dte
     * @param int $cantidad
     * @return bool
     */
    public function hasEnoughFolios(int $dte, int $cantidad = 1): bool;

    /**
     * Consumir el próximo folio.
     *
     * Devuelve el folio + el CAF asociado (operación atómica).
     *
     * @param int $dte
     * @return CafFolioInterface
     */
    public function consume(int $dte): CafFolioInterface;

    /**
     * Devuelve todos los CAFs cargados como lista plana.
     *
     * Se usa principalmente para persistir los inputs del proceso. El nombre
     * de archivo canónico de cada CAF se construye a partir de
     * getTipoDocumento() y getFolioDesde(), que juntos son únicos.
     *
     * @return CafInterface[]
     */
    public function getCafs(): array;

    /**
     * Obtiene el CAF que cubre un folio ya asignado.
     *
     * A diferencia de consume(), no modifica el estado del pool. Se usa cuando
     * el folio fue asignado previamente (por un parser) y se necesita el CAF
     * para timbrar y firmar el documento.
     *
     * @param int $dte Tipo de documento.
     * @param int $folio Folio a buscar.
     * @return CafInterface
     * @throws \RuntimeException Si no hay CAF cargado que cubra el folio.
     */
    public function getCafForFolio(int $dte, int $folio): CafInterface;
}

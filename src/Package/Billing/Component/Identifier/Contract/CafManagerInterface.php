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

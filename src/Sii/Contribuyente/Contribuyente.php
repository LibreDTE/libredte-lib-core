<?php

declare(strict_types=1);

/**
 * LibreDTE: Biblioteca PHP (Núcleo).
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

namespace libredte\lib\Core\Sii\Contribuyente;

use libredte\lib\Core\Helper\Rut;
use libredte\lib\Core\Service\ArrayDataProvider;
use libredte\lib\Core\Service\DataProviderInterface;
use libredte\lib\Core\Signature\Certificate;
use libredte\lib\Core\Signature\CertificateFaker;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\Caf;
use libredte\lib\Core\Sii\Dte\AutorizacionFolio\CafFaker;

/**
 * Clase para representar un contribuyente en el sistema del SII (Servicio de
 * Impuestos Internos).
 *
 * Proporciona información básica del contribuyente, como su RUT, razón social,
 * giro, entre otros.
 */
class Contribuyente
{
    /**
     * RUT del contribuyente.
     *
     * @var int
     */
    private int $rut;

    /**
     * Dígito verificador (DV) del RUT.
     *
     * @var string
     */
    private string $dv;

    /**
     * Razón social del contribuyente.
     *
     * @var string|null
     */
    private ?string $razon_social;

    /**
     * Giro comercial del contribuyente.
     *
     * @var string|null
     */
    private ?string $giro;

    /**
     * Código de actividad económica del contribuyente.
     *
     * @var int|null
     */
    private ?int $actividad_economica;

    /**
     * Teléfono del contribuyente.
     *
     * @var string|null
     */
    private ?string $telefono;

    /**
     * Dirección de correo electrónico del contribuyente.
     *
     * @var string|null
     */
    private ?string $email;

    /**
     * Dirección física del contribuyente.
     *
     * @var string|null
     */
    private ?string $direccion;

    /**
     * Comuna de residencia del contribuyente.
     *
     * @var string|null
     */
    private ?string $comuna;

    /**
     * Proveedor de datos.
     *
     * @var DataProviderInterface
     */
    protected DataProviderInterface $dataProvider;

    /**
     * Constructor de la clase Contribuyente.
     *
     * @param string|int|null $rut RUT del contribuyente.
     * @param string|null $razon_social Razón social del contribuyente.
     * @param string|null $giro Giro comercial del contribuyente.
     * @param int|null $actividad_economica Código de actividad económica.
     * @param string|null $telefono Teléfono del contribuyente.
     * @param string|null $email Correo electrónico del contribuyente.
     * @param string|null $direccion Dirección física del contribuyente.
     * @param string|null $comuna Comuna de residencia.
     * @param array|null $data Datos adicionales como array.
     * @param DataProviderInterface|null $dataProvider Proveedor de datos.
     */
    public function __construct(
        string|int|null $rut = null,
        ?string $razon_social = null,
        ?string $giro = null,
        ?int $actividad_economica = null,
        ?string $telefono = null,
        ?string $email = null,
        ?string $direccion = null,
        ?string $comuna = null,
        ?array $data = null,
        ?DataProviderInterface $dataProvider = null
    ) {
        // Asignar datos pasados en variable $data como arreglo.
        if ($data !== null) {
            $this->setData($data);
        }

        // Asignar datos pasados de manera individual.
        else {
            $rut = Rut::format(is_null($rut) ? '66666666-6' : $rut);
            [$this->rut, $this->dv] = Rut::toArray($rut);

            $this->razon_social = $razon_social ?: null;
            $this->giro = $giro ?: null;
            $this->actividad_economica = $actividad_economica ?: null;
            $this->telefono = $telefono ?: null;
            $this->email = $email ?: null;
            $this->direccion = $direccion ?: null;
            $this->comuna = $comuna ?: null;
        }

        // Validar el RUT asignado (independiente del origen).
        Rut::validate($this->getRut());

        // Asignar proveedor de datos si se pasó o crear uno.
        $this->dataProvider = $dataProvider ?? new ArrayDataProvider();
    }

    /**
     * Asigna los datos del contribuyente desde un array.
     *
     * @param array $data Datos del contribuyente.
     */
    private function setData(array $data): void
    {
        $rut = (
            $data['rut']
            ?? $data['RUTEmisor']
            ?? $data['RUTRecep']
            ?? null
        ) ?: '66666666-6';
        [$this->rut, $this->dv] = Rut::toArray($rut);

        $this->razon_social = (
            $data['razon_social']
            ?? $data['RznSoc']
            ?? $data['RznSocEmisor']
            ?? $data['RznSocRecep']
            ?? null
        ) ?: null;

        $this->giro = (
            $data['giro']
            ?? $data['GiroEmis']
            ?? $data['GiroEmisor']
            ?? $data['GiroRecep']
            ?? null
        ) ?: null;

        $this->actividad_economica = (
            (int) (
                $data['actividad_economica']
                ?? $data['Acteco']
                ?? 0
            )
        ) ?: null;

        $this->telefono = (
            $data['telefono']
            ?? $data['Telefono']
            ?? $data['Contacto']
            ?? null
        ) ?: null;

        $this->email = (
            $data['email']
            ?? $data['CorreoEmisor']
            ?? $data['CorreoRecep']
            ?? null
        ) ?: null;

        $this->direccion = (
            $data['direccion']
            ?? $data['DirOrigen']
            ?? $data['DirRecep']
            ?? null
        ) ?: null;

        $this->comuna = (
            $data['comuna']
            ?? $data['CmnaOrigen']
            ?? $data['CmnaRecep']
            ?? null
        ) ?: null;
    }

    /**
     * Devuelve el RUT completo (incluyendo el DV) del contribuyente.
     *
     * @return string RUT completo del contribuyente.
     */
    public function getRut(): string
    {
        return $this->rut . '-' . $this->dv;
    }

    /**
     * Devuelve la razón social del contribuyente.
     *
     * Si no hay razón social, devuelve el RUT.
     *
     * @return string Razón social o RUT.
     */
    public function getRazonSocial(): string
    {
        return $this->razon_social ?? $this->getRut();
    }

    /**
     * Devuelve el giro comercial del contribuyente.
     *
     * @return string|null Giro del contribuyente o null si no se especifica.
     */
    public function getGiro(): ?string
    {
        return $this->giro;
    }

    /**
     * Devuelve el código de actividad económica del contribuyente.
     *
     * @return int|null Código de actividad económica o null.
     */
    public function getActividadEconomica(): ?int
    {
        return $this->actividad_economica;
    }

    /**
     * Devuelve el teléfono del contribuyente.
     *
     * @return string|null Teléfono del contribuyente o null.
     */
    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    /**
     * Devuelve el correo electrónico del contribuyente.
     *
     * @return string|null Correo electrónico del contribuyente o null.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Devuelve la dirección del contribuyente.
     *
     * @return string|null Dirección del contribuyente o null.
     */
    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    /**
     * Devuelve la comuna del contribuyente.
     *
     * @return string|null Comuna del contribuyente o null.
     */
    public function getComuna(): ?string
    {
        return $this->comuna;
    }

    /**
     * Genera y devuelve un certificado ficticio para el contribuyente.
     *
     * @return Certificate Certificado ficticio del contribuyente.
     */
    public function getFakeCertificate(): Certificate
    {
        $faker = new CertificateFaker();
        $faker->setSubject(
            serialNumber: $this->getRut(),
        );

        return $faker->create();
    }

    /**
     * Genera y devuelve un CAF (Código de Autorización de Folios)
     * ficticio para el contribuyente.
     *
     * @param int $codigoDocumento Código del tipo de documento.
     * @param int $folioDesde Número de folio inicial.
     * @param int|null $folioHasta Número de folio final. Si es `null`, se usa
     * el mismo valor de $folioDesde.
     * @return Caf CAF ficticio generado para el contribuyente.
     */
    public function getFakeCaf(
        int $codigoDocumento,
        int $folioDesde,
        ?int $folioHasta = null
    ): Caf {
        if ($folioHasta === null) {
            $folioHasta = $folioDesde;
        }

        $faker = new CafFaker($this->dataProvider);
        $faker->setEmisor($this->getRut(), $this->getRazonSocial());
        $faker->setTipoDocumento($codigoDocumento);
        $faker->setRangoFolios($folioDesde, $folioHasta);

        return $faker->create();
    }
}

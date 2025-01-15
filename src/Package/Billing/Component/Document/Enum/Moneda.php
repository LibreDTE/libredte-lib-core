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

namespace libredte\lib\Core\Package\Billing\Component\Document\Enum;

/**
 * Enum con las monedas reconocidas en la aplicación.
 */
enum Moneda: string
{
    /**
     * Pesos chilenos.
     */
    case CLP = 'CLP';

    /**
     * Unidad de Fomento de Chile (UF).
     */
    case CLF = 'CLF';

    /**
     * Unidad Tributaria Mensual de Chile.
     */
    case UTM = 'UTM';

    /**
     * Unidad Tributaria Anual de Chile.
     */
    case UTA = 'UTA';

    /**
     * Dólar de Estados Unidos.
     */
    case USD = 'USD';

    /**
     * Euro.
     */
    case EUR = 'EUR';

    /**
     * I love Bitcoin <3
     */
    case BTC = 'BTC';

    /**
     * Peso argentino.
     */
    case ARS = 'ARS';

    /**
     * Libra esterlina.
     */
    case GBP = 'GBP';

    /**
     * Corona sueca.
     */
    case SEK = 'SEK';

    /**
     * Dólar de Hong Kong.
     */
    case HKD = 'HKD';

    /**
     * Rand sudafricano.
     */
    case ZAR = 'ZAR';

    /**
     * Peso colombiano.
     */
    case COP = 'COP';

    /**
     * Peso mexicano.
     */
    case MXN = 'MXN';

    /**
     * Bolívar venezolano.
     */
    case VES = 'VES';

    /**
     * Dólar de Singapur.
     */
    case SGD = 'SGD';

    /**
     * Rupia india.
     */
    case INR = 'INR';

    /**
     * Nuevo dólar taiwanés.
     */
    case TWD = 'TWD';

    /**
     * Dirham de Emiratos Árabes Unidos.
     */
    case AED = 'AED';

    /**
     * Won surcoreano.
     */
    case KRW = 'KRW';

    /**
     * Zloty polaco.
     */
    case PLN = 'PLN';

    /**
     * Corona checa.
     */
    case CZK = 'CZK';

    /**
     * Forint húngaro.
     */
    case HUF = 'HUF';

    /**
     * Baht tailandés.
     */
    case THB = 'THB';

    /**
     * Lira turca.
     */
    case TRY = 'TRY';

    /**
     * Ringgit malayo.
     */
    case MYR = 'MYR';

    /**
     * Rublo ruso.
     */
    case RUB = 'RUB';

    /**
     * Rupia indonesia.
     */
    case IDR = 'IDR';

    /**
     * Grivna ucraniana.
     */
    case UAH = 'UAH';

    /**
     * Shekel israelí.
     */
    case ILS = 'ILS';

    /**
     * Peso filipino.
     */
    case PHP = 'PHP';

    /**
     * Riyal saudí.
     */
    case SAR = 'SAR';

    /**
     * Rupia pakistaní.
     */
    case PKR = 'PKR';

    /**
     * Dong vietnamita.
     */
    case VND = 'VND';

    /**
     * Libra egipcia.
     */
    case EGP = 'EGP';

    /**
     * Leu rumano.
     */
    case RON = 'RON';

    /**
     * Corona islandesa.
     */
    case ISK = 'ISK';

    /**
     * Rial iraní.
     */
    case IRR = 'IRR';

    /**
     * Colón costarricense.
     */
    case CRC = 'CRC';

    /**
     * Balboa panameño.
     */
    case PAB = 'PAB';

    /**
     * Guaraní paraguayo.
     */
    case PYG = 'PYG';

    /**
     * Sol peruano.
     */
    case PEN = 'PEN';

    /**
     * Peso uruguayo.
     */
    case UYU = 'UYU';

    /**
     * Dólar australiano.
     */
    case AUD = 'AUD';

    /**
     * Boliviano.
     */
    case BOB = 'BOB';

    /**
     * Yuan chino.
     */
    case CNY = 'CNY';

    /**
     * Real brasileño.
     */
    case BRL = 'BRL';

    /**
     * Corona danesa.
     */
    case DKK = 'DKK';

    /**
     * Dólar canadiense.
     */
    case CAD = 'CAD';

    /**
     * Yen japonés.
     */
    case JPY = 'JPY';

    /**
     * Franco suizo.
     */
    case CHF = 'CHF';

    /**
     * Corona noruega.
     */
    case NOK = 'NOK';

    /**
     * Dólar neozelandés.
     */
    case NZD = 'NZD';

    /**
     * Monedas no especificadas.
     *
     * En estricto rigor ISO 4217 define XXX como "Sin divisa".
     */
    case XXX = 'XXX';

    /**
     * Cantidad de decimales que cada moneda puede tener.
     *
     * @var array
     */
    private const DECIMALES = [
        self::CLP->value => 0,
        self::CLF->value => 2,
        self::UTM->value => 0,
        self::UTA->value => 0,
        self::USD->value => 2,
        self::EUR->value => 2,
        self::BTC->value => 8,
        self::ARS->value => 2,
        self::GBP->value => 2,
        self::SEK->value => 2,
        self::HKD->value => 2,
        self::ZAR->value => 2,
        self::COP->value => 2,
        self::MXN->value => 2,
        self::VES->value => 2,
        self::SGD->value => 2,
        self::INR->value => 2,
        self::TWD->value => 2,
        self::AED->value => 2,
        self::KRW->value => 0,
        self::PLN->value => 2,
        self::CZK->value => 2,
        self::HUF->value => 2,
        self::THB->value => 2,
        self::TRY->value => 2,
        self::MYR->value => 2,
        self::RUB->value => 2,
        self::IDR->value => 2,
        self::UAH->value => 2,
        self::ILS->value => 2,
        self::PHP->value => 2,
        self::SAR->value => 2,
        self::PKR->value => 2,
        self::VND->value => 0,
        self::EGP->value => 2,
        self::RON->value => 2,
        self::ISK->value => 0,
        self::IRR->value => 2,
        self::CRC->value => 2,
        self::PAB->value => 2,
        self::PYG->value => 0,
        self::PEN->value => 2,
        self::UYU->value => 2,
        self::AUD->value => 2,
        self::BOB->value => 2,
        self::CNY->value => 2,
        self::BRL->value => 2,
        self::DKK->value => 2,
        self::CAD->value => 2,
        self::JPY->value => 0,
        self::CHF->value => 2,
        self::NOK->value => 2,
        self::NZD->value => 2,
        self::XXX->value => 2,
    ];

    /**
     * Símbolos de las monedas reconocidas.
     *
     * @var array
     */
    private const SIMBOLOS = [
        self::CLP->value => '$',
        self::CLF->value => 'UF',
        self::UTM->value => 'UTM',
        self::UTA->value => 'UTA',
        self::USD->value => '$',
        self::EUR->value => '€',
        self::BTC->value => '₿',
        self::ARS->value => '$',
        self::GBP->value => '£',
        self::SEK->value => 'kr',
        self::HKD->value => 'HK$',
        self::ZAR->value => 'R',
        self::COP->value => '$',
        self::MXN->value => '$',
        self::VES->value => 'Bs.',
        self::SGD->value => 'S$',
        self::INR->value => '₹',
        self::TWD->value => 'NT$',
        self::AED->value => 'د.إ',
        self::KRW->value => '₩',
        self::PLN->value => 'zł',
        self::CZK->value => 'Kč',
        self::HUF->value => 'Ft',
        self::THB->value => '฿',
        self::TRY->value => '₺',
        self::MYR->value => 'RM',
        self::RUB->value => '₽',
        self::IDR->value => 'Rp',
        self::UAH->value => '₴',
        self::ILS->value => '₪',
        self::PHP->value => '₱',
        self::SAR->value => '﷼',
        self::PKR->value => '₨',
        self::VND->value => '₫',
        self::EGP->value => '£',
        self::RON->value => 'lei',
        self::ISK->value => 'kr',
        self::IRR->value => '﷼',
        self::CRC->value => '₡',
        self::PAB->value => 'B/.',
        self::PYG->value => '₲',
        self::PEN->value => 'S/',
        self::UYU->value => '$U',
        self::AUD->value => 'A$',
        self::BOB->value => 'Bs.',
        self::CNY->value => '¥',
        self::BRL->value => 'R$',
        self::DKK->value => 'kr',
        self::CAD->value => 'C$',
        self::JPY->value => '¥',
        self::CHF->value => 'CHF',
        self::NOK->value => 'kr',
        self::NZD->value => 'NZ$',
        self::XXX->value => '',
    ];

    /**
     * Entrega la cantidad de decimales de la moneda.
     *
     * @return int
     */
    public function getDecimales(): int
    {
        return self::DECIMALES[$this->value];
    }

    /**
     * Entrega el símbolo de la moneda.
     *
     * @return string
     */
    public function getSimbolo(): string
    {
        return self::SIMBOLOS[$this->value];
    }
}

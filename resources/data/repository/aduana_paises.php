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

/**
 * Países de aduana (documentos de exportación).
 *
 * Cada país tiene la glosa del SII y su código ISO 3166-1 alpha-2
 * (`codigo_iso`), que es el que usa UBL (`Country/IdentificationCode`) y otros
 * estándares. El código ISO es `null` cuando el país del SII no tiene un código
 * ISO 3166-1 vigente: países que ya no existen (por ejemplo, U.R.S.S.), grupos
 * de territorios (por ejemplo, TERR.BRIT.EN AF), zonas francas y los códigos
 * especiales (9xx). Cada código ISO está en un solo país del SII: si el SII
 * tiene dos para el mismo país, el código ISO queda en el vigente.
 *
 * Los países sin código ISO tienen un comentario con el motivo y con el país
 * recomendado para usar en su lugar. Al final están los países que ya no
 * existen en los listados oficiales del SII.
 */
return [
    // Paises más comunes.
    563 => [
        'glosa' => 'ALEMANIA',
        'codigo_iso' => 'DE',
    ],
    224 => [
        'glosa' => 'ARGENTINA',
        'codigo_iso' => 'AR',
    ],
    406 => [
        'glosa' => 'AUSTRALIA',
        'codigo_iso' => 'AU',
    ],
    220 => [
        'glosa' => 'BRASIL',
        'codigo_iso' => 'BR',
    ],
    226 => [
        'glosa' => 'CANADA',
        'codigo_iso' => 'CA',
    ],
    505 => [
        'glosa' => 'FRANCIA',
        'codigo_iso' => 'FR',
    ],
    510 => [
        'glosa' => 'REINO UNIDO',
        'codigo_iso' => 'GB',
    ],
    225 => [
        'glosa' => 'U.S.A.',
        'codigo_iso' => 'US',
    ],
    997 => [
        'glosa' => 'CHILE',
        'codigo_iso' => 'CL',
    ],
    // Paises ordenados por alfabeto.
    308 => [
        'glosa' => 'AFGANISTAN',
        'codigo_iso' => 'AF',
    ],
    518 => [
        'glosa' => 'ALBANIA',
        'codigo_iso' => 'AL',
    ],
    525 => [
        'glosa' => 'ANDORRA',
        'codigo_iso' => 'AD',
    ],
    140 => [
        'glosa' => 'ANGOLA',
        'codigo_iso' => 'AO',
    ],
    242 => [
        'glosa' => 'ANGUILA',
        'codigo_iso' => 'AI',
    ],
    240 => [
        'glosa' => 'ANTIGUA Y BBUDA',
        'codigo_iso' => 'AG',
    ],
    // Sin código ISO: ISO retiró el código de las Antillas Neerlandesas (AN) en
    // 2010 y el SII no tiene los países que las sucedieron (Curazao, Sint
    // Maarten y Bonaire, San Eustaquio y Saba). Recomendado: ninguno.
    247 => [
        'glosa' => 'ANTILLAS NEERLANDESA',
        'codigo_iso' => null,
    ],
    302 => [
        'glosa' => 'ARABIA SAUDITA',
        'codigo_iso' => 'SA',
    ],
    127 => [
        'glosa' => 'ARGELIA',
        'codigo_iso' => 'DZ',
    ],
    540 => [
        'glosa' => 'ARMENIA',
        'codigo_iso' => 'AM',
    ],
    243 => [
        'glosa' => 'ARUBA',
        'codigo_iso' => 'AW',
    ],
    509 => [
        'glosa' => 'AUSTRIA',
        'codigo_iso' => 'AT',
    ],
    541 => [
        'glosa' => 'AZERBAIJAN',
        'codigo_iso' => 'AZ',
    ],
    207 => [
        'glosa' => 'BAHAMAS',
        'codigo_iso' => 'BS',
    ],
    313 => [
        'glosa' => 'BAHREIN',
        'codigo_iso' => 'BH',
    ],
    321 => [
        'glosa' => 'BANGLADESH',
        'codigo_iso' => 'BD',
    ],
    204 => [
        'glosa' => 'BARBADOS',
        'codigo_iso' => 'BB',
    ],
    542 => [
        'glosa' => 'BELARUS',
        'codigo_iso' => 'BY',
    ],
    514 => [
        'glosa' => 'BELGICA',
        'codigo_iso' => 'BE',
    ],
    236 => [
        'glosa' => 'BELICE',
        'codigo_iso' => 'BZ',
    ],
    150 => [
        'glosa' => 'BENIN',
        'codigo_iso' => 'BJ',
    ],
    244 => [
        'glosa' => 'BERMUDAS',
        'codigo_iso' => 'BM',
    ],
    318 => [
        'glosa' => 'BHUTAN',
        'codigo_iso' => 'BT',
    ],
    221 => [
        'glosa' => 'BOLIVIA',
        'codigo_iso' => 'BO',
    ],
    543 => [
        'glosa' => 'BOSNIA HEZGVINA',
        'codigo_iso' => 'BA',
    ],
    113 => [
        'glosa' => 'BOTSWANA',
        'codigo_iso' => 'BW',
    ],
    344 => [
        'glosa' => 'BRUNEI',
        'codigo_iso' => 'BN',
    ],
    527 => [
        'glosa' => 'BULGARIA',
        'codigo_iso' => 'BG',
    ],
    161 => [
        'glosa' => 'BURKINA FASO',
        'codigo_iso' => 'BF',
    ],
    141 => [
        'glosa' => 'BURUNDI',
        'codigo_iso' => 'BI',
    ],
    129 => [
        'glosa' => 'CABO VERDE',
        'codigo_iso' => 'CV',
    ],
    315 => [
        'glosa' => 'CAMBODIA',
        'codigo_iso' => 'KH',
    ],
    149 => [
        'glosa' => 'CAMERUN',
        'codigo_iso' => 'CM',
    ],
    130 => [
        'glosa' => 'CHAD',
        'codigo_iso' => 'TD',
    ],
    336 => [
        'glosa' => 'CHINA',
        'codigo_iso' => 'CN',
    ],
    305 => [
        'glosa' => 'CHIPRE',
        'codigo_iso' => 'CY',
    ],
    202 => [
        'glosa' => 'COLOMBIA',
        'codigo_iso' => 'CO',
    ],
    // Sin código ISO: No es un país (los demás). Recomendado: ninguno.
    888 => [
        'glosa' => 'LOS DEMAS',
        'codigo_iso' => null,
    ],
    // Sin código ISO: No es un país (código especial de aduana). Recomendado:
    // ninguno.
    901 => [
        'glosa' => 'COMB.Y LUBRIC.',
        'codigo_iso' => null,
    ],
    118 => [
        'glosa' => 'COMORAS',
        'codigo_iso' => 'KM',
    ],
    144 => [
        'glosa' => 'CONGO',
        'codigo_iso' => 'CG',
    ],
    334 => [
        'glosa' => 'COREA DEL NORTE',
        'codigo_iso' => 'KP',
    ],
    333 => [
        'glosa' => 'COREA DEL SUR',
        'codigo_iso' => 'KR',
    ],
    107 => [
        'glosa' => 'COSTA DE MARFIL',
        'codigo_iso' => 'CI',
    ],
    211 => [
        'glosa' => 'COSTA RICA',
        'codigo_iso' => 'CR',
    ],
    547 => [
        'glosa' => 'CROACIA',
        'codigo_iso' => 'HR',
    ],
    209 => [
        'glosa' => 'CUBA',
        'codigo_iso' => 'CU',
    ],
    // Sin código ISO: No es un país (depósito franco). Recomendado: ninguno.
    906 => [
        'glosa' => 'DEPOSITO FRANCO',
        'codigo_iso' => null,
    ],
    507 => [
        'glosa' => 'DINAMARCA',
        'codigo_iso' => 'DK',
    ],
    155 => [
        'glosa' => 'DJIBOUTI',
        'codigo_iso' => 'DJ',
    ],
    231 => [
        'glosa' => 'DOMINICA',
        'codigo_iso' => 'DM',
    ],
    218 => [
        'glosa' => 'ECUADOR',
        'codigo_iso' => 'EC',
    ],
    124 => [
        'glosa' => 'EGIPTO',
        'codigo_iso' => 'EG',
    ],
    213 => [
        'glosa' => 'EL SALVADOR',
        'codigo_iso' => 'SV',
    ],
    341 => [
        'glosa' => 'EMIR.ARAB.UNID.',
        'codigo_iso' => 'AE',
    ],
    163 => [
        'glosa' => 'ERITREA',
        'codigo_iso' => 'ER',
    ],
    548 => [
        'glosa' => 'ESLOVENIA',
        'codigo_iso' => 'SI',
    ],
    517 => [
        'glosa' => 'ESPANA',
        'codigo_iso' => 'ES',
    ],
    549 => [
        'glosa' => 'ESTONIA',
        'codigo_iso' => 'EE',
    ],
    139 => [
        'glosa' => 'ETIOPIA',
        'codigo_iso' => 'ET',
    ],
    401 => [
        'glosa' => 'FIJI',
        'codigo_iso' => 'FJ',
    ],
    335 => [
        'glosa' => 'FILIPINAS',
        'codigo_iso' => 'PH',
    ],
    512 => [
        'glosa' => 'FINLANDIA',
        'codigo_iso' => 'FI',
    ],
    145 => [
        'glosa' => 'GABON',
        'codigo_iso' => 'GA',
    ],
    102 => [
        'glosa' => 'GAMBIA',
        'codigo_iso' => 'GM',
    ],
    550 => [
        'glosa' => 'GEORGIA',
        'codigo_iso' => 'GE',
    ],
    108 => [
        'glosa' => 'GHANA',
        'codigo_iso' => 'GH',
    ],
    565 => [
        'glosa' => 'GIBRALTAR',
        'codigo_iso' => 'GI',
    ],
    232 => [
        'glosa' => 'GRANADA',
        'codigo_iso' => 'GD',
    ],
    520 => [
        'glosa' => 'GRECIA',
        'codigo_iso' => 'GR',
    ],
    253 => [
        'glosa' => 'GROENLANDIA',
        'codigo_iso' => 'GL',
    ],
    425 => [
        'glosa' => 'GUAM',
        'codigo_iso' => 'GU',
    ],
    215 => [
        'glosa' => 'GUATEMALA',
        'codigo_iso' => 'GT',
    ],
    566 => [
        'glosa' => 'GUERNSEY',
        'codigo_iso' => 'GG',
    ],
    104 => [
        'glosa' => 'GUINEA',
        'codigo_iso' => 'GN',
    ],
    147 => [
        'glosa' => 'GUINEA ECUATRL',
        'codigo_iso' => 'GQ',
    ],
    103 => [
        'glosa' => 'GUINEA-BISSAU',
        'codigo_iso' => 'GW',
    ],
    217 => [
        'glosa' => 'GUYANA',
        'codigo_iso' => 'GY',
    ],
    208 => [
        'glosa' => 'HAITI',
        'codigo_iso' => 'HT',
    ],
    515 => [
        'glosa' => 'PAISES BAJOS',
        'codigo_iso' => 'NL',
    ],
    214 => [
        'glosa' => 'HONDURAS',
        'codigo_iso' => 'HN',
    ],
    342 => [
        'glosa' => 'HONG KONG',
        'codigo_iso' => 'HK',
    ],
    530 => [
        'glosa' => 'HUNGRIA',
        'codigo_iso' => 'HU',
    ],
    317 => [
        'glosa' => 'INDIA',
        'codigo_iso' => 'IN',
    ],
    328 => [
        'glosa' => 'INDONESIA',
        'codigo_iso' => 'ID',
    ],
    307 => [
        'glosa' => 'IRAK',
        'codigo_iso' => 'IQ',
    ],
    309 => [
        'glosa' => 'IRAN',
        'codigo_iso' => 'IR',
    ],
    506 => [
        'glosa' => 'IRLANDA',
        'codigo_iso' => 'IE',
    ],
    567 => [
        'glosa' => 'ISLA DE MAN',
        'codigo_iso' => 'IM',
    ],
    516 => [
        'glosa' => 'ISLANDIA',
        'codigo_iso' => 'IS',
    ],
    246 => [
        'glosa' => 'ISLAS CAYMAN',
        'codigo_iso' => 'KY',
    ],
    427 => [
        'glosa' => 'ISLAS COOK',
        'codigo_iso' => 'CK',
    ],
    327 => [
        'glosa' => 'ISLAS MALDIVAS',
        'codigo_iso' => 'MV',
    ],
    424 => [
        'glosa' => 'ISLAS MARIANAS DEL NORTE',
        'codigo_iso' => 'MP',
    ],
    164 => [
        'glosa' => 'ISLAS MARSHALL',
        'codigo_iso' => 'MH',
    ],
    418 => [
        'glosa' => 'ISLAS SALOMON',
        'codigo_iso' => 'SB',
    ],
    403 => [
        'glosa' => 'ISLAS TONGA',
        'codigo_iso' => 'TO',
    ],
    245 => [
        'glosa' => 'ISLAS VIRG.BRIT',
        'codigo_iso' => 'VG',
    ],
    249 => [
        'glosa' => 'ISLAS VIRGENES (ESTADOS UNIDOS',
        'codigo_iso' => 'VI',
    ],
    306 => [
        'glosa' => 'ISRAEL',
        'codigo_iso' => 'IL',
    ],
    504 => [
        'glosa' => 'ITALIA',
        'codigo_iso' => 'IT',
    ],
    205 => [
        'glosa' => 'JAMAICA',
        'codigo_iso' => 'JM',
    ],
    331 => [
        'glosa' => 'JAPON',
        'codigo_iso' => 'JP',
    ],
    568 => [
        'glosa' => 'JERSEY',
        'codigo_iso' => 'JE',
    ],
    301 => [
        'glosa' => 'JORDANIA',
        'codigo_iso' => 'JO',
    ],
    551 => [
        'glosa' => 'KASAJSTAN',
        'codigo_iso' => 'KZ',
    ],
    137 => [
        'glosa' => 'KENIA',
        'codigo_iso' => 'KE',
    ],
    552 => [
        'glosa' => 'KIRGISTAN',
        'codigo_iso' => 'KG',
    ],
    416 => [
        'glosa' => 'KIRIBATI',
        'codigo_iso' => 'KI',
    ],
    303 => [
        'glosa' => 'KUWAIT',
        'codigo_iso' => 'KW',
    ],
    316 => [
        'glosa' => 'LAOS',
        'codigo_iso' => 'LA',
    ],
    114 => [
        'glosa' => 'LESOTHO',
        'codigo_iso' => 'LS',
    ],
    553 => [
        'glosa' => 'LETONIA',
        'codigo_iso' => 'LV',
    ],
    311 => [
        'glosa' => 'LIBANO',
        'codigo_iso' => 'LB',
    ],
    106 => [
        'glosa' => 'LIBERIA',
        'codigo_iso' => 'LR',
    ],
    125 => [
        'glosa' => 'LIBIA',
        'codigo_iso' => 'LY',
    ],
    534 => [
        'glosa' => 'LIECHTENSTEIN',
        'codigo_iso' => 'LI',
    ],
    554 => [
        'glosa' => 'LITUANIA',
        'codigo_iso' => 'LT',
    ],
    532 => [
        'glosa' => 'LUXEMBURGO',
        'codigo_iso' => 'LU',
    ],
    345 => [
        'glosa' => 'MACAO',
        'codigo_iso' => 'MO',
    ],
    555 => [
        'glosa' => 'MACEDONIA',
        'codigo_iso' => 'MK',
    ],
    120 => [
        'glosa' => 'MADAGASCAR',
        'codigo_iso' => 'MG',
    ],
    329 => [
        'glosa' => 'MALASIA',
        'codigo_iso' => 'MY',
    ],
    115 => [
        'glosa' => 'MALAWI',
        'codigo_iso' => 'MW',
    ],
    133 => [
        'glosa' => 'MALI',
        'codigo_iso' => 'ML',
    ],
    523 => [
        'glosa' => 'MALTA',
        'codigo_iso' => 'MT',
    ],
    128 => [
        'glosa' => 'MARRUECOS',
        'codigo_iso' => 'MA',
    ],
    250 => [
        'glosa' => 'MARTINICA',
        'codigo_iso' => 'MQ',
    ],
    119 => [
        'glosa' => 'MAURICIO',
        'codigo_iso' => 'MU',
    ],
    134 => [
        'glosa' => 'MAURITANIA',
        'codigo_iso' => 'MR',
    ],
    216 => [
        'glosa' => 'MEXICO',
        'codigo_iso' => 'MX',
    ],
    417 => [
        'glosa' => 'MICRONESIA',
        'codigo_iso' => 'FM',
    ],
    556 => [
        'glosa' => 'MOLDOVA',
        'codigo_iso' => 'MD',
    ],
    535 => [
        'glosa' => 'MONACO',
        'codigo_iso' => 'MC',
    ],
    337 => [
        'glosa' => 'MONGOLIA',
        'codigo_iso' => 'MN',
    ],
    252 => [
        'glosa' => 'MONSERRAT',
        'codigo_iso' => 'MS',
    ],
    561 => [
        'glosa' => 'MONTENEGRO',
        'codigo_iso' => 'ME',
    ],
    121 => [
        'glosa' => 'MOZAMBIQUE',
        'codigo_iso' => 'MZ',
    ],
    326 => [
        'glosa' => 'MYANMAR (EX BIR',
        'codigo_iso' => 'MM',
    ],
    // Sin código ISO: No es un país (nacionalidad reputada). Recomendado:
    // ninguno.
    998 => [
        'glosa' => 'NAC.REPUTADA',
        'codigo_iso' => null,
    ],
    159 => [
        'glosa' => 'NAMIBIA',
        'codigo_iso' => 'NA',
    ],
    402 => [
        'glosa' => 'NAURU',
        'codigo_iso' => 'NR',
    ],
    320 => [
        'glosa' => 'NEPAL',
        'codigo_iso' => 'NP',
    ],
    212 => [
        'glosa' => 'NICARAGUA',
        'codigo_iso' => 'NI',
    ],
    131 => [
        'glosa' => 'NIGER',
        'codigo_iso' => 'NE',
    ],
    111 => [
        'glosa' => 'NIGERIA',
        'codigo_iso' => 'NG',
    ],
    421 => [
        'glosa' => 'NIUE',
        'codigo_iso' => 'NU',
    ],
    513 => [
        'glosa' => 'NORUEGA',
        'codigo_iso' => 'NO',
    ],
    423 => [
        'glosa' => 'NUEVA CALEDONIA',
        'codigo_iso' => 'NC',
    ],
    405 => [
        'glosa' => 'NUEVA ZELANDIA',
        'codigo_iso' => 'NZ',
    ],
    304 => [
        'glosa' => 'OMAN',
        'codigo_iso' => 'OM',
    ],
    // Sin código ISO: No es un país (código especial de aduana). Recomendado:
    // ninguno.
    904 => [
        'glosa' => 'ORIG.O DEST. NO',
        'codigo_iso' => null,
    ],
    324 => [
        'glosa' => 'PAKISTAN',
        'codigo_iso' => 'PK',
    ],
    420 => [
        'glosa' => 'BELAU',
        'codigo_iso' => 'PW',
    ],
    210 => [
        'glosa' => 'PANAMA',
        'codigo_iso' => 'PA',
    ],
    222 => [
        'glosa' => 'PARAGUAY',
        'codigo_iso' => 'PY',
    ],
    219 => [
        'glosa' => 'PERU',
        'codigo_iso' => 'PE',
    ],
    // Sin código ISO: No es un país (código especial de aduana). Recomendado:
    // ninguno.
    903 => [
        'glosa' => 'PESCA EXTRA',
        'codigo_iso' => null,
    ],
    422 => [
        'glosa' => 'POLINESIA FRANCESA',
        'codigo_iso' => 'PF',
    ],
    528 => [
        'glosa' => 'POLONIA',
        'codigo_iso' => 'PL',
    ],
    501 => [
        'glosa' => 'PORTUGAL',
        'codigo_iso' => 'PT',
    ],
    412 => [
        'glosa' => 'PPUA.NVA.GUINEA',
        'codigo_iso' => 'PG',
    ],
    251 => [
        'glosa' => 'PUERTO RICO',
        'codigo_iso' => 'PR',
    ],
    312 => [
        'glosa' => 'QATAR',
        'codigo_iso' => 'QA',
    ],
    // Sin código ISO: No es un país (código especial de aduana). Recomendado:
    // ninguno.
    902 => [
        'glosa' => 'RANCHO DE NAVES',
        'codigo_iso' => null,
    ],
    148 => [
        'glosa' => 'REP.CENT.AFRIC.',
        'codigo_iso' => 'CF',
    ],
    143 => [
        'glosa' => 'REP.DEM. CONGO',
        'codigo_iso' => 'CD',
    ],
    206 => [
        'glosa' => 'REP.DOMINICANA',
        'codigo_iso' => 'DO',
    ],
    545 => [
        'glosa' => 'REP.ESLOVACA',
        'codigo_iso' => 'SK',
    ],
    544 => [
        'glosa' => 'REPUBLICA CHECA',
        'codigo_iso' => 'CZ',
    ],
    546 => [
        'glosa' => 'REPUBLICA DE SERBIA',
        'codigo_iso' => 'RS',
    ],
    346 => [
        'glosa' => 'REPUBLICA DE YEMEN',
        'codigo_iso' => 'YE',
    ],
    519 => [
        'glosa' => 'RUMANIA',
        'codigo_iso' => 'RO',
    ],
    562 => [
        'glosa' => 'RUSIA',
        'codigo_iso' => 'RU',
    ],
    142 => [
        'glosa' => 'RWANDA',
        'codigo_iso' => 'RW',
    ],
    146 => [
        'glosa' => 'S.TOM.PRINCIPE',
        'codigo_iso' => 'ST',
    ],
    234 => [
        'glosa' => 'S.VTE.Y GRANAD.',
        'codigo_iso' => 'VC',
    ],
    165 => [
        'glosa' => 'SAHARAUI',
        'codigo_iso' => 'EH',
    ],
    404 => [
        'glosa' => 'SAMOA OCC.',
        'codigo_iso' => 'WS',
    ],
    536 => [
        'glosa' => 'SAN MARINO',
        'codigo_iso' => 'SM',
    ],
    233 => [
        'glosa' => 'SANTA LUCIA(ISL',
        'codigo_iso' => 'LC',
    ],
    524 => [
        'glosa' => 'SANTA SEDE',
        'codigo_iso' => 'VA',
    ],
    101 => [
        'glosa' => 'SENEGAL',
        'codigo_iso' => 'SN',
    ],
    156 => [
        'glosa' => 'SEYCHELLES',
        'codigo_iso' => 'SC',
    ],
    105 => [
        'glosa' => 'SIERRA LEONA',
        'codigo_iso' => 'SL',
    ],
    332 => [
        'glosa' => 'SINGAPUR',
        'codigo_iso' => 'SG',
    ],
    310 => [
        'glosa' => 'SIRIA',
        'codigo_iso' => 'SY',
    ],
    241 => [
        'glosa' => 'SNT.KIT & NEVIS',
        'codigo_iso' => 'KN',
    ],
    138 => [
        'glosa' => 'SOMALIA',
        'codigo_iso' => 'SO',
    ],
    314 => [
        'glosa' => 'SRI LANKA',
        'codigo_iso' => 'LK',
    ],
    112 => [
        'glosa' => 'SUDAFRICA',
        'codigo_iso' => 'ZA',
    ],
    123 => [
        'glosa' => 'SUDAN',
        'codigo_iso' => 'SD',
    ],
    160 => [
        'glosa' => 'SUDAN DEL SUR',
        'codigo_iso' => 'SS',
    ],
    511 => [
        'glosa' => 'SUECIA',
        'codigo_iso' => 'SE',
    ],
    508 => [
        'glosa' => 'SUIZA',
        'codigo_iso' => 'CH',
    ],
    235 => [
        'glosa' => 'SURINAM',
        'codigo_iso' => 'SR',
    ],
    122 => [
        'glosa' => 'SWAZILANDIA',
        'codigo_iso' => 'SZ',
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    409 => [
        'glosa' => 'T.NORTEAM.EN OP',
        'codigo_iso' => null,
    ],
    557 => [
        'glosa' => 'TADJIKISTAN',
        'codigo_iso' => 'TJ',
    ],
    330 => [
        'glosa' => 'TAIWAN (FORMOSA',
        'codigo_iso' => 'TW',
    ],
    135 => [
        'glosa' => 'TANZANIA',
        'codigo_iso' => 'TZ',
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    152 => [
        'glosa' => 'TER.ESPAN.EN AF',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    229 => [
        'glosa' => 'TER.HOLAN.EN AM',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    343 => [
        'glosa' => 'TER.PORTUG.E/AS',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    151 => [
        'glosa' => 'TERR.BRIT.EN AF',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    227 => [
        'glosa' => 'TERR.BRIT.EN AM',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    407 => [
        'glosa' => 'TERR.BRIT.EN OP',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    230 => [
        'glosa' => 'TERR.D/DINAMARC',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    153 => [
        'glosa' => 'TERR.FRAN.EN AF',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    228 => [
        'glosa' => 'TERR.FRAN.EN AM',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Agrupación de territorios, no un país. Recomendado:
    // ninguno.
    408 => [
        'glosa' => 'TERR.FRAN.EN OP',
        'codigo_iso' => null,
    ],
    319 => [
        'glosa' => 'THAILANDIA',
        'codigo_iso' => 'TH',
    ],
    426 => [
        'glosa' => 'TIMOR ORIENTAL',
        'codigo_iso' => 'TL',
    ],
    109 => [
        'glosa' => 'TOGO',
        'codigo_iso' => 'TG',
    ],
    203 => [
        'glosa' => 'TRINID.Y TOBAGO',
        'codigo_iso' => 'TT',
    ],
    126 => [
        'glosa' => 'TUNEZ',
        'codigo_iso' => 'TN',
    ],
    248 => [
        'glosa' => 'TURCAS Y CAICOS',
        'codigo_iso' => 'TC',
    ],
    558 => [
        'glosa' => 'TURKMENISTAN',
        'codigo_iso' => 'TM',
    ],
    522 => [
        'glosa' => 'TURQUIA',
        'codigo_iso' => 'TR',
    ],
    419 => [
        'glosa' => 'TUVALU',
        'codigo_iso' => 'TV',
    ],
    559 => [
        'glosa' => 'UCRANIA',
        'codigo_iso' => 'UA',
    ],
    136 => [
        'glosa' => 'UGANDA',
        'codigo_iso' => 'UG',
    ],
    223 => [
        'glosa' => 'URUGUAY',
        'codigo_iso' => 'UY',
    ],
    560 => [
        'glosa' => 'UZBEKISTAN',
        'codigo_iso' => 'UZ',
    ],
    415 => [
        'glosa' => 'VANUATU',
        'codigo_iso' => 'VU',
    ],
    201 => [
        'glosa' => 'VENEZUELA',
        'codigo_iso' => 'VE',
    ],
    325 => [
        'glosa' => 'VIETNAM',
        'codigo_iso' => 'VN',
    ],
    322 => [
        'glosa' => 'PALESTINA',
        'codigo_iso' => 'PS',
    ],
    117 => [
        'glosa' => 'ZAMBIA',
        'codigo_iso' => 'ZM',
    ],
    // Sin código ISO: Zona franca, no un país. Recomendado: ninguno.
    910 => [
        'glosa' => 'ZF.ARICA-ZF IND',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Zona franca, no un país. Recomendado: ninguno.
    905 => [
        'glosa' => 'ZF.IQUIQUE',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Zona franca, no un país. Recomendado: ninguno.
    907 => [
        'glosa' => 'ZF.PARENAS',
        'codigo_iso' => null,
    ],
    116 => [
        'glosa' => 'ZIMBABWE',
        'codigo_iso' => 'ZW',
    ],
    // Sin código ISO: No es un país (otros). Recomendado: ninguno.
    999 => [
        'glosa' => 'OTROS(PAIS DESC',
        'codigo_iso' => null,
    ],
    // Estos países ya no existen en los listados oficiales, se dejan por si
    // existiesen documentos antiguos que los usen.
    // Sin código ISO: Alemania Oriental dejó de existir en 1990. Recomendado:
    // 563 ALEMANIA.
    503 => [
        'glosa' => 'ALEMANIA R.D.(N',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Alemania Occidental dejó de existir como país separado en
    // 1990. Recomendado: 563 ALEMANIA.
    502 => [
        'glosa' => 'ALEMANIA R.F.',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Hoy es Burkina Faso. Recomendado: 161 BURKINA FASO.
    132 => [
        'glosa' => 'ALTO VOLTA',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Bantustán de Sudáfrica que dejó de existir en 1994.
    // Recomendado: 112 SUDAFRICA.
    154 => [
        'glosa' => 'BOPHUTHATSWANA',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Se dividió en 1993. Recomendado: 544 REPUBLICA CHECA o
    // 545 REP.ESLOVACA, según corresponda.
    529 => [
        'glosa' => 'CHECOESLOVAQUIA',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Bantustán de Sudáfrica que dejó de existir en 1994.
    // Recomendado: 112 SUDAFRICA.
    162 => [
        'glosa' => 'CISKEY',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Error de tipeo de 565 GIBRALTAR (el SII ya no lo tiene).
    // Recomendado: 565 GIBRALTAR.
    585 => [
        'glosa' => 'GILBRALTAR',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Dejó de existir. Recomendado: el país sucesor que
    // corresponda (por ejemplo, 546 REPUBLICA DE SERBIA).
    564 => [
        'glosa' => 'RF YUGOSLAVIA',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Bantustán de Sudáfrica que dejó de existir en 1994.
    // Recomendado: 112 SUDAFRICA.
    166 => [
        'glosa' => 'TRANSKEI',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Dejó de existir en 1991. Recomendado: el país sucesor que
    // corresponda (por ejemplo, 562 RUSIA o 559 UCRANIA).
    521 => [
        'glosa' => 'U.R.S.S.   (NO',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Yemen se unificó en 1990. Recomendado: 346 REPUBLICA DE
    // YEMEN.
    323 => [
        'glosa' => 'YEMEN DEL SUR',
        'codigo_iso' => null,
    ],
    // Sin código ISO: Dejó de existir. Recomendado: el país sucesor que
    // corresponda (por ejemplo, 546 REPUBLICA DE SERBIA).
    526 => [
        'glosa' => 'YUGOESLAVIA (NO',
        'codigo_iso' => null,
    ],
];

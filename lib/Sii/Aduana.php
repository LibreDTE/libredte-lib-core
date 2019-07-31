<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace sasco\LibreDTE\Sii;

/**
 * Clase para trabajar con las tablas de la Aduana
 * Fuentes:
 *  - http://comext.aduana.cl:7001/codigos
 *  - https://www.aduana.cl/compendio-de-normas-anexo-51/aduana/2008-02-18/165942.html
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2019-07-25
 */
class Aduana
{

    private static $tablas = [
        'FmaPagExp' => [
            'glosa' => 'Forma pago exp.',
            'valor' => [
                1 => 'COB1',
                11 => 'ACRED',
                12 => 'CBOF',
                2 => 'COBRANZA',
                21 => 'S/PAGO',
                32 => 'ANTICIPO',
                50 => 'ANT/COB',
                60 => 'ANT/CRED',
                80 => 'S/PAGO/COB',
            ],
        ],
        'CodModVenta' => [
            'glosa' => 'Mod. venta',
            'valor' => [
                1 => 'A firme',
                2 => 'Bajo condición',
                3 => 'En consignación libre',
                4 => 'En consignación con un mínimo a firme',
                9 => 'Sin pago',
            ],
        ],
        'CodClauVenta' => [
            'glosa' => 'Claú. venta',
            'valor' => [
                1 => 'CIF',
                2 => 'CFR',
                3 => 'EXW',
                4 => 'FAS',
                5 => 'FOB',
                6 => 'S/CL',
                8 => 'OTROS',
                9 => 'DDP',
                10 => 'FCA',
                11 => 'CPT',
                12 => 'CIP',
                17 => 'DAT',
                18 => 'DAP',
            ],
        ],
        'TotClauVenta' => 'Total claú.',
        'CodViaTransp' => [
            'glosa' => 'Transporte',
            'valor' => [
                1 =>'Marítima, fluvial y lacustre',
                4 => 'Aéreo',
                5 => 'Postal',
                6 => 'Ferroviario',
                7 => 'Carretero/terrestre',
                8 => 'Óleoductos, gasoductos',
                9 => 'Tendido eléctrico (aéreo,subt)',
                10 => 'Otra',
            ],
        ],
        'NombreTransp' => 'Nomb. trans.',
        'RUTCiaTransp' => 'RUT trans.',
        'NomCiaTransp' => 'Comp. trans.',
        'IdAdicTransp' => 'Trans. Ad.',
        'Booking' => 'Booking',
        'Operador' => 'Operador',
        'CodPtoEmbarque' => [
            'glosa' => 'Embarque',
            'tabla' => 'puertos',
        ],
        'IdAdicPtoEmb' => 'Embarq. Ad.',
        'CodPtoDesemb' => [
            'glosa' => 'Desembarq.',
            'tabla' => 'puertos',
        ],
        'IdAdicPtoDesemb' => 'Desemb. Ad.',
        'Tara' => 'Tara',
        'CodUnidMedTara' => [
            'glosa' => 'U. tara',
            'tabla' => 'unidades',
        ],
        'PesoBruto' => 'Peso bruto',
        'CodUnidPesoBruto' => [
            'glosa' => 'U. p. bruto',
            'tabla' => 'unidades',
        ],
        'PesoNeto' => 'Peso neto',
        'CodUnidPesoNeto' => [
            'glosa' => 'U. p. neto',
            'tabla' => 'unidades',
        ],
        'TotItems' => 'Items',
        'TotBultos' => 'Total bultos',
        'TipoBultos' => [
            'glosa' => 'Bultos',
            'valor' => [
                1 => 'POLVO',
                2 => 'GRANOS',
                3 => 'NODULOS',
                4 => 'LIQUIDO',
                5 => 'GAS',
                10 => 'PIEZA',
                11 => 'TUBO',
                12 => 'CILINDRO',
                13 => 'ROLLOS',
                16 => 'BARRA',
                17 => 'LINGOTE',
                18 => 'TRONCOS',
                19 => 'BLOQUE',
                20 => 'ROLLIZO',
                21 => 'CAJON',
                22 => 'CAJA DE CARTON',
                23 => 'FARDO',
                24 => 'BAUL',
                25 => 'COFRE',
                26 => 'ARMAZON',
                27 => 'BANDEJA',
                28 => 'CAJAMADERA',
                29 => 'CAJALATA',
                31 => 'BOTELLAGAS',
                32 => 'BOTELLA',
                33 => 'JAULA',
                34 => 'BIDON',
                35 => 'JABA',
                36 => 'CESTA',
                37 => 'BARRILETE',
                38 => 'TONEL',
                39 => 'PIPA',
                40 => 'CAJANOESP',
                41 => 'JARRO',
                42 => 'FRASCO',
                43 => 'DAMAJUANA',
                44 => 'BARRIL',
                45 => 'TAMBOR',
                46 => 'CUNETE',
                47 => 'TARRO',
                51 => 'CUBO',
                61 => 'PAQUETE',
                62 => 'SACO',
                63 => 'MALETA',
                64 => 'BOLSA',
                65 => 'BALA',
                66 => 'RED',
                67 => 'SOBRE',
                73 => 'CONT20',
                74 => 'CONT40',
                75 => 'CONTENEDOR REFRIGERADO', // REEFER20
                76 => 'REEFER40',
                77 => 'ESTANQUE',
                78 => 'CONTNOESP',
                80 => 'PALLETS',
                81 => 'TABLERO',
                82 => 'LAMINA',
                83 => 'CARRETE',
                85 => 'AUTOMOTOR',
                86 => 'ATAUD',
                88 => 'MAQUINARIA',
                89 => 'PLANCHAS',
                90 => 'ATADO',
                91 => 'BOBINA',
                93 => 'BULTONOESP',
                98 => 'SIN BULTO',
                99 => 'S/EMBALAR',
            ],
        ],
        'MntFlete' => 'Flete',
        'MntSeguro' => 'Seguro',
        'CodPaisRecep' => [
            'glosa' => 'P. receptor',
            'tabla' => 'paises',
        ],
        'CodPaisDestin' => [
            'glosa' => 'P. destino',
            'tabla' => 'paises',
        ],
        'unidades' => [
            1 => 'TMB',
            2 => 'QMB',
            3 => 'MKWH',
            4 => 'TMN',
            5 => 'KLT',
            6 => 'KN',
            7 => 'GN',
            8 => 'HL',
            9 => 'LT',
            10 => 'U',
            11 => 'DOC',
            12 => 'U(JGO)',
            13 => 'MU',
            14 => 'MT',
            15 => 'MT2',
            16 => 'MCUB',
            17 => 'PAR',
            18 => 'KNFC',
            19 => 'CARTON',
            20 => 'KWH',
            23 => 'BAR',
            24 => 'M2/1MM',
            99 => 'S.U.M',
        ],
        'paises' => [
            // paises más comunes
            563 => 'ALEMANIA',
            224 => 'ARGENTINA',
            406 => 'AUSTRALIA',
            220 => 'BRASIL',
            226 => 'CANADA',
            505 => 'FRANCIA',
            510 => 'REINO UNIDO',
            225 => 'U.S.A.',
            997 => 'CHILE',
            // paises ordenados por alfabeto
            308 => 'AFGANISTAN',
            518 => 'ALBANIA',
            503 => 'ALEMANIA R.D.(N',
            502 => 'ALEMANIA R.F.',
            132 => 'ALTO VOLTA',
            525 => 'ANDORRA',
            140 => 'ANGOLA',
            242 => 'ANGUILA',
            240 => 'ANTIGUA Y BBUDA',
            247 => 'ANTILLAS NEERLANDESA',
            302 => 'ARABIA SAUDITA',
            127 => 'ARGELIA',
            540 => 'ARMENIA',
            243 => 'ARUBA',
            509 => 'AUSTRIA',
            541 => 'AZERBAIJAN',
            207 => 'BAHAMAS',
            313 => 'BAHREIN',
            321 => 'BANGLADESH',
            204 => 'BARBADOS',
            542 => 'BELARUS',
            514 => 'BELGICA',
            236 => 'BELICE',
            150 => 'BENIN',
            244 => 'BERMUDAS',
            318 => 'BHUTAN',
            221 => 'BOLIVIA',
            154 => 'BOPHUTHATSWANA',
            543 => 'BOSNIA HEZGVINA',
            113 => 'BOTSWANA',
            344 => 'BRUNEI',
            527 => 'BULGARIA',
            161 => 'BURKINA FASO',
            141 => 'BURUNDI',
            129 => 'CABO VERDE',
            315 => 'CAMBODIA',
            149 => 'CAMERUN',
            130 => 'CHAD',
            529 => 'CHECOESLOVAQUIA',
            336 => 'CHINA',
            305 => 'CHIPRE',
            162 => 'CISKEY',
            202 => 'COLOMBIA',
            901 => 'COMB.Y LUBRIC.',
            118 => 'COMORAS',
            144 => 'CONGO',
            334 => 'COREA DEL NORTE',
            333 => 'COREA DEL SUR',
            107 => 'COSTA DE MARFIL',
            211 => 'COSTA RICA',
            547 => 'CROACIA',
            209 => 'CUBA',
            906 => 'DEPOSITO FRANCO',
            507 => 'DINAMARCA',
            155 => 'DJIBOUTI',
            231 => 'DOMINICA',
            218 => 'ECUADOR',
            124 => 'EGIPTO',
            213 => 'EL SALVADOR',
            341 => 'EMIR.ARAB.UNID.',
            163 => 'ERITREA',
            548 => 'ESLOVENIA',
            517 => 'ESPANA',
            549 => 'ESTONIA',
            139 => 'ETIOPIA',
            401 => 'FIJI',
            335 => 'FILIPINAS',
            512 => 'FINLANDIA',
            145 => 'GABON',
            102 => 'GAMBIA',
            550 => 'GEORGIA',
            108 => 'GHANA',
            565 => 'GIBRALTAR',
            585 => 'GILBRALTAR',
            232 => 'GRANADA',
            520 => 'GRECIA',
            253 => 'GROENLANDIA',
            425 => 'GUAM',
            215 => 'GUATEMALA',
            566 => 'GUERNSEY',
            104 => 'GUINEA',
            147 => 'GUINEA ECUATRL',
            103 => 'GUINEA-BISSAU',
            217 => 'GUYANA',
            208 => 'HAITI',
            515 => 'HOLANDA',
            214 => 'HONDURAS',
            342 => 'HONG KONG',
            530 => 'HUNGRIA',
            317 => 'INDIA',
            328 => 'INDONESIA',
            307 => 'IRAK',
            309 => 'IRAN',
            506 => 'IRLANDA',
            567 => 'ISLA DE MAN',
            516 => 'ISLANDIA',
            246 => 'ISLAS CAYMAN',
            427 => 'ISLAS COOK',
            327 => 'ISLAS MALDIVAS',
            424 => 'ISLAS MARIANAS DEL NORTE',
            164 => 'ISLAS MARSHALL',
            418 => 'ISLAS SALOMON',
            403 => 'ISLAS TONGA',
            245 => 'ISLAS VIRG.BRIT',
            249 => 'ISLAS VIRGENES (ESTADOS UNIDOS',
            306 => 'ISRAEL',
            504 => 'ITALIA',
            205 => 'JAMAICA',
            331 => 'JAPON',
            568 => 'JERSEY',
            301 => 'JORDANIA',
            551 => 'KASAJSTAN',
            137 => 'KENIA',
            552 => 'KIRGISTAN',
            416 => 'KIRIBATI',
            303 => 'KUWAIT',
            316 => 'LAOS',
            114 => 'LESOTHO',
            553 => 'LETONIA',
            311 => 'LIBANO',
            106 => 'LIBERIA',
            125 => 'LIBIA',
            534 => 'LIECHTENSTEIN',
            554 => 'LITUANIA',
            532 => 'LUXEMBURGO',
            345 => 'MACAO',
            555 => 'MACEDONIA',
            120 => 'MADAGASCAR',
            329 => 'MALASIA',
            115 => 'MALAWI',
            133 => 'MALI',
            523 => 'MALTA',
            128 => 'MARRUECOS',
            250 => 'MARTINICA',
            119 => 'MAURICIO',
            134 => 'MAURITANIA',
            216 => 'MEXICO',
            417 => 'MICRONESIA',
            556 => 'MOLDOVA',
            535 => 'MONACO',
            337 => 'MONGOLIA',
            252 => 'MONSERRAT',
            561 => 'MONTENEGRO',
            121 => 'MOZAMBIQUE',
            326 => 'MYANMAR (EX BIR',
            998 => 'NAC.REPUTADA',
            159 => 'NAMIBIA',
            402 => 'NAURU',
            320 => 'NEPAL',
            212 => 'NICARAGUA',
            131 => 'NIGER',
            111 => 'NIGERIA',
            421 => 'NIUE',
            513 => 'NORUEGA',
            423 => 'NUEVA CALEDONIA',
            405 => 'NUEVA ZELANDIA',
            304 => 'OMAN',
            904 => 'ORIG.O DEST. NO',
            324 => 'PAKISTAN',
            420 => 'PALAU',
            210 => 'PANAMA',
            222 => 'PARAGUAY',
            219 => 'PERU',
            903 => 'PESCA EXTRA',
            422 => 'POLINESIA FRANCESA',
            528 => 'POLONIA',
            501 => 'PORTUGAL',
            412 => 'PPUA.NVA.GUINEA',
            251 => 'PUERTO RICO',
            312 => 'QATAR',
            902 => 'RANCHO DE NAVES',
            148 => 'REP.CENT.AFRIC.',
            143 => 'REP.DEM. CONGO',
            206 => 'REP.DOMINICANA',
            545 => 'REP.ESLOVACA',
            544 => 'REPUBLICA CHECA',
            546 => 'REPUBLICA DE SERBIA',
            346 => 'REPUBLICA DE YEMEN',
            564 => 'RF YUGOSLAVIA',
            519 => 'RUMANIA',
            562 => 'RUSIA',
            142 => 'RWANDA',
            146 => 'S.TOM.PRINCIPE',
            234 => 'S.VTE.Y GRANAD.',
            165 => 'SAHARAUI',
            404 => 'SAMOA OCC.',
            536 => 'SAN MARINO',
            233 => 'SANTA LUCIA(ISL',
            524 => 'SANTA SEDE',
            101 => 'SENEGAL',
            156 => 'SEYCHELLES',
            105 => 'SIERRA LEONA',
            332 => 'SINGAPUR',
            310 => 'SIRIA',
            241 => 'SNT.KIT & NEVIS',
            138 => 'SOMALIA',
            314 => 'SRI LANKA',
            112 => 'SUDAFRICA',
            123 => 'SUDAN',
            160 => 'SUDAN DEL SUR',
            511 => 'SUECIA',
            508 => 'SUIZA',
            235 => 'SURINAM',
            122 => 'SWAZILANDIA',
            409 => 'T.NORTEAM.EN AU',
            557 => 'TADJIKISTAN',
            330 => 'TAIWAN (FORMOSA',
            135 => 'TANZANIA',
            152 => 'TER.ESPAN.EN AF',
            229 => 'TER.HOLAN.EN AM',
            343 => 'TER.PORTUG.E/AS',
            151 => 'TERR.BRIT.EN AF',
            227 => 'TERR.BRIT.EN AM',
            407 => 'TERR.BRIT.EN AU',
            230 => 'TERR.D/DINAMARC',
            153 => 'TERR.FRAN.EN AF',
            228 => 'TERR.FRAN.EN AM',
            408 => 'TERR.FRAN.EN AU',
            319 => 'THAILANDIA',
            426 => 'TIMOR ORIENTAL',
            109 => 'TOGO',
            166 => 'TRANSKEI',
            203 => 'TRINID.Y TOBAGO',
            126 => 'TUNEZ',
            248 => 'TURCAS Y CAICOS',
            558 => 'TURKMENISTAN',
            522 => 'TURQUIA',
            419 => 'TUVALU',
            521 => 'U.R.S.S.   (NO',
            559 => 'UCRANIA',
            136 => 'UGANDA',
            223 => 'URUGUAY',
            560 => 'UZBEKISTAN',
            415 => 'VANUATU',
            201 => 'VENEZUELA',
            158 => 'VIENDA',
            325 => 'VIETNAM',
            322 => 'YEMEN',
            323 => 'YEMEN DEL SUR',
            526 => 'YUGOESLAVIA (NO',
            117 => 'ZAMBIA',
            910 => 'ZF.ARICA-ZF IND',
            905 => 'ZF.IQUIQUE',
            907 => 'ZF.PARENAS',
            116 => 'ZIMBABWE',
            999 => 'OTROS(PAIS DESC',
        ],
        'puertos' => [
            111 => 'MONTREAL',
            112 => 'COSTA DEL PACIFICO, OTROS NO E',
            113 => 'HALIFAX',
            114 => 'VANCOUVER',
            115 => 'SAINT JOHN',
            116 => 'TORONTO',
            117 => 'OTROS PUERTOS DE CANADA NO IDE',
            118 => 'BAYSIDE',
            120 => 'PORT CARTIES',
            121 => 'COSTA DEL ATLANTICO, OTROS NO',
            122 => 'PUERTOS DEL GOLFO DE MEXICO, O',
            123 => 'COSTA DEL PACIFICO, OTROS NO E',
            124 => 'QUEBEC',
            125 => 'PRINCE RUPERT',
            126 => 'HAMILTON',
            131 => 'BOSTON',
            132 => 'NEW HAVEN',
            133 => 'BRIDGEPORT',
            134 => 'NEW YORK',
            135 => 'FILADELFIA',
            136 => 'BALTIMORE',
            137 => 'NORFOLK',
            138 => 'WILMINGTON',
            139 => 'CHARLESTON',
            140 => 'SAVANAH',
            141 => 'MIAMI',
            142 => 'EVERGLADES',
            143 => 'JACKSONVILLE',
            145 => 'PALM BEACH',
            146 => 'BATON ROUGE',
            147 => 'COLUMBRES',
            148 => 'PITTSBURGH',
            149 => 'DULUTH',
            150 => 'MILWAUKEE',
            151 => 'TAMPA',
            152 => 'PENSACOLA',
            153 => 'MOBILE',
            154 => 'NEW ORLEANS',
            155 => 'PORT ARTHUR',
            156 => 'GALVESTON',
            157 => 'CORPUS CRISTI',
            158 => 'BROWSVILLE',
            159 => 'HOUSTON',
            160 => 'OAKLAND',
            161 => 'STOCKTON',
            171 => 'SEATLE',
            172 => 'PORTLAND',
            173 => 'SAN FRANCISCO',
            174 => 'LOS ANGELES',
            175 => 'LONG BEACH',
            176 => 'SAN DIEGO',
            180 => 'OTROS PUERTOS DE ESTADOS UNIDO',
            199 => 'LOS VILOS',
            201 => 'PUCHOCO',
            202 => 'OXIQUIM',
            203 => 'T. GASERO ABASTIBLE',
            204 => 'PATACHE',
            205 => 'CALBUCO',
            206 => 'MICHILLA',
            207 => 'PUERTO ANGAMOS',
            208 => 'POSEIDON',
            209 => 'TRES PUENTES',
            210 => 'OTROS PUERTOS DE MEXICO NO ESP',
            211 => 'TAMPICO',
            212 => 'COSTA DEL PACIFICO, OTROS PUER',
            213 => 'VERACRUZ',
            214 => 'COATZACOALCOS',
            215 => 'GUAYMAS',
            216 => 'MAZATLAN',
            217 => 'MANZANILLO',
            218 => 'ACAPULCO',
            219 => 'GOLFO DE MEXICO, OTROS NO ESPE',
            220 => 'ALTAMIRA',
            221 => 'CRISTOBAL',
            222 => 'BALBOA',
            223 => 'COLON',
            224 => 'OTROS PUERTOS DE PANAMA NO ESP',
            231 => 'OTROS PUERTOS DE COLOMBIA NO E',
            232 => 'BUENAVENTURA',
            233 => 'BARRANQUILLA',
            241 => 'OTROS PUERTOS DE ECUADOR NO ES',
            242 => 'GUAYAQUIL',
            251 => 'OTROS PUERTOS DE PERU NO ESPEC',
            252 => 'CALLAO',
            253 => 'ILO',
            254 => 'IQUITOS',
            261 => 'OTROS PUERTOS DE ARGENTINA NO',
            262 => 'BUENOS AIRES',
            263 => 'NECOCHEA',
            264 => 'MENDOZA',
            265 => 'CORDOBA',
            266 => 'BAHIA BLANCA',
            267 => 'COMODORO RIVADAVIA',
            268 => 'PUERTO MADRYN',
            269 => 'MAR DEL PLATA',
            270 => 'ROSARIO',
            271 => 'OTROS PUERTOS DE URUGUAY NO ES',
            272 => 'MONTEVIDEO',
            281 => 'OTROS PUERTOS DE VENEZUELA NO',
            282 => 'LA GUAIRA',
            285 => 'MARACAIBO',
            291 => 'OTROS PUERTOS DE BRASIL NO ESP',
            292 => 'SANTOS',
            293 => 'RIO JANEIRO',
            294 => 'RIO GRANDE DEL SUR',
            295 => 'PARANAGUA',
            296 => 'SAO PAULO',
            297 => 'SALVADOR',
            301 => 'OTROS PUERTOS DE LAS ANTILLAS',
            302 => 'CURAZAO',
            399 => 'OTROS PUERTOS DE AMERICA NO ES',
            411 => 'SHANGAI',
            412 => 'DAIREN',
            413 => 'OTROS PUERTOS DE CHINA NO ESPE',
            421 => 'NANPO',
            422 => 'BUSAN CY (PUSAN)',
            423 => 'OTROS PUERTOS DE COREA',
            431 => 'MANILA',
            432 => 'OTROS PUERTOS DE FILIPINAS NO',
            441 => 'OTROS PUERTOS DE JAPON NO ESPE',
            442 => 'OSAKA',
            443 => 'KOBE',
            444 => 'YOKOHAMA',
            445 => 'NAGOYA',
            446 => 'SHIMIZUI',
            447 => 'MOJI',
            448 => 'YAWATA',
            449 => 'FUKUYAMA',
            451 => 'KAOHSIUNG',
            452 => 'KEELUNG',
            453 => 'OTROS PUERTOS DE TAIWAN NO ESP',
            461 => 'KARHG ISLAND',
            462 => 'OTROS PUERTOS DE IRAN NO ESPEC',
            471 => 'CALCUTA',
            472 => 'OTROS PUERTOS DE INDIA NO E',
            481 => 'CHALNA',
            482 => 'OTROS PUERTOS DE BANGLADESH NO',
            491 => 'OTROS PUERTOS DE SINGAPURE NO',
            492 => 'HONG KONG',
            499 => 'OTROS PUERTOS ASIATICOS NO ESP',
            511 => 'CONSTANZA',
            512 => 'OTROS PUERTOS DE RUMANIA NO ES',
            521 => 'VARNA',
            522 => 'OTROS PUERTOS DE BULGARIA NO E',
            531 => 'RIJEKA',
            532 => 'OTROS PUERTOS DE YUGOESLAVIA N',
            533 => 'BELGRADO',
            534 => 'OTROS PUERTOS DE SER',
            535 => 'PODGORITSA',
            536 => 'OTROS PUERTOS DE MON',
            537 => 'OTROS PUERTOS DE CRO',
            538 => 'RIJEKA',
            541 => 'OTROS PUERTOS DE ITALIA NO ESP',
            542 => 'GENOVA',
            543 => 'LIORNA, LIVORNO',
            544 => 'NAPOLES',
            545 => 'SALERNO',
            546 => 'AUGUSTA',
            547 => 'SAVONA',
            551 => 'OTROS PUERTOS DE FRANCIA NO ES',
            552 => 'LA PALLICE',
            553 => 'LE HAVRE',
            554 => 'MARSELLA',
            555 => 'BURDEOS',
            556 => 'CALAIS',
            557 => 'BREST',
            558 => 'RUAN',
            561 => 'OTROS PUERTOS DE ESPANA NO ESP',
            562 => 'CADIZ',
            563 => 'BARCELONA',
            564 => 'BILBAO',
            565 => 'HUELVA',
            566 => 'SEVILLA',
            567 => 'TARRAGONA',
            571 => 'LIVERPOOL',
            572 => 'LONDRES',
            573 => 'ROCHESTER',
            574 => 'ETEN SALVERRY',
            576 => 'OTROS PUERTOS DE INGLATERRA NO',
            577 => 'DOVER',
            578 => 'PLYMOUTH',
            581 => 'HELSINSKI',
            582 => 'OTROS PUERTOS DE FINLANDIA NO',
            583 => 'HANKO',
            584 => 'KEMI',
            585 => 'KOKKOLA',
            586 => 'KOTKA',
            587 => 'OULO',
            588 => 'PIETARSAARI',
            589 => 'PORI',
            591 => 'BREMEN',
            592 => 'HAMBURGO',
            593 => 'NUREMBERG',
            594 => 'FRANKFURT',
            595 => 'DUSSELDORF',
            596 => 'OTROS PUERTOS DE ALEMANIA NO E',
            597 => 'CUXHAVEN',
            598 => 'ROSTOCK',
            599 => 'OLDENBURG',
            601 => 'AMBERES',
            602 => 'OTROS PUERTOS DE BELGICA NO ES',
            603 => 'ZEEBRUGGE',
            604 => 'GHENT',
            605 => 'OOSTENDE',
            611 => 'LISBOA',
            612 => 'OTROS PUERTOS DE PORTUGAL NO E',
            613 => 'SETUBAL',
            621 => 'AMSTERDAM',
            622 => 'ROTTERDAM',
            623 => 'OTROS PUERTOS DE HOLANDA NO ES',
            631 => 'GOTEMBURGO',
            632 => 'OTROS PUERTOS DE SUECIA NO ESP',
            633 => 'MALMO',
            634 => 'HELSIMBORG',
            635 => 'KALMAR',
            641 => 'AARHUS',
            642 => 'COPENHAGEN',
            643 => 'OTROS PUERTOS DE DINAMARCA NO',
            644 => 'AALBORG',
            645 => 'ODENSE',
            651 => 'OSLO',
            652 => 'OTROS PUERTOS DE NORUEGA NO ES',
            653 => 'STAVANGER',
            699 => 'OTROS PUERTOS DE EUROPA NO ESP',
            711 => 'DURBAM',
            712 => 'CIUDAD DEL CABO',
            713 => 'OTROS PUERTOS DE SUDAFRICA NO',
            714 => 'SALDANHA',
            715 => 'PORT-ELIZABETH',
            716 => 'MOSSEL-BAY',
            717 => 'EAST-LONDON',
            799 => 'OTROS PUERTOS DE AFRICA NO ESP',
            811 => 'SIDNEY',
            812 => 'FREMANTLE',
            813 => 'OTROS PUERTOS DE AUSTRALIA NO',
            814 => 'ADELAIDA',
            815 => 'DARWIN',
            816 => 'GERALDTON',
            899 => 'OTROS PUERTOS DE OCEANIA NO',
            900 => 'RANCHO DE NAVES Y AERONAVES DE',
            901 => 'ARICA',
            902 => 'IQUIQUE',
            903 => 'ANTOFAGASTA',
            904 => 'COQUIMBO',
            905 => 'VALPARAISO',
            906 => 'SAN ANTONIO',
            907 => 'TALCAHUANO',
            908 => 'SAN VICENTE',
            909 => 'LIRQUEN',
            910 => 'PUERTO MONTT',
            911 => 'CHACABUCO/PTO.AYSEN',
            912 => 'PUNTA ARENAS',
            913 => 'PATILLOS',
            914 => 'TOCOPILLA',
            915 => 'MEJILLONES',
            916 => 'TALTAL',
            917 => 'CHANARAL/BARQUITO',
            918 => 'CALDERA',
            919 => 'CALDERILLA',
            920 => 'HUASCO/GUACOLDA',
            921 => 'QUINTERO',
            922 => 'JUAN FERNANDEZ',
            923 => 'CONSTUTUCION',
            924 => 'TOME',
            925 => 'PENCO',
            926 => 'CORONEL',
            927 => 'LOTA',
            928 => 'LEBU',
            929 => 'ISLA DE PASCUA',
            930 => 'CORRAL',
            931 => 'ANCUD',
            932 => 'CASTRO',
            933 => 'QUELLON',
            934 => 'CHAITEN',
            935 => 'TORTEL',
            936 => 'NATALES',
            937 => 'GUARELLO',
            938 => 'CUTTER COVE',
            939 => 'PERCY',
            940 => 'CLARENCIA',
            941 => 'GREGORIO',
            942 => 'CABO NEGRO',
            943 => 'PUERTO WILLIAMS',
            944 => 'TERRITORIO ANTARTICO CHILENO',
            945 => 'SALINAS',
            946 => 'GUAYACAN',
            947 => 'PUNTA DELGADA',
            948 => 'VENTANAS',
            949 => 'PINO HACHADO(LIUCURA',
            950 => 'CALETA COLOSO',
            951 => 'AGUAS NEGRAS',
            952 => 'ZONA FRANCA IQUIQUE',
            953 => 'ZONA FRANCA PUNTA ARENAS',
            954 => 'RIO MAYER',
            955 => 'RIO MOSCO',
            956 => 'VISVIRI',
            957 => 'CHACALLUTA',
            958 => 'CHUNGARA',
            959 => 'COLCHANE',
            960 => 'ABRA DE NAPA',
            961 => 'OLLAGUE',
            962 => 'SAN PEDRO DE ATACAMA',
            963 => 'SOCOMPA',
            964 => 'SAN FRANCISCO',
            965 => 'LOS LIBERTADORES',
            966 => 'MAHUIL MALAL',
            967 => 'CARDENAL SAMORE',
            968 => 'PEREZ ROSALES',
            969 => 'FUTALEUFU',
            970 => 'PALENA-CARRENLEUFU',
            971 => 'PANGUIPULLI',
            972 => 'HUAHUM',
            973 => 'LAGO VERDE',
            974 => 'APPELEG',
            975 => 'PAMPA ALTA',
            976 => 'HUEMULES',
            977 => 'CHILE CHICO',
            978 => 'BAKER',
            979 => 'DOROTEA',
            980 => 'CASAS VIEJAS',
            981 => 'MONTE AYMOND',
            982 => 'SAN SEBASTIAN',
            983 => 'COYHAIQUE ALTO',
            984 => 'TRIANA',
            985 => 'IBANEZ PALAVICINI',
            986 => 'VILLA OHIGGINS',
            987 => 'AEROP.CHACALLUTA',
            988 => 'AEROP.DIEGO ARACENA',
            989 => 'AEROP.CERRO MORENO',
            990 => 'AEROP.EL TEPUAL',
            991 => 'AEROP.C.I.DEL CAMPO',
            992 => 'AEROP.A.M.BENITEZ',
            993 => 'CAP HUACHIPATO',
            994 => 'ARICA-TACNA',
            995 => 'ARICA-LA PAZ',
            996 => 'TERM. PETROLERO ENAP',
            997 => 'OTROS PTOS. CHILENOS',
            998 => 'PASO JAMA',
        ],
    ]; ///< Tablas con los datos de la aduana

    private static $tablasInvertidas = null; ///< Tablas con los datos de la aduana pero invertidas (el valor es la llave de la tabla)

    /**
     * Entrega la glosa para el campo en la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-04-05
     */
    public static function getGlosa($tag)
    {
        if (!isset(self::$tablas[$tag])) {
            return false;
        }
        return is_array(self::$tablas[$tag]) ? self::$tablas[$tag]['glosa'] : self::$tablas[$tag];
    }

    /**
     * Entrega el valor traducido a partir de la tabla
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-27
     */
    public static function getValor($tag, $codigo)
    {
        if (!isset(self::$tablas[$tag])) {
            return false;
        }
        if (!is_array(self::$tablas[$tag])) {
            return $codigo;
        }
        $tabla = isset(self::$tablas[$tag]['valor']) ? self::$tablas[$tag]['valor'] : self::$tablas[self::$tablas[$tag]['tabla']];
        if ($tag=='TipoBultos') {
            $valor = isset($tabla[$codigo['CodTpoBultos']]) ? $tabla[$codigo['CodTpoBultos']] : $codigo['CodTpoBultos'];
            $valor = $codigo['CantBultos'].' '.$valor;
            if (!empty($codigo['IdContainer'])) {
                $valor .= ' ('.$codigo['IdContainer'].' / '.$codigo['Sello'].' / '.$codigo['EmisorSello'].')';
            }
            else if (!empty($codigo['Marcas'])) {
                $valor .= ' ('.$codigo['Marcas'].')';
            }
        } else {
            $valor = isset($tabla[$codigo]) ? $tabla[$codigo] : $codigo;
        }
        return $valor;
    }

    /**
     * Método que entrega a partir de su valor (texto) el código que corresponde
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-26
     */
    public static function getCodigo($tag, $valor)
    {
        if (self::$tablasInvertidas===null) {
            self::$tablasInvertidas = self::getTablasInvertidas();
        }
        $valor = strtoupper($valor);
        return isset(self::$tablasInvertidas[$tag][$valor]) ? self::$tablasInvertidas[$tag][$valor] : $valor;
    }

    /**
     * Método que crea las tablas invertidas en memoria para poder hacer más
     * rápidas las búsquedas.
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-06
     */
    private static function getTablasInvertidas()
    {
        self::$tablasInvertidas = [];
        foreach (self::$tablas as $tag => $info) {
            if (is_string($info) or (!isset($info['valor']) and !isset($info['tabla']))) {
                continue;
            }
            $tabla = isset($info['valor']) ? $info['valor'] : self::$tablas[$info['tabla']];
            foreach ($tabla as &$val) {
                $val = str_replace(
                    ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'],
                    ['A', 'E', 'I', 'O', 'U', 'N', 'a', 'e', 'i', 'o', 'u', 'n'],
                    $val
                );
                $val = strtoupper($val);
            }
            self::$tablasInvertidas[$tag] = array_flip($tabla);
        }
        return self::$tablasInvertidas;
    }

    /**
     * Método que entrega los datos de las nacionalidades
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-13
     */
    public static function getNacionalidades()
    {
        return self::$tablas['paises'];
    }

    /**
     * Método que entrega la glosa de la nacionalidad a partir de su código
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-07-29
     */
    public static function getNacionalidad($codigo)
    {
        return isset(self::$tablas['paises'][$codigo]) ? self::$tablas['paises'][$codigo] : $codigo;
    }

    /**
     * Método que entrega los datos de las formas de pago
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-25
     */
    public static function getFormasDePago()
    {
        return self::$tablas['FmaPagExp']['valor'];
    }

    /**
     * Método que entrega los datos de las modalidades de venta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-25
     */
    public static function getModalidadesDeVenta()
    {
        return self::$tablas['CodModVenta']['valor'];
    }

    /**
     * Método que entrega los datos de las clausulas de venta
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-25
     */
    public static function getClausulasDeVenta()
    {
        return self::$tablas['CodClauVenta']['valor'];
    }

    /**
     * Método que entrega los datos de los tipos de transportes
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-25
     */
    public static function getTransportes()
    {
        return self::$tablas['CodViaTransp']['valor'];
    }

    /**
     * Método que entrega los datos de los puertos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-25
     */
    public static function getPuertos()
    {
        return self::$tablas['puertos'];
    }

    /**
     * Método que entrega los datos de las unidades
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-25
     */
    public static function getUnidades()
    {
        return self::$tablas['unidades'];
    }

    /**
     * Método que entrega los datos de los tipos de bultos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-25
     */
    public static function getBultos()
    {
        return self::$tablas['TipoBultos']['valor'];
    }

}

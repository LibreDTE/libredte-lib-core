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

namespace libredte\lib\Core\Package\Billing\Component\Document\Worker\Dispatcher\Job;

use DateTimeImmutable;
use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;
use Derafu\Date\Exception\InvalidDateException;
use Derafu\Date\Support\DateParser;
use Derafu\L10n\Cl\Rut\Rut;
use Derafu\Translation\Exception\Runtime\TranslatableUnexpectedValueException as UnexpectedValueException;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\XPathReader;
use libredte\lib\Core\Package\Billing\Component\Document\Exception\DispatcherException;

/**
 * Valida los datos del sobre de documentos tributarios electrónicos.
 *
 * Solo valida lo que no valida el esquema XSD del sobre, y solo respecto al
 * sobre. La validación de los datos de cada documento corresponde al worker
 * de validación de documentos.
 *
 * Reglas:
 *
 *   - Los RUT de la carátula tienen dígito verificador válido.
 *   - La fecha de la resolución no es posterior a la fecha de la firma del
 *     sobre.
 *   - La cantidad de documentos de cada tipo declarada en la carátula
 *     coincide con la cantidad de documentos del sobre.
 */
#[Job(name: 'validate_envelope', worker: 'dispatcher', component: 'document', package: 'billing')]
class ValidateEnvelopeJob extends AbstractJob implements JobInterface
{
    /**
     * Tags de la carátula que contienen un RUT.
     *
     * @var list<string>
     */
    private const RUT_TAGS = ['RutEmisor', 'RutEnvia', 'RutReceptor'];

    /**
     * Valida los datos del sobre.
     *
     * @param XmlDocumentInterface $xmlDocument XML del sobre de documentos.
     * @return void
     * @throws DispatcherException Si algún dato del sobre no es válido.
     */
    public function execute(XmlDocumentInterface $xmlDocument): void
    {
        $reader = $xmlDocument->getReader(source: 'envelope XML');

        $this->validateRuts($reader);
        $this->validateResolutionDate($reader);
        $this->validateSubTotals($reader);
    }

    /**
     * Valida que los RUT de la carátula tengan dígito verificador válido.
     *
     * @param XPathReader $reader
     * @return void
     * @throws DispatcherException
     */
    private function validateRuts(XPathReader $reader): void
    {
        foreach (self::RUT_TAGS as $tag) {
            $rut = $reader->requireString('//Caratula/' . $tag);

            try {
                Rut::validate($rut);
            } catch (UnexpectedValueException $e) {
                throw new DispatcherException([
                    'The RUT {rut} in the envelope cover tag {tag} is not valid. {detail}',
                    'rut' => $rut,
                    'tag' => $tag,
                    'detail' => $e,
                ], previous: $e);
            }
        }
    }

    /**
     * Valida que la fecha de la resolución no sea posterior a la fecha de la
     * firma del sobre.
     *
     * Se compara con la fecha de la firma y no con la fecha actual para que el
     * resultado no dependa del momento en que se valida el sobre.
     *
     * @param XPathReader $reader
     * @return void
     * @throws DispatcherException
     */
    private function validateResolutionDate(XPathReader $reader): void
    {
        $resolution = $reader->requireString('//Caratula/FchResol');
        $signature = $reader->requireString('//Caratula/TmstFirmaEnv');

        $resolutionDate = $this->parseDate($resolution, 'Y-m-d', 'FchResol');
        $signatureDate = $this->parseDate($signature, 'Y-m-d\TH:i:s', 'TmstFirmaEnv');

        // La fecha de la firma se compara al inicio del día: la resolución
        // puede ser del mismo día en que se firma el sobre.
        if ($resolutionDate > $signatureDate->setTime(0, 0)) {
            throw new DispatcherException([
                'The resolution date {resolution} of the envelope cover is later than the signing date {signature}.',
                'resolution' => $resolution,
                'signature' => $signature,
            ]);
        }
    }

    /**
     * Lee una fecha de la carátula, que debe estar escrita exactamente en el
     * formato indicado (con todos sus dígitos).
     *
     * @param string $value Texto de la fecha.
     * @param string $format Formato en que debe estar escrita.
     * @param string $tag Tag de la carátula, para el mensaje de error.
     * @return DateTimeImmutable
     * @throws DispatcherException Si no es una fecha válida en el formato.
     */
    private function parseDate(string $value, string $format, string $tag): DateTimeImmutable
    {
        try {
            $date = DateParser::parseStrict($value, $format);
        } catch (InvalidDateException $e) {
            throw new DispatcherException([
                'The date {date} in the envelope cover tag {tag} is not valid. {detail}',
                'date' => $value,
                'tag' => $tag,
                'detail' => $e,
            ], previous: $e);
        }

        // `parseStrict()` acepta campos con menos dígitos (`2014-8-22`).
        if ($date->format($format) !== $value) {
            throw new DispatcherException([
                'The date {date} in the envelope cover tag {tag} is not valid. {detail}',
                'date' => $value,
                'tag' => $tag,
                'detail' => $date->format($format),
            ]);
        }

        return $date;
    }

    /**
     * Valida que la cantidad de documentos de cada tipo declarada en la
     * carátula coincida con la cantidad de documentos del sobre.
     *
     * @param XPathReader $reader
     * @return void
     * @throws DispatcherException
     */
    private function validateSubTotals(XPathReader $reader): void
    {
        $declared = [];
        foreach ($reader->requireReaders('//Caratula/SubTotDTE') as $subTotal) {
            $type = $subTotal->requireAsInt('TpoDTE');
            $declared[$type] = ($declared[$type] ?? 0) + $subTotal->requireAsInt('NroDTE');
        }

        $found = [];
        foreach ($reader->getReaders('//SetDTE/DTE/*/Encabezado/IdDoc') ?? [] as $idDoc) {
            $type = $idDoc->requireAsInt('TipoDTE');
            $found[$type] = ($found[$type] ?? 0) + 1;
        }

        $types = array_keys($declared + $found);
        sort($types);
        foreach ($types as $type) {
            if (($declared[$type] ?? 0) !== ($found[$type] ?? 0)) {
                throw new DispatcherException([
                    'The envelope cover declares {declared} documents of type {type}, but the envelope contains {found}.',
                    'declared' => $declared[$type] ?? 0,
                    'type' => $type,
                    'found' => $found[$type] ?? 0,
                ]);
            }
        }
    }
}

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

namespace libredte\lib\Tests\Unit\Package\Billing\Component\Integration;

use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiException;
use libredte\lib\Core\Package\Billing\Component\Integration\Exception\SiiLazy\ConsumeWebserviceException;
use libredte\lib\Core\Package\Billing\Component\Integration\Support\SiiAttempts;
use libredte\lib\Core\Package\Billing\Component\Integration\Worker\SiiLazy\Job\ConsumeWebserviceJob;
use libredte\lib\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;

/**
 * Tests of how long a call to a SOAP service of the SII can take when the
 * service accepts the connection and never answers.
 */
#[CoversClass(ConsumeWebserviceJob::class)]
#[UsesClass(SiiAttempts::class)]
#[UsesClass(SiiException::class)]
class ConsumeWebserviceJobTimeoutTest extends TestCase
{
    /**
     * Calls the function of a service that never answers and returns the
     * seconds it took to fail.
     *
     * The server only listens: the connection is accepted by the system and
     * the request is never answered.
     */
    private function callAServiceThatNeverAnswers(SiiAttempts $attempts): float
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertNotFalse($server, (string) $errorMessage);
        $address = (string) stream_socket_get_name($server, false);

        $wsdl = (string) tempnam(sys_get_temp_dir(), 'sii_wsdl_');
        file_put_contents($wsdl, $this->createWsdl('http://' . $address . '/'));

        $job = new ConsumeWebserviceJob(
            new XmlService(new XmlEncoder(), new XmlDecoder(), new XmlValidator())
        );
        $call = new ReflectionMethod($job, 'callServiceFunction');

        $previousTimeout = (string) ini_get('default_socket_timeout');
        $start = microtime(true);
        try {
            $call->invoke(
                $job,
                $wsdl,
                'getEstCesionRelac',
                ['x'],
                ['exceptions' => true, 'cache_wsdl' => WSDL_CACHE_NONE],
                $attempts,
                null
            );
            $this->fail('The call should fail: the service never answers.');
        } catch (ConsumeWebserviceException) {
            return microtime(true) - $start;
        } finally {
            ini_set('default_socket_timeout', $previousTimeout);
            fclose($server);
            unlink($wsdl);
        }
    }

    public function testACallThatIsNeverAnsweredFailsWhenTheTimeoutOfTheAttemptEnds(): void
    {
        $attempts = new SiiAttempts(1, 2, 60);

        $seconds = $this->callAServiceThatNeverAnswers($attempts);

        $this->assertGreaterThanOrEqual(1.5, $seconds);
        $this->assertLessThan(6.0, $seconds);
        $this->assertCount(1, $attempts);
    }

    public function testTheTotalDurationStopsTheRetries(): void
    {
        // Each attempt takes 2 s and the total allows 3 s: after the first one
        // there is no time for another, so the 5 retries are not made.
        $attempts = new SiiAttempts(5, 2, 3);

        $seconds = $this->callAServiceThatNeverAnswers($attempts);

        $this->assertLessThan(5.0, $seconds);
        $this->assertCount(1, $attempts);
    }

    /**
     * Creates the WSDL of a service with a function that answers at an address.
     */
    private function createWsdl(string $location): string
    {
        return <<<XML
            <?xml version="1.0"?>
            <definitions name="S" targetNamespace="urn:s"
                xmlns="http://schemas.xmlsoap.org/wsdl/"
                xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
                xmlns:tns="urn:s"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema">
              <message name="inM"><part name="x" type="xsd:string"/></message>
              <message name="outM"><part name="r" type="xsd:string"/></message>
              <portType name="P">
                <operation name="getEstCesionRelac">
                  <input message="tns:inM"/>
                  <output message="tns:outM"/>
                </operation>
              </portType>
              <binding name="B" type="tns:P">
                <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
                <operation name="getEstCesionRelac">
                  <soap:operation soapAction=""/>
                  <input><soap:body use="encoded" namespace="urn:s" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></input>
                  <output><soap:body use="encoded" namespace="urn:s" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/></output>
                </operation>
              </binding>
              <service name="S">
                <port name="SP" binding="tns:B"><soap:address location="{$location}"/></port>
              </service>
            </definitions>
            XML;
    }
}

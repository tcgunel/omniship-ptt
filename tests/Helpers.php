<?php

declare(strict_types=1);

namespace Omniship\PTT\Tests;

use Omniship\Common\Message\AbstractRequest;
use Omniship\Common\Message\ResponseInterface;

function createMockSoapClient(): \SoapClient
{
    return new class (null, [
        'uri' => 'http://pttws.ptt.gov.tr/PttVeriYukleme/services/Sorgu',
        'location' => 'http://localhost',
    ]) extends \SoapClient {
        public function __soapCall(string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null): mixed
        {
            return new \stdClass();
        }
    };
}

function createMockSoapClientWithResponse(object $response): \SoapClient
{
    return new class (null, [
        'uri' => 'http://pttws.ptt.gov.tr/PttVeriYukleme/services/Sorgu',
        'location' => 'http://localhost',
    ], $response) extends \SoapClient {
        public function __construct(?string $wsdl, array $options, private readonly object $mockResponse)
        {
            parent::__construct($wsdl, $options);
        }

        public function __soapCall(string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null): mixed
        {
            return $this->mockResponse;
        }
    };
}

function createMockRequest(): AbstractRequest
{
    return new class extends AbstractRequest {
        public function getData(): array
        {
            return [];
        }

        public function sendData(array $data): ResponseInterface
        {
            throw new \RuntimeException('Not implemented');
        }
    };
}

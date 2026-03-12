<?php

declare(strict_types=1);

namespace Omniship\PTT;

use Omniship\Common\AbstractSoapCarrier;
use Omniship\Common\Message\RequestInterface;
use Omniship\PTT\Message\CancelShipmentRequest;
use Omniship\PTT\Message\CreateShipmentRequest;
use Omniship\PTT\Message\GetTrackingStatusRequest;

class Carrier extends AbstractSoapCarrier
{
    /** @var array<string, \SoapClient> */
    private array $soapClients = [];

    public function getName(): string
    {
        return 'PTT Kargo';
    }

    public function getShortName(): string
    {
        return 'PTT';
    }

    protected function getWsdlUrl(): string
    {
        return $this->getServiceWsdlUrl('PttVeriYukleme');
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultParameters(): array
    {
        return [
            'musteriId' => '',
            'sifre' => '',
            'testMode' => false,
        ];
    }

    public function getMusteriId(): string
    {
        return $this->getParameter('musteriId') ?? '';
    }

    public function setMusteriId(string $musteriId): static
    {
        return $this->setParameter('musteriId', $musteriId);
    }

    public function getSifre(): string
    {
        return $this->getParameter('sifre') ?? '';
    }

    public function setSifre(string $sifre): static
    {
        return $this->setParameter('sifre', $sifre);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CreateShipmentRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getTrackingStatus(array $options = []): RequestInterface
    {
        return $this->createRequest(GetTrackingStatusRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function cancelShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CancelShipmentRequest::class, $options);
    }

    /**
     * PTT uses separate SOAP services for different operations.
     * Override to route each request class to the correct service WSDL.
     *
     * @param class-string<RequestInterface> $class
     * @param array<string, mixed> $parameters
     */
    protected function createRequest(string $class, array $parameters): RequestInterface
    {
        $service = match ($class) {
            GetTrackingStatusRequest::class => 'GonderiHareketV2',
            default => 'PttVeriYukleme',
        };

        /** @var \Omniship\Common\Message\AbstractSoapRequest $request */
        $request = new $class($this->getSoapClientForService($service));

        return $request->initialize(
            array_replace($this->getParameters(), $parameters),
        );
    }

    private function getServiceWsdlUrl(string $service): string
    {
        $suffix = $this->getTestMode() ? 'Test' : '';

        return "https://pttws.ptt.gov.tr/{$service}{$suffix}/services/Sorgu?wsdl";
    }

    private function getSoapClientForService(string $service): \SoapClient
    {
        // When a SoapClient is injected (e.g. for testing), use it for all services.
        if ($this->soapClient !== null) {
            return $this->soapClient;
        }

        if (!isset($this->soapClients[$service])) {
            $this->soapClients[$service] = new \SoapClient(
                $this->getServiceWsdlUrl($service),
                $this->getSoapOptions(),
            );
        }

        return $this->soapClients[$service];
    }
}

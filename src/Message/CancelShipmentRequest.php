<?php

declare(strict_types=1);

namespace Omniship\PTT\Message;

use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;

class CancelShipmentRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'barkodVeriSil';
    }

    public function getDosyaAdi(): ?string
    {
        return $this->getParameter('dosyaAdi');
    }

    public function setDosyaAdi(string $dosyaAdi): static
    {
        return $this->setParameter('dosyaAdi', $dosyaAdi);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $barcode = $this->getTrackingNumber();

        if ($barcode === null || $barcode === '') {
            $this->validate('trackingNumber');
        }

        $this->validate('musteriId', 'sifre');

        return [
            'inpDelete' => [
                'barcode' => $barcode,
                'dosyaAdi' => $this->getDosyaAdi() ?? '',
                'musteriId' => $this->getParameter('musteriId'),
                'sifre' => $this->getParameter('sifre'),
            ],
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new CancelShipmentResponse($this, $data);
    }
}

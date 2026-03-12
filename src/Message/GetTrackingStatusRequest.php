<?php

declare(strict_types=1);

namespace Omniship\PTT\Message;

use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;

class GetTrackingStatusRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'barkodSorgu';
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('musteriId', 'sifre', 'trackingNumber');

        return [
            'input' => [
                'musteri_no' => $this->getParameter('musteriId'),
                'sifre' => $this->getParameter('sifre'),
                'barkod' => $this->getTrackingNumber(),
            ],
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new GetTrackingStatusResponse($this, $data);
    }
}

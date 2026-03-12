<?php

declare(strict_types=1);

namespace Omniship\PTT\Message;

use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\CancelResponse;

class CancelShipmentResponse extends AbstractResponse implements CancelResponse
{
    public function isSuccessful(): bool
    {
        return $this->getHataKodu() === 1;
    }

    public function isCancelled(): bool
    {
        return $this->isSuccessful();
    }

    public function getMessage(): ?string
    {
        return $this->getAciklama();
    }

    public function getCode(): ?string
    {
        $hataKodu = $this->getHataKodu();

        return $hataKodu !== null ? (string) $hataKodu : null;
    }

    private function getReturn(): ?object
    {
        $data = $this->data;

        if (is_object($data) && isset($data->return)) {
            return is_object($data->return) ? $data->return : null;
        }

        // Flat structure (mock responses)
        if (is_object($data) && isset($data->hataKodu)) {
            return $data;
        }

        return null;
    }

    private function getHataKodu(): ?int
    {
        $return = $this->getReturn();

        if ($return !== null && isset($return->hataKodu)) {
            return (int) $return->hataKodu;
        }

        return null;
    }

    private function getAciklama(): ?string
    {
        $return = $this->getReturn();

        if ($return !== null && isset($return->aciklama)) {
            return (string) $return->aciklama;
        }

        return null;
    }
}

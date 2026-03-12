<?php

declare(strict_types=1);

namespace Omniship\PTT\Message;

use Omniship\Common\Label;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\ShipmentResponse;

class CreateShipmentResponse extends AbstractResponse implements ShipmentResponse
{
    public function isSuccessful(): bool
    {
        return $this->getHataKodu() === 1;
    }

    public function getMessage(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->donguAciklama) && $detail->donguAciklama !== '') {
            return (string) $detail->donguAciklama;
        }

        return $this->getAciklama();
    }

    public function getCode(): ?string
    {
        $hataKodu = $this->getHataKodu();

        return $hataKodu !== null ? (string) $hataKodu : null;
    }

    public function getShipmentId(): ?string
    {
        return null;
    }

    public function getTrackingNumber(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->barkod) && $detail->barkod !== '') {
            return (string) $detail->barkod;
        }

        return null;
    }

    public function getBarcode(): ?string
    {
        return $this->getTrackingNumber();
    }

    public function getLabel(): ?Label
    {
        return null;
    }

    public function getTotalCharge(): ?float
    {
        return null;
    }

    public function getCurrency(): ?string
    {
        return null;
    }

    public function getTrackingUrl(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->donguAciklama)) {
            $url = (string) $detail->donguAciklama;
            if (str_starts_with($url, 'http')) {
                return $url;
            }
        }

        return null;
    }

    private function getReturn(): ?object
    {
        $data = $this->data;

        if (is_object($data) && isset($data->return)) {
            return is_object($data->return) ? $data->return : null;
        }

        // Flat structure (mock responses may not have ->return wrapper)
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

    private function getDetail(): ?object
    {
        $return = $this->getReturn();

        if ($return === null || !isset($return->dongu)) {
            return null;
        }

        $dongu = $return->dongu;

        if (is_array($dongu)) {
            return $dongu[0] ?? null;
        }

        if (is_object($dongu)) {
            return $dongu;
        }

        return null;
    }
}

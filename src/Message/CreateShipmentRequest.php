<?php

declare(strict_types=1);

namespace Omniship\PTT\Message;

use Omniship\Common\Address;
use Omniship\Common\Enum\PaymentType;
use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;
use Omniship\Common\Package;

class CreateShipmentRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'kabulEkle2';
    }

    public function getDosyaAdi(): ?string
    {
        return $this->getParameter('dosyaAdi');
    }

    public function setDosyaAdi(string $dosyaAdi): static
    {
        return $this->setParameter('dosyaAdi', $dosyaAdi);
    }

    public function getBarcode(): ?string
    {
        return $this->getParameter('barcode');
    }

    public function setBarcode(string $barcode): static
    {
        return $this->setParameter('barcode', $barcode);
    }

    public function getReferenceNumber(): ?string
    {
        return $this->getParameter('referenceNumber');
    }

    public function setReferenceNumber(string $referenceNumber): static
    {
        return $this->setParameter('referenceNumber', $referenceNumber);
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->getParameter('paymentType');
    }

    public function setPaymentType(PaymentType $paymentType): static
    {
        return $this->setParameter('paymentType', $paymentType);
    }

    public function getCashOnDelivery(): bool
    {
        return (bool) $this->getParameter('cashOnDelivery');
    }

    public function setCashOnDelivery(bool $value): static
    {
        return $this->setParameter('cashOnDelivery', $value);
    }

    public function getCodAmount(): ?float
    {
        return $this->getParameter('codAmount');
    }

    public function setCodAmount(float $amount): static
    {
        return $this->setParameter('codAmount', $amount);
    }

    public function getInsuredValue(): ?float
    {
        return $this->getParameter('insuredValue');
    }

    public function setInsuredValue(float $value): static
    {
        return $this->setParameter('insuredValue', $value);
    }

    public function getExtraServices(): ?string
    {
        return $this->getParameter('extraServices');
    }

    public function setExtraServices(string $services): static
    {
        return $this->setParameter('extraServices', $services);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('musteriId', 'sifre', 'shipFrom', 'shipTo', 'barcode');

        $shipFrom = $this->getShipFrom();
        assert($shipFrom instanceof Address);

        $shipTo = $this->getShipTo();
        assert($shipTo instanceof Address);

        $packages = $this->getPackages() ?? [];

        $totalWeightGrams = 0;
        $totalDesi = 0.0;
        $totalLength = 0.0;
        $totalWidth = 0.0;
        $totalHeight = 0.0;

        foreach ($packages as $package) {
            // PTT expects weight in grams
            $totalWeightGrams += (int) ($package->weight * 1000);
            $totalDesi += $package->getDesi() ?? 0;
            $totalLength += $package->length ?? 0;
            $totalWidth += $package->width ?? 0;
            $totalHeight += $package->height ?? 0;
        }

        if ($totalWeightGrams === 0) {
            $totalWeightGrams = 1;
        }

        if ($totalDesi === 0.0 && $totalLength > 0 && $totalWidth > 0 && $totalHeight > 0) {
            $totalDesi = ($totalLength * $totalWidth * $totalHeight) / 3000;
        }

        if ($totalDesi === 0.0) {
            $totalDesi = 1;
        }

        // Build extra services string
        $extraServices = $this->getExtraServices() ?? '';

        if ($this->getCashOnDelivery() && !str_contains($extraServices, 'OS')) {
            $extraServices .= 'OS';
        }

        if ($this->getInsuredValue() !== null && $this->getInsuredValue() > 0 && !str_contains($extraServices, 'DK')) {
            $extraServices .= 'DK';
        }

        // Map PaymentType to PTT odemesekli
        $odemeSekli = $this->mapPaymentType($this->getPaymentType());

        // Phone: PTT expects 10 digits without leading 0
        $recipientPhone = $this->formatPhone($shipTo->phone ?? '');
        $senderPhone = $this->formatPhone($shipFrom->phone ?? '');

        $dosyaAdi = $this->getDosyaAdi() ?? ('PTT-' . date('Ymd-His-') . uniqid());

        $dongu = [
            'aAdres' => $this->buildAddress($shipTo),
            'agirlik' => $totalWeightGrams,
            'aliciAdi' => $shipTo->name ?? '',
            'aliciIlAdi' => $shipTo->city ?? '',
            'aliciIlceAdi' => $shipTo->district ?? '',
            'aliciSms' => $recipientPhone,
            'barkodNo' => $this->getBarcode(),
            'boy' => (int) ($totalLength ?: 1),
            'deger_ucreti' => $this->getInsuredValue() ?? 0,
            'desi' => $totalDesi,
            'ekhizmet' => $extraServices,
            'en' => (int) ($totalWidth ?: 1),
            'musteriReferansNo' => $this->getReferenceNumber() ?? '',
            'odemesekli' => $odemeSekli,
            'odeme_sart_ucreti' => $this->getCashOnDelivery() ? ($this->getCodAmount() ?? 0) : 0,
            'rezerve1' => '',
            'yukseklik' => (int) ($totalHeight ?: 1),
            'gondericibilgi' => [
                'gonderici_adi' => $shipFrom->name ?? '',
                'gonderici_adresi' => $this->buildAddress($shipFrom),
                'gonderici_email' => $shipFrom->email ?? '',
                'gonderici_il_ad' => $shipFrom->city ?? '',
                'gonderici_ilce_ad' => $shipFrom->district ?? '',
                'gonderici_posta_kodu' => $shipFrom->postalCode ?? '',
                'gonderici_telefonu' => $senderPhone,
                'gonderici_sms' => $senderPhone,
                'gonderici_ulke_id' => '052',
            ],
        ];

        return [
            'input' => [
                'dosyaAdi' => $dosyaAdi,
                'gonderiTip' => 'NORMAL',
                'gonderiTur' => 'KARGO',
                'kullanici' => 'PttWs',
                'musteriId' => $this->getParameter('musteriId'),
                'sifre' => $this->getParameter('sifre'),
                'dongu' => [$dongu],
            ],
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new CreateShipmentResponse($this, $data);
    }

    private function buildAddress(Address $address): string
    {
        $parts = array_filter([
            $address->street1,
            $address->street2,
        ]);

        return implode(' ', $parts);
    }

    private function formatPhone(string $phone): string
    {
        // Strip everything except digits
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === null) {
            return '';
        }

        // Remove country code +90 or 0090
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        // Remove leading 0
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    private function mapPaymentType(?PaymentType $paymentType): string
    {
        if ($paymentType === null) {
            return '';
        }

        return match ($paymentType) {
            PaymentType::SENDER => 'MH',        // Mahsup
            PaymentType::RECEIVER => 'UA',      // Ücreti Alıcıdan
            PaymentType::THIRD_PARTY => 'N',    // Nakit
        };
    }
}

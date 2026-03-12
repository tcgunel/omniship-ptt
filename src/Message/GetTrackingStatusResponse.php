<?php

declare(strict_types=1);

namespace Omniship\PTT\Message;

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\TrackingResponse;
use Omniship\Common\TrackingEvent;
use Omniship\Common\TrackingInfo;

class GetTrackingStatusResponse extends AbstractResponse implements TrackingResponse
{
    /**
     * PTT gonderi_durum_id → ShipmentStatus mapping.
     * Maps the most common status codes to our normalized statuses.
     */
    private const STATUS_MAP = [
        // KABUL (ust_durum_id: 1000)
        1 => ShipmentStatus::PICKED_UP,       // Kabul Edildi
        701 => ShipmentStatus::PICKED_UP,     // Kayıt Edildi
        // SEVK (ust_durum_id: 2000)
        77 => ShipmentStatus::IN_TRANSIT,     // Sevk Edildi
        8 => ShipmentStatus::IN_TRANSIT,      // Zimmet Alındı
        9 => ShipmentStatus::IN_TRANSIT,      // Zimmet Edildi
        3 => ShipmentStatus::IN_TRANSIT,      // Torbaya Eklendi
        6 => ShipmentStatus::IN_TRANSIT,      // Torbadan Alındı
        11 => ShipmentStatus::IN_TRANSIT,     // Geliş Kaydı Yapıldı
        91 => ShipmentStatus::IN_TRANSIT,     // Torbası Sevk Edildi
        92 => ShipmentStatus::IN_TRANSIT,     // Torbasının Geliş Kaydı Yapıldı
        149 => ShipmentStatus::IN_TRANSIT,    // Ptt Tarafından Zimmet Alındı
        // DAĞITIM (ust_durum_id: 3000)
        7 => ShipmentStatus::OUT_FOR_DELIVERY, // Dağıtıcıya Verildi
        // İADE (ust_durum_id: 4000)
        99 => ShipmentStatus::RETURNED,       // İade Edilecek
        151 => ShipmentStatus::RETURNED,      // İADE-Tanınmıyor
        154 => ShipmentStatus::RETURNED,      // İADE-Kabul Edilmedi
        155 => ShipmentStatus::RETURNED,      // İADE-Adres Yetersiz
        156 => ShipmentStatus::RETURNED,      // İADE-Diğer
        161 => ShipmentStatus::RETURNED,      // İADE-Adreste Yok
        // TESLİM (ust_durum_id: 5000)
        100 => ShipmentStatus::DELIVERED,     // Teslim Edildi
        157 => ShipmentStatus::DELIVERED,     // Evrak Memuruna Teslim
        202 => ShipmentStatus::DELIVERED,     // Kargomatikten Teslim Alındı
        252 => ShipmentStatus::DELIVERED,     // MAZBATA TESLİM
        807 => ShipmentStatus::DELIVERED,     // MUHATABA BİZZAT TESLİM
        812 => ShipmentStatus::DELIVERED,     // AYNI KONUTTA YAKINA TESLİM
        // GÖNDERİCİSİNE TESLİM (ust_durum_id: 5500)
        120 => ShipmentStatus::RETURNED,      // Göndericisine Teslim Edildi
        141 => ShipmentStatus::RETURNED,      // İadeten Banka Şubesine Teslim
        // TESLİM EDİLEMEDİ (ust_durum_id: 6000)
        101 => ShipmentStatus::FAILURE,       // Teslim Edilemedi
        109 => ShipmentStatus::FAILURE,       // İmha
        124 => ShipmentStatus::FAILURE,       // Adreste Yok/Kapalı
        126 => ShipmentStatus::FAILURE,       // Adreste Tanınmıyor
        140 => ShipmentStatus::FAILURE,       // Kabul Edilmedi
        // KABUL İPTAL (ust_durum_id: 7000)
        2 => ShipmentStatus::CANCELLED,       // İptal Edildi
    ];

    public function isSuccessful(): bool
    {
        $return = $this->getReturn();

        if ($return === null) {
            return false;
        }

        // sonucKodu == 10 means successful query for barkodSorgu
        if (isset($return->sonucKodu)) {
            return (int) $return->sonucKodu === 10;
        }

        return isset($return->BARNO);
    }

    public function getMessage(): ?string
    {
        $return = $this->getReturn();

        if ($return !== null && isset($return->sonucAciklama)) {
            return (string) $return->sonucAciklama;
        }

        return null;
    }

    public function getCode(): ?string
    {
        $return = $this->getReturn();

        if ($return !== null && isset($return->sonucKodu)) {
            return (string) $return->sonucKodu;
        }

        return null;
    }

    public function getTrackingInfo(): TrackingInfo
    {
        $return = $this->getReturn();
        $events = [];
        $status = ShipmentStatus::UNKNOWN;
        $trackingNumber = '';

        if ($return !== null) {
            $trackingNumber = isset($return->BARNO) ? (string) $return->BARNO : '';
            $events = $this->parseEvents($return);

            // Current status is derived from the last event
            if ($events !== []) {
                $lastEvent = end($events);
                $status = $lastEvent->status;
            }
        }

        return new TrackingInfo(
            trackingNumber: $trackingNumber,
            status: $status,
            events: $events,
            carrier: 'PTT Kargo',
        );
    }

    /**
     * @return TrackingEvent[]
     */
    private function parseEvents(?object $return): array
    {
        if ($return === null || !isset($return->dongu)) {
            return [];
        }

        $events = [];
        $dongu = $return->dongu;

        if (!is_array($dongu)) {
            $dongu = [$dongu];
        }

        foreach ($dongu as $item) {
            if (!is_object($item)) {
                continue;
            }

            $statusCode = isset($item->IKODU) ? (int) $item->IKODU : null;
            $eventStatus = $statusCode !== null
                ? (self::STATUS_MAP[$statusCode] ?? ShipmentStatus::UNKNOWN)
                : ShipmentStatus::UNKNOWN;

            $dateTime = $this->parseDateTime($item);

            $location = null;
            if (isset($item->IMERK) && trim((string) $item->IMERK) !== '') {
                $location = (string) $item->IMERK;
            }

            $events[] = new TrackingEvent(
                status: $eventStatus,
                description: (string) ($item->ISLEM ?? ''),
                occurredAt: $dateTime,
                location: $location,
            );
        }

        return $events;
    }

    private function parseDateTime(object $item): \DateTimeImmutable
    {
        $date = isset($item->ITARIH) ? (string) $item->ITARIH : '';
        $time = isset($item->ISAAT) ? (string) $item->ISAAT : '';

        if ($date !== '') {
            try {
                // ITARIH format: DD/MM/YYYY or YYYYMMDD
                if (str_contains($date, '/')) {
                    $dateStr = $date . ($time !== '' ? ' ' . $time : '');

                    return new \DateTimeImmutable($dateStr);
                }

                if (strlen($date) === 8 && ctype_digit($date)) {
                    $formatted = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
                    $dateStr = $formatted . ($time !== '' ? ' ' . $time : '');

                    return new \DateTimeImmutable($dateStr);
                }

                return new \DateTimeImmutable($date);
            } catch (\Exception) {
                // Fall through to default
            }
        }

        return new \DateTimeImmutable();
    }

    private function getReturn(): ?object
    {
        $data = $this->data;

        if (is_object($data) && isset($data->return)) {
            return is_object($data->return) ? $data->return : null;
        }

        // Flat structure (mock responses may not have ->return wrapper)
        if (is_object($data) && (isset($data->BARNO) || isset($data->sonucKodu) || isset($data->dongu))) {
            return $data;
        }

        return null;
    }

    public static function mapStatus(int $statusCode): ShipmentStatus
    {
        return self::STATUS_MAP[$statusCode] ?? ShipmentStatus::UNKNOWN;
    }
}

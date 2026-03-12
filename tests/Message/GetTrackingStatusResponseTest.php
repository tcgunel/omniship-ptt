<?php

declare(strict_types=1);

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\PTT\Message\GetTrackingStatusResponse;

use function Omniship\PTT\Tests\createMockRequest;

it('parses successful delivery response with multiple events', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'ALICI' => 'Mehmet Demir',
            'BARNO' => 'KP02123456789',
            'GONDEREN' => 'Ahmet Yılmaz',
            'sonucKodu' => 10,
            'dongu' => [
                (object) [
                    'IKODU' => '1',
                    'ISLEM' => 'Kabul Edildi',
                    'ITARIH' => '20/03/2026',
                    'ISAAT' => '10:00:00',
                    'IMERK' => 'İSTANBUL/KADIKÖY',
                    'siraNo' => 1,
                ],
                (object) [
                    'IKODU' => '77',
                    'ISLEM' => 'Sevk Edildi',
                    'ITARIH' => '21/03/2026',
                    'ISAAT' => '08:00:00',
                    'IMERK' => 'ANKARA AKTARMA',
                    'siraNo' => 2,
                ],
                (object) [
                    'IKODU' => '7',
                    'ISLEM' => 'Dağıtıcıya Verildi',
                    'ITARIH' => '22/03/2026',
                    'ISAAT' => '09:00:00',
                    'IMERK' => 'ANKARA/ÇANKAYA',
                    'siraNo' => 3,
                ],
                (object) [
                    'IKODU' => '100',
                    'ISLEM' => 'Teslim Edildi',
                    'ITARIH' => '22/03/2026',
                    'ISAAT' => '14:30:00',
                    'IMERK' => 'ANKARA/ÇANKAYA',
                    'siraNo' => 4,
                ],
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue();

    $info = $response->getTrackingInfo();

    expect($info->trackingNumber)->toBe('KP02123456789')
        ->and($info->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->carrier)->toBe('PTT Kargo')
        ->and($info->events)->toHaveCount(4);

    expect($info->events[0]->status)->toBe(ShipmentStatus::PICKED_UP)
        ->and($info->events[0]->description)->toBe('Kabul Edildi')
        ->and($info->events[0]->location)->toBe('İSTANBUL/KADIKÖY');

    expect($info->events[1]->status)->toBe(ShipmentStatus::IN_TRANSIT)
        ->and($info->events[1]->description)->toBe('Sevk Edildi');

    expect($info->events[2]->status)->toBe(ShipmentStatus::OUT_FOR_DELIVERY)
        ->and($info->events[2]->description)->toBe('Dağıtıcıya Verildi');

    expect($info->events[3]->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->events[3]->description)->toBe('Teslim Edildi');
});

it('parses single event response (not array)', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'BARNO' => 'KP02987654321',
            'sonucKodu' => 10,
            'dongu' => (object) [
                'IKODU' => '1',
                'ISLEM' => 'Kabul Edildi',
                'ITARIH' => '20/03/2026',
                'ISAAT' => '15:42:34',
                'IMERK' => 'İSTANBUL',
                'siraNo' => 1,
            ],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::PICKED_UP)
        ->and($info->trackingNumber)->toBe('KP02987654321')
        ->and($info->events)->toHaveCount(1);
});

it('parses returned/iade response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'BARNO' => 'KP02111111111',
            'sonucKodu' => 10,
            'dongu' => (object) [
                'IKODU' => '99',
                'ISLEM' => 'İade Edilecek',
                'ITARIH' => '22/03/2026',
                'ISAAT' => '10:00:00',
                'IMERK' => 'ANKARA',
                'siraNo' => 1,
            ],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::RETURNED);
});

it('parses cancelled response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'BARNO' => 'KP02222222222',
            'sonucKodu' => 10,
            'dongu' => (object) [
                'IKODU' => '2',
                'ISLEM' => 'İptal Edildi',
                'ITARIH' => '22/03/2026',
                'ISAAT' => '10:00:00',
                'IMERK' => 'ANKARA',
                'siraNo' => 1,
            ],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::CANCELLED);
});

it('parses failure response (teslim edilemedi)', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'BARNO' => 'KP02333333333',
            'sonucKodu' => 10,
            'dongu' => (object) [
                'IKODU' => '101',
                'ISLEM' => 'Teslim Edilemedi',
                'ITARIH' => '22/03/2026',
                'ISAAT' => '16:00:00',
                'IMERK' => 'ANKARA/ÇANKAYA',
                'siraNo' => 1,
            ],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::FAILURE);
});

it('handles flat response structure (no return wrapper)', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'BARNO' => 'KP02444444444',
        'sonucKodu' => 10,
        'dongu' => (object) [
            'IKODU' => '100',
            'ISLEM' => 'Teslim Edildi',
            'ITARIH' => '22/03/2026',
            'ISAAT' => '14:00:00',
            'IMERK' => 'ANKARA',
            'siraNo' => 1,
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue();
    $info = $response->getTrackingInfo();
    expect($info->trackingNumber)->toBe('KP02444444444')
        ->and($info->status)->toBe(ShipmentStatus::DELIVERED);
});

it('parses error response (query failed)', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'sonucKodu' => 0,
            'sonucAciklama' => 'Barkod bulunamadı',
        ],
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('Barkod bulunamadı')
        ->and($response->getCode())->toBe('0');
});

it('returns unknown status for unmapped IKODU', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'BARNO' => 'KP02555555555',
            'sonucKodu' => 10,
            'dongu' => (object) [
                'IKODU' => '9999',
                'ISLEM' => 'Unknown Event',
                'ITARIH' => '22/03/2026',
                'ISAAT' => '10:00:00',
                'IMERK' => '',
                'siraNo' => 1,
            ],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::UNKNOWN)
        ->and($info->events[0]->location)->toBeNull();
});

it('maps all known key status codes', function () {
    expect(GetTrackingStatusResponse::mapStatus(1))->toBe(ShipmentStatus::PICKED_UP)
        ->and(GetTrackingStatusResponse::mapStatus(77))->toBe(ShipmentStatus::IN_TRANSIT)
        ->and(GetTrackingStatusResponse::mapStatus(7))->toBe(ShipmentStatus::OUT_FOR_DELIVERY)
        ->and(GetTrackingStatusResponse::mapStatus(100))->toBe(ShipmentStatus::DELIVERED)
        ->and(GetTrackingStatusResponse::mapStatus(99))->toBe(ShipmentStatus::RETURNED)
        ->and(GetTrackingStatusResponse::mapStatus(120))->toBe(ShipmentStatus::RETURNED)
        ->and(GetTrackingStatusResponse::mapStatus(101))->toBe(ShipmentStatus::FAILURE)
        ->and(GetTrackingStatusResponse::mapStatus(2))->toBe(ShipmentStatus::CANCELLED)
        ->and(GetTrackingStatusResponse::mapStatus(12345))->toBe(ShipmentStatus::UNKNOWN);
});

it('parses YYYYMMDD date format', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'BARNO' => 'KP02666666666',
            'sonucKodu' => 10,
            'dongu' => (object) [
                'IKODU' => '1',
                'ISLEM' => 'Kabul Edildi',
                'ITARIH' => '20260322',
                'ISAAT' => '15:42:34',
                'IMERK' => 'ANKARA',
                'siraNo' => 1,
            ],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->events[0]->occurredAt->format('Y-m-d'))->toBe('2026-03-22');
});

it('returns empty events when no dongu', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'return' => (object) [
            'BARNO' => 'KP02777777777',
            'sonucKodu' => 10,
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->events)->toBeEmpty()
        ->and($info->status)->toBe(ShipmentStatus::UNKNOWN);
});

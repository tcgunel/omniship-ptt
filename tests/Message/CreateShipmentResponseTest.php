<?php

declare(strict_types=1);

use Omniship\PTT\Message\CreateShipmentResponse;

use function Omniship\PTT\Tests\createMockRequest;

it('parses successful response with return wrapper', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
            'dongu' => (object) [
                'barkod' => 'KP02123456789',
                'donguHataKodu' => 1,
                'donguSonuc' => true,
                'donguAciklama' => 'https://pttws.ptt.gov.tr/ReferansSorgu/faces/referansSorgu.xhtml?guid=abc123',
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('KP02123456789')
        ->and($response->getBarcode())->toBe('KP02123456789')
        ->and($response->getMessage())->toContain('pttws.ptt.gov.tr')
        ->and($response->getCode())->toBe('1')
        ->and($response->getTrackingUrl())->toContain('https://');
});

it('parses successful response with flat structure', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'hataKodu' => 1,
        'aciklama' => 'BASARILI',
        'dongu' => (object) [
            'barkod' => 'KP02987654321',
            'donguHataKodu' => 1,
            'donguSonuc' => true,
            'donguAciklama' => 'Success',
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('KP02987654321');
});

it('parses error response', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 0,
            'aciklama' => 'Hata oluştu',
        ],
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('Hata oluştu')
        ->and($response->getCode())->toBe('0');
});

it('handles dongu as array (batch response)', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
            'dongu' => [
                (object) [
                    'barkod' => 'KP02111111111',
                    'donguHataKodu' => 1,
                    'donguSonuc' => true,
                    'donguAciklama' => 'OK',
                ],
                (object) [
                    'barkod' => 'KP02222222222',
                    'donguHataKodu' => 1,
                    'donguSonuc' => true,
                    'donguAciklama' => 'OK',
                ],
            ],
        ],
    ]);

    // Returns first barcode from batch
    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('KP02111111111');
});

it('returns null tracking number when no dongu', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBeNull();
});

it('returns null tracking URL when donguAciklama is not a URL', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
            'dongu' => (object) [
                'barkod' => 'KP02123456789',
                'donguAciklama' => 'Not a URL',
            ],
        ],
    ]);

    expect($response->getTrackingUrl())->toBeNull();
});

it('returns raw data via getData()', function () {
    $rawData = (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
        ],
    ];

    $response = new CreateShipmentResponse(createMockRequest(), $rawData);

    expect($response->getData())->toBe($rawData);
});

it('returns null for label, charge, and currency', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
        ],
    ]);

    expect($response->getLabel())->toBeNull()
        ->and($response->getTotalCharge())->toBeNull()
        ->and($response->getCurrency())->toBeNull()
        ->and($response->getShipmentId())->toBeNull();
});

it('returns empty barcode when dongu has empty barkod', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
            'dongu' => (object) [
                'barkod' => '',
                'donguSonuc' => true,
            ],
        ],
    ]);

    expect($response->getTrackingNumber())->toBeNull();
});

<?php

declare(strict_types=1);

use Omniship\PTT\Message\CancelShipmentResponse;

use function Omniship\PTT\Tests\createMockRequest;

it('parses successful cancellation with return wrapper', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => '1 adet kayit silindi.',
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue()
        ->and($response->getMessage())->toBe('1 adet kayit silindi.')
        ->and($response->getCode())->toBe('1');
});

it('parses successful cancellation with flat structure', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'hataKodu' => 1,
        'aciklama' => '1 adet kayit silindi.',
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue();
});

it('parses failed cancellation', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => 0,
            'aciklama' => 'Kayit bulunamadi.',
        ],
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->isCancelled())->toBeFalse()
        ->and($response->getMessage())->toBe('Kayit bulunamadi.')
        ->and($response->getCode())->toBe('0');
});

it('parses error with hataKodu -1', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'return' => (object) [
            'hataKodu' => -1,
            'aciklama' => 'Sistem hatası.',
        ],
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->isCancelled())->toBeFalse();
});

it('returns raw data via getData()', function () {
    $rawData = (object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
        ],
    ];

    $response = new CancelShipmentResponse(createMockRequest(), $rawData);

    expect($response->getData())->toBe($rawData);
});

it('handles null data gracefully', function () {
    $response = new CancelShipmentResponse(createMockRequest(), new \stdClass());

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->isCancelled())->toBeFalse()
        ->and($response->getMessage())->toBeNull()
        ->and($response->getCode())->toBeNull();
});

<?php

declare(strict_types=1);

use Omniship\PTT\Message\GetTrackingStatusRequest;
use Omniship\PTT\Message\GetTrackingStatusResponse;

use function Omniship\PTT\Tests\createMockSoapClient;
use function Omniship\PTT\Tests\createMockSoapClientWithResponse;

beforeEach(function () {
    $this->request = new GetTrackingStatusRequest(createMockSoapClient());
    $this->request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'trackingNumber' => 'KP02123456789',
    ]);
});

it('builds correct SOAP data', function () {
    $data = $this->request->getData();

    expect($data)->toHaveKey('input')
        ->and($data['input']['musteri_no'])->toBe('999999999')
        ->and($data['input']['sifre'])->toBe('testpass')
        ->and($data['input']['barkod'])->toBe('KP02123456789');
});

it('uses barkodSorgu SOAP method', function () {
    $reflection = new ReflectionMethod($this->request, 'getSoapMethod');

    expect($reflection->invoke($this->request))->toBe('barkodSorgu');
});

it('throws when tracking number is missing', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('throws when musteriId is missing', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'sifre' => 'testpass',
        'trackingNumber' => 'KP02123456789',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns GetTrackingStatusResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'return' => (object) [
            'BARNO' => 'KP02123456789',
            'ALICI' => 'Mehmet Demir',
            'sonucKodu' => 10,
            'dongu' => (object) [
                'IKODU' => '1',
                'ISLEM' => 'Kabul Edildi',
                'ITARIH' => '23/03/2023',
                'ISAAT' => '15:42:34',
                'IMERK' => 'ANKARA/GENEL MÜDÜRLÜK',
                'siraNo' => 1,
            ],
        ],
    ]);

    $request = new GetTrackingStatusRequest($soapClient);
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'trackingNumber' => 'KP02123456789',
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(GetTrackingStatusResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});

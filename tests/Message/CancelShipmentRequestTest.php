<?php

declare(strict_types=1);

use Omniship\PTT\Message\CancelShipmentRequest;
use Omniship\PTT\Message\CancelShipmentResponse;

use function Omniship\PTT\Tests\createMockSoapClient;
use function Omniship\PTT\Tests\createMockSoapClientWithResponse;

beforeEach(function () {
    $this->request = new CancelShipmentRequest(createMockSoapClient());
    $this->request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'trackingNumber' => 'KP02123456789',
        'dosyaAdi' => 'TEST-20260312',
    ]);
});

it('builds correct SOAP data', function () {
    $data = $this->request->getData();

    expect($data)->toHaveKey('inpDelete')
        ->and($data['inpDelete']['barcode'])->toBe('KP02123456789')
        ->and($data['inpDelete']['dosyaAdi'])->toBe('TEST-20260312')
        ->and($data['inpDelete']['musteriId'])->toBe('999999999')
        ->and($data['inpDelete']['sifre'])->toBe('testpass');
});

it('uses barkodVeriSil SOAP method', function () {
    $reflection = new ReflectionMethod($this->request, 'getSoapMethod');

    expect($reflection->invoke($this->request))->toBe('barkodVeriSil');
});

it('defaults dosyaAdi to empty string when not set', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'trackingNumber' => 'KP02123456789',
    ]);

    $data = $request->getData();

    expect($data['inpDelete']['dosyaAdi'])->toBe('');
});

it('throws when tracking number is missing', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('throws when musteriId is missing', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'sifre' => 'testpass',
        'trackingNumber' => 'KP02123456789',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CancelShipmentResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => '1 adet kayit silindi.',
        ],
    ]);

    $request = new CancelShipmentRequest($soapClient);
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'trackingNumber' => 'KP02123456789',
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(CancelShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});

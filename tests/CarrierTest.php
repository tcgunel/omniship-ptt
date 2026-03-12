<?php

declare(strict_types=1);

use Omniship\PTT\Carrier;
use Omniship\PTT\Message\CancelShipmentRequest;
use Omniship\PTT\Message\CreateShipmentRequest;
use Omniship\PTT\Message\GetTrackingStatusRequest;

use function Omniship\PTT\Tests\createMockSoapClient;

beforeEach(function () {
    $this->carrier = new Carrier(createMockSoapClient());
    $this->carrier->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'testMode' => true,
    ]);
});

it('has the correct name', function () {
    expect($this->carrier->getName())->toBe('PTT Kargo');
    expect($this->carrier->getShortName())->toBe('PTT');
});

it('has correct default parameters', function () {
    $carrier = new Carrier(createMockSoapClient());
    $carrier->initialize();

    expect($carrier->getMusteriId())->toBe('')
        ->and($carrier->getSifre())->toBe('')
        ->and($carrier->getTestMode())->toBeFalse();
});

it('initializes with custom parameters', function () {
    expect($this->carrier->getMusteriId())->toBe('999999999')
        ->and($this->carrier->getSifre())->toBe('testpass')
        ->and($this->carrier->getTestMode())->toBeTrue();
});

it('returns test WSDL URL in test mode', function () {
    $reflection = new ReflectionMethod($this->carrier, 'getWsdlUrl');

    expect($reflection->invoke($this->carrier))
        ->toContain('PttVeriYuklemeTest');
});

it('returns production WSDL URL in production mode', function () {
    $this->carrier->setTestMode(false);
    $reflection = new ReflectionMethod($this->carrier, 'getWsdlUrl');

    expect($reflection->invoke($this->carrier))
        ->toContain('PttVeriYukleme/services/Sorgu')
        ->not->toContain('Test');
});

it('supports createShipment method', function () {
    expect($this->carrier->supports('createShipment'))->toBeTrue();
});

it('supports getTrackingStatus method', function () {
    expect($this->carrier->supports('getTrackingStatus'))->toBeTrue();
});

it('supports cancelShipment method', function () {
    expect($this->carrier->supports('cancelShipment'))->toBeTrue();
});

it('creates a CreateShipmentRequest', function () {
    $request = $this->carrier->createShipment([
        'barcode' => 'KP02123456789',
    ]);

    expect($request)->toBeInstanceOf(CreateShipmentRequest::class);
});

it('creates a GetTrackingStatusRequest', function () {
    $request = $this->carrier->getTrackingStatus([
        'trackingNumber' => 'KP02123456789',
    ]);

    expect($request)->toBeInstanceOf(GetTrackingStatusRequest::class);
});

it('creates a CancelShipmentRequest', function () {
    $request = $this->carrier->cancelShipment([
        'trackingNumber' => 'KP02123456789',
    ]);

    expect($request)->toBeInstanceOf(CancelShipmentRequest::class);
});

it('sets and gets musteriId', function () {
    $this->carrier->setMusteriId('123456789');

    expect($this->carrier->getMusteriId())->toBe('123456789');
});

it('sets and gets sifre', function () {
    $this->carrier->setSifre('newpassword');

    expect($this->carrier->getSifre())->toBe('newpassword');
});

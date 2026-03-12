<?php

declare(strict_types=1);

use Omniship\Common\Address;
use Omniship\Common\Enum\PaymentType;
use Omniship\Common\Package;
use Omniship\PTT\Message\CreateShipmentRequest;
use Omniship\PTT\Message\CreateShipmentResponse;

use function Omniship\PTT\Tests\createMockSoapClient;
use function Omniship\PTT\Tests\createMockSoapClientWithResponse;

beforeEach(function () {
    $this->request = new CreateShipmentRequest(createMockSoapClient());
    $this->request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'barcode' => 'KP02123456789',
        'dosyaAdi' => 'TEST-20260312',
        'shipFrom' => new Address(
            name: 'Ahmet Yılmaz',
            street1: 'Atatürk Cad. No:42',
            city: 'İstanbul',
            district: 'Kadıköy',
            postalCode: '34710',
            country: 'TR',
            phone: '+905551234567',
        ),
        'shipTo' => new Address(
            name: 'Mehmet Demir',
            street1: 'Kızılay Mah. 123. Sok. No:5',
            city: 'Ankara',
            district: 'Çankaya',
            postalCode: '06420',
            country: 'TR',
            phone: '05559876543',
        ),
        'packages' => [
            new Package(weight: 2.5, length: 30, width: 20, height: 15),
        ],
    ]);
});

it('builds correct SOAP data structure', function () {
    $data = $this->request->getData();

    expect($data)->toHaveKey('input')
        ->and($data['input']['musteriId'])->toBe('999999999')
        ->and($data['input']['sifre'])->toBe('testpass')
        ->and($data['input']['kullanici'])->toBe('PttWs')
        ->and($data['input']['gonderiTip'])->toBe('NORMAL')
        ->and($data['input']['gonderiTur'])->toBe('KARGO')
        ->and($data['input']['dosyaAdi'])->toBe('TEST-20260312')
        ->and($data['input']['dongu'])->toBeArray()
        ->and($data['input']['dongu'])->toHaveCount(1);
});

it('builds correct shipment data', function () {
    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['aliciAdi'])->toBe('Mehmet Demir')
        ->and($dongu['aliciIlAdi'])->toBe('Ankara')
        ->and($dongu['aliciIlceAdi'])->toBe('Çankaya')
        ->and($dongu['barkodNo'])->toBe('KP02123456789')
        ->and($dongu['aAdres'])->toBe('Kızılay Mah. 123. Sok. No:5');
});

it('formats phone number correctly', function () {
    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    // +905559876543 → 5559876543
    expect($dongu['aliciSms'])->toBe('5559876543');
});

it('calculates weight in grams', function () {
    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    // 2.5 kg → 2500 grams
    expect($dongu['agirlik'])->toBe(2500);
});

it('calculates desi from dimensions', function () {
    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    // (30 * 20 * 15) / 3000 = 3.0
    expect($dongu['desi'])->toBe(3.0);
});

it('includes sender info in gondericibilgi', function () {
    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['gondericibilgi'])->toBeArray()
        ->and($dongu['gondericibilgi']['gonderici_adi'])->toBe('Ahmet Yılmaz')
        ->and($dongu['gondericibilgi']['gonderici_il_ad'])->toBe('İstanbul')
        ->and($dongu['gondericibilgi']['gonderici_ilce_ad'])->toBe('Kadıköy')
        ->and($dongu['gondericibilgi']['gonderici_ulke_id'])->toBe('052');
});

it('concatenates street1 and street2 for address', function () {
    $this->request->setShipTo(new Address(
        name: 'Test',
        street1: 'Line 1',
        street2: 'Line 2',
        city: 'Istanbul',
        district: 'Kadikoy',
        phone: '05551234567',
    ));

    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['aAdres'])->toBe('Line 1 Line 2');
});

it('includes COD fields when cash on delivery is set', function () {
    $this->request->setCashOnDelivery(true);
    $this->request->setCodAmount(150.50);

    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['odeme_sart_ucreti'])->toBe(150.50)
        ->and($dongu['ekhizmet'])->toContain('OS');
});

it('has zero COD when not set', function () {
    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['odeme_sart_ucreti'])->toBe(0);
});

it('includes insurance fields', function () {
    $this->request->setInsuredValue(500.00);

    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['deger_ucreti'])->toBe(500.00)
        ->and($dongu['ekhizmet'])->toContain('DK');
});

it('maps payment type to PTT odemesekli', function () {
    $this->request->setPaymentType(PaymentType::RECEIVER);

    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['odemesekli'])->toBe('UA');
});

it('maps sender payment type', function () {
    $this->request->setPaymentType(PaymentType::SENDER);

    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['odemesekli'])->toBe('MH');
});

it('auto-generates dosyaAdi when not provided', function () {
    // Create a fresh request without dosyaAdi
    $request = new CreateShipmentRequest(createMockSoapClient());
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'barcode' => 'KP02123456789',
        'shipFrom' => new Address(
            name: 'Sender',
            street1: 'Address',
            city: 'Istanbul',
            district: 'Kadikoy',
            phone: '5551234567',
        ),
        'shipTo' => new Address(
            name: 'Receiver',
            street1: 'Address',
            city: 'Ankara',
            district: 'Cankaya',
            phone: '5559876543',
        ),
        'packages' => [new Package(weight: 1.0)],
    ]);

    $data = $request->getData();

    expect($data['input']['dosyaAdi'])->toStartWith('PTT-');
});

it('sets and gets reference number', function () {
    $this->request->setReferenceNumber('REF-123');

    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    expect($dongu['musteriReferansNo'])->toBe('REF-123');
});

it('throws when required parameters are missing', function () {
    $request = new CreateShipmentRequest(createMockSoapClient());
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CreateShipmentResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'return' => (object) [
            'hataKodu' => 1,
            'aciklama' => 'BASARILI',
            'dongu' => (object) [
                'barkod' => 'KP02123456789',
                'donguHataKodu' => 1,
                'donguSonuc' => true,
                'donguAciklama' => 'https://pttws.ptt.gov.tr/ReferansSorgu/faces/referansSorgu.xhtml?musteri_no=999999999',
            ],
        ],
    ]);

    $request = new CreateShipmentRequest($soapClient);
    $request->initialize([
        'musteriId' => '999999999',
        'sifre' => 'testpass',
        'barcode' => 'KP02123456789',
        'dosyaAdi' => 'TEST-20260312',
        'shipFrom' => new Address(
            name: 'Sender',
            street1: 'Address',
            city: 'Istanbul',
            district: 'Kadikoy',
            phone: '5551234567',
        ),
        'shipTo' => new Address(
            name: 'Receiver',
            street1: 'Address',
            city: 'Ankara',
            district: 'Cankaya',
            phone: '5559876543',
        ),
        'packages' => [new Package(weight: 1.0)],
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(CreateShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('KP02123456789');
});

it('handles multiple packages', function () {
    $this->request->setPackages([
        new Package(weight: 1.0, length: 20, width: 10, height: 10),
        new Package(weight: 2.0, length: 30, width: 20, height: 15),
    ]);

    $data = $this->request->getData();
    $dongu = $data['input']['dongu'][0];

    // Total weight: 3.0 kg = 3000 grams
    expect($dongu['agirlik'])->toBe(3000);
});

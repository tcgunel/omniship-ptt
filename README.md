# Omniship PTT Kargo

PTT Kargo carrier driver for the [Omniship](https://github.com/tcgunel/omniship) shipping library.

Uses the PTT SOAP web services with customer ID/password authentication.

## Installation

```bash
composer require tcgunel/omniship-ptt
```

## Usage

### Initialize

```php
use Omniship\Omniship;

$carrier = Omniship::create('PTT');
$carrier->initialize([
    'musteriId' => 'your-customer-id',
    'sifre' => 'your-password',
    'testMode' => true, // false for production
]);
```

### Create Shipment

PTT requires a **pre-allocated barcode** for each shipment.

```php
use Omniship\Common\Address;
use Omniship\Common\Package;
use Omniship\Common\Enum\PaymentType;

$response = $carrier->createShipment([
    'shipFrom' => new Address(
        name: 'Ahmet Yilmaz',
        street1: 'Ataturk Cad. No:42',
        city: 'Istanbul',
        district: 'Kadikoy',
        postalCode: '34710',
        country: 'TR',
        phone: '05551234567',
        email: 'sender@example.com',
    ),
    'shipTo' => new Address(
        name: 'Mehmet Demir',
        street1: 'Kizilirmak Cad. No:5',
        city: 'Ankara',
        district: 'Cankaya',
        postalCode: '06420',
        country: 'TR',
        phone: '05559876543',
    ),
    'packages' => [
        new Package(weight: 2.5, length: 30, width: 20, height: 15),
    ],
    'barcode' => 'PTT0000012345678',  // required - pre-allocated barcode
    'paymentType' => PaymentType::SENDER,
    'cashOnDelivery' => false,
    'codAmount' => 0.0,
    'referenceNumber' => 'ORDER-001', // optional
    'insuredValue' => 0.0,            // optional - declared value
])->send();

if ($response->isSuccessful()) {
    echo $response->getTrackingNumber(); // barcode number
    echo $response->getBarcode();        // same as tracking number
    echo $response->getShipmentId();     // PTT shipment ID
} else {
    echo $response->getMessage(); // error description
    echo $response->getCode();    // PTT error code
}
```

### Track Shipment

```php
$response = $carrier->getTrackingStatus([
    'trackingNumber' => 'PTT0000012345678',
])->send();

if ($response->isSuccessful()) {
    $info = $response->getTrackingInfo();
    echo $info->trackingNumber;
    echo $info->status->value;  // "delivered", "in_transit", etc.
    echo $info->carrier;        // "PTT Kargo"

    foreach ($info->events as $event) {
        echo $event->description;
        echo $event->occurredAt->format('Y-m-d H:i');
        echo $event->location;
    }
}
```

### Cancel Shipment

```php
$response = $carrier->cancelShipment([
    'trackingNumber' => 'PTT0000012345678',
])->send();

if ($response->isCancelled()) {
    echo 'Shipment cancelled';
}
```

## Payment Types

| PTT Code | Description | Omniship Enum |
|----------|-------------|---------------|
| `MH` | Mahsup (sender pays) | `PaymentType::SENDER` |
| `UA` | Ucreti Alicidan (receiver pays) | `PaymentType::RECEIVER` |
| `N` | Nakit (third party) | `PaymentType::THIRD_PARTY` |

## Extra Services

PTT supports extra service codes appended as a string:

| Code | Description |
|------|-------------|
| `OS` | Cash on delivery (Odeme Sartli) |
| `DK` | Declared value (Deger Konulmus) |

## API Details

- **Transport**: SOAP/XML via PHP SoapClient
- **Auth**: `musteriId` + `sifre` in request body
- **Create/Cancel Service**: `https://pttws.ptt.gov.tr/PttVeriYukleme/services/Sorgu?wsdl`
- **Tracking Service**: `https://pttws.ptt.gov.tr/GonderiHareketV2/services/Sorgu?wsdl`
- **Test WSDLs**: Append `Test` to service name (e.g. `PttVeriYuklemeTest`)

### SOAP Methods

| Operation | Service | SOAP Method |
|-----------|---------|-------------|
| Create | PttVeriYukleme | `kabulEkle2` |
| Track | GonderiHareketV2 | `gonderiDurum` |
| Cancel | PttVeriYukleme | `gonderiIptal` |

## Testing

```bash
vendor/bin/pest
```

## License

MIT

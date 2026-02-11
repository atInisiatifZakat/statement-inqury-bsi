# BSI Integration Package

A Laravel package for integrating with Bank BSI API for statement inquiry operations.

## Installation

```bash
composer require inisiatif/statement-inquiry-bsi
```

## Configuration

Add your BSI API configuration to `config/services.php`:

```php
'bsi_api' => [
    'env' => env('BSI_ENV', 'production'),
    'api_key' => env('BSI_API_KEY'),
    'cust_id' => env('BSI_CUST_ID'),
    'user_id' => env('BSI_USER_ID'),
    'password' => env('BSI_PASSWORD'),
    'sandbox_url' => env('BSI_SANDBOX_URL'),
    'production_url' => env('BSI_PRODUCTION_URL'),
    'channel_id' => env('BSI_CHANNEL_ID', 'API'),
    'verify_ssl' => env('BSI_VERIFY_SSL', true), // Use false for self-signed certificates
],
```

### SSL Certificate Configuration

For development environments with self-signed certificates, you can disable SSL verification by setting `BSI_VERIFY_SSL=false` in your `.env` file:

```env
BSI_VERIFY_SSL=false
```

> **⚠️ Warning**: Only disable SSL verification in development environments. Always use proper SSL certificates in production.

## Usage

### Inject the BsiClient

```php
use Inisiatif\Bsi\BsiClient;

public function someMethod(BsiClient $bsiClient)
{
    // Get account statement
    $statement = $bsiClient->getAccountStatement(
        accountNumber: '1234567890',
        from: now()->subDays(30),
        to: now()
    );
    
    // Get balance information
    $balance = $bsiClient->getInformationBalance('1234567890');
}
```

## Requirements

- PHP 8.1+
- Laravel 11.0+
- PSR Logger

## License

Proprietary

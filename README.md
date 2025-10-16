# Laravel Pardakht

[![Latest Version](https://img.shields.io/packagist/v/fiachehr/laravel-pardakht.svg?style=flat-square)](https://packagist.org/packages/fiachehr/laravel-pardakht)
[![Total Downloads](https://img.shields.io/packagist/dt/fiachehr/laravel-pardakht.svg?style=flat-square)](https://packagist.org/packages/fiachehr/laravel-pardakht)
[![License](https://img.shields.io/packagist/l/fiachehr/laravel-pardakht.svg?style=flat-square)](LICENSE)
[![Tests](https://img.shields.io/github/workflow/status/fiachehr/laravel-pardakht/Tests?label=tests&style=flat-square)](https://github.com/fiachehr/laravel-pardakht/actions)

Professional Laravel payment gateway package for Iranian payment providers with full SOLID principles and Clean Architecture.

## Table of Contents

- [Supported Gateways](#supported-gateways)
- [Key Features](#key-features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Testing](#testing)
- [Architecture](#architecture)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)

## Supported Gateways

- ✅ **Bank Mellat** (Bank-e Mellat) - SOAP
- ✅ **Mabna Card** (Sepehr) - REST API
- ✅ **ZarinPal** - REST API

## Key Features

### Architecture & Design
- 🏗️ **SOLID Principles** - Full adherence to SOLID principles
- 🎨 **Clean Architecture** - Clean and maintainable architecture
- 🎯 **Design Patterns** - Repository, Factory, Facade patterns
- 🧩 **Contract-Based** - Interface-driven programming

### Capabilities
- 📦 **Auto Storage** - Automatic transaction storage in database
- 🔄 **Easy Switching** - Switch between gateways easily
- 🛡️ **Error Handling** - Professional exception handling
- ✨ **Value Objects** - Type-safe value objects
- 🧪 **Test Coverage** - Comprehensive unit and feature tests
- 🔌 **Extensible** - Easy to add custom gateways
- 🌐 **Sandbox Support** - Full sandbox/test environment support

### Developer Experience
- 📝 **Full Documentation** - Comprehensive docs with examples
- 🎓 **Educational** - Self-documented, readable code
- 🔧 **Simple Config** - Easy configuration via .env

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- PHP SOAP extension (for Mellat gateway)
- PHP JSON extension

## Installation

### 1. Install via Composer

```bash
composer require fiachehr/laravel-pardakht
```

### 2. Publish Assets

```bash
# Publish all (config + migrations)
php artisan vendor:publish --provider="Fiachehr\Pardakht\PardakhtServiceProvider"
```

Or separately:

```bash
# Config only
php artisan vendor:publish --tag=pardakht-config

# Migrations only
php artisan vendor:publish --tag=pardakht-migrations
```

### 3. Run Migrations

```bash
php artisan migrate
```

This creates the `pardakht_transactions` table for storing transactions.

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Default gateway
PARDAKHT_DEFAULT_GATEWAY=mellat

# ========================================
# Bank Mellat (Production)
# ========================================
MELLAT_TERMINAL_ID=your_terminal_id
MELLAT_USERNAME=your_username
MELLAT_PASSWORD=your_password
MELLAT_CALLBACK_URL=https://yoursite.com/payment/callback
MELLAT_SANDBOX=false

# ========================================
# Mabna Card / Sepehr (Production)
# ========================================
MABNA_TERMINAL_ID=your_terminal_id
MABNA_CALLBACK_URL=https://yoursite.com/payment/callback
MABNA_SANDBOX=false

# ========================================
# ZarinPal (Production)
# ========================================
ZARINPAL_MERCHANT_ID=your_merchant_id
ZARINPAL_CALLBACK_URL=https://yoursite.com/payment/callback
ZARINPAL_SANDBOX=false
ZARINPAL_DESCRIPTION="Payment via ZarinPal"
```

### Sandbox Mode (Testing)

For development and testing, enable sandbox mode:

```env
# Bank Mellat - Test Mode
MELLAT_SANDBOX=true
MELLAT_TERMINAL_ID=test_terminal_id
MELLAT_USERNAME=test_username
MELLAT_PASSWORD=test_password

# Mabna - Test Mode
MABNA_SANDBOX=true
MABNA_TERMINAL_ID=test_terminal_id

# ZarinPal - Test Mode
ZARINPAL_SANDBOX=true
ZARINPAL_MERCHANT_ID=test_merchant_id
```

**Get Test Credentials:**
- For Bank Mellat and Mabna: [BankTest.ir](https://banktest.ir)
- For ZarinPal: [ZarinPal Sandbox](https://sandbox.zarinpal.com)

### Advanced Configuration

The `config/pardakht.php` file:

```php
return [
    // Default gateway
    'default' => env('PARDAKHT_DEFAULT_GATEWAY', 'mellat'),
    
    // Auto-store transactions
    'store_transactions' => true,
    
    // Gateway configurations
    'gateways' => [
        'mellat' => [
            'driver' => 'mellat',
            'terminal_id' => env('MELLAT_TERMINAL_ID'),
            'username' => env('MELLAT_USERNAME'),
            'password' => env('MELLAT_PASSWORD'),
            'callback_url' => env('MELLAT_CALLBACK_URL'),
            'sandbox' => env('MELLAT_SANDBOX', false),
        ],
        // ...
    ],
];
```

## Usage

### 1. Create Payment Request

```php
use Fiachehr\Pardakht\Facades\Pardakht;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;

public function payment()
{
    // Create payment request
    $paymentRequest = new PaymentRequest(
        amount: 100000,              // Amount in Rials
        orderId: 'ORDER-12345',      // Order ID
        callbackUrl: route('payment.callback'),
        description: 'Order payment #12345',
        mobile: '09123456789',       // Optional
        email: 'user@example.com',   // Optional
        metadata: [                  // Optional
            'user_id' => auth()->id(),
            'product_id' => 5
        ]
    );

    try {
        // Send request to default gateway
        $response = Pardakht::request($paymentRequest);
        
        // Or use specific gateway
        // $response = Pardakht::request($paymentRequest, 'zarinpal');
        
        if ($response->isSuccessful()) {
            // Store tracking code in session
            session(['payment_tracking_code' => $response->trackingCode]);
            
            // Redirect user to payment gateway
            return redirect($response->getPaymentUrl());
        }
        
    } catch (\Fiachehr\Pardakht\Exceptions\GatewayException $e) {
        // Handle error
        \Log::error('Payment request failed', [
            'gateway' => $e->getGatewayName(),
            'message' => $e->getMessage(),
            'code' => $e->getGatewayCode()
        ]);
        
        return back()->with('error', 'Payment request failed: ' . $e->getMessage());
    }
}
```

### 2. Verify Payment (Callback)

```php
use Fiachehr\Pardakht\Facades\Pardakht;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Illuminate\Http\Request;

public function callback(Request $request)
{
    // Get tracking code from session
    $trackingCode = session('payment_tracking_code');
    
    if (!$trackingCode) {
        return redirect()->route('payment.failed')
            ->with('error', 'Payment information not found');
    }
    
    // Create verification request
    $verificationRequest = new VerificationRequest(
        trackingCode: $trackingCode,
        gatewayData: $request->all() // All data returned from gateway
    );
    
    try {
        // Verify payment
        $response = Pardakht::verify($verificationRequest);
        
        // Or specify gateway
        // $response = Pardakht::verify($verificationRequest, 'mellat');
        
        if ($response->isSuccessful()) {
            // Payment successful - perform required operations
            
            // Example: Update order status
            $order = Order::where('id', $orderId)->first();
            $order->update([
                'status' => 'paid',
                'payment_reference' => $response->referenceId,
                'paid_at' => now()
            ]);
            
            // Clear session
            session()->forget('payment_tracking_code');
            
            return view('payment.success', [
                'referenceId' => $response->referenceId,
                'cardNumber' => $response->getMaskedCardNumber(),
                'amount' => $response->amount,
                'transactionId' => $response->transactionId,
            ]);
        }
        
    } catch (\Fiachehr\Pardakht\Exceptions\GatewayException $e) {
        \Log::error('Payment verification failed', [
            'gateway' => $e->getGatewayName(),
            'message' => $e->getMessage(),
            'code' => $e->getGatewayCode()
        ]);
        
        return view('payment.failed', [
            'message' => $e->getMessage(),
            'code' => $e->getGatewayCode(),
        ]);
    }
}
```

### 3. Advanced Usage

#### Working with Multiple Gateways

```php
// Get list of available gateways
$gateways = Pardakht::available();
// ['mellat', 'mabna', 'zarinpal']

// Get specific gateway instance
$mellatGateway = Pardakht::gateway('mellat');
$zarinpalGateway = Pardakht::gateway('zarinpal');

// Use gateway directly
$response = $mellatGateway->request($paymentRequest);
```

#### Working with Transactions via Repository

```php
use Fiachehr\Pardakht\Contracts\TransactionRepositoryInterface;

class PaymentController extends Controller
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}
    
    public function history()
    {
        // Get successful transactions
        $successful = $this->transactionRepository->getSuccessful();
        
        // Get failed transactions
        $failed = $this->transactionRepository->getFailed();
        
        // Find by tracking code
        $transaction = $this->transactionRepository->findByTrackingCode($trackingCode);
        
        // Find by order ID
        $transaction = $this->transactionRepository->findByOrderId($orderId);
    }
}
```

#### Using Transaction Model

```php
use Fiachehr\Pardakht\Models\Transaction;

// Get successful transactions for specific gateway
$transactions = Transaction::gateway('mellat')
    ->successful()
    ->latest()
    ->get();

// Get pending transactions
$pending = Transaction::pending()->get();

// Filter by date
$transactions = Transaction::whereDate('created_at', today())
    ->successful()
    ->get();

// Check transaction status
$transaction = Transaction::find(1);
if ($transaction->isSuccessful()) {
    // Transaction was successful
}
```

#### Adding Custom Gateway

```php
use Fiachehr\Pardakht\Facades\Pardakht;
use Fiachehr\Pardakht\Gateways\AbstractGateway;

class CustomGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'custom';
    }
    
    public function request(PaymentRequest $request): PaymentResponse
    {
        // Implement payment request
    }
    
    public function verify(VerificationRequest $request): VerificationResponse
    {
        // Implement payment verification
    }
    
    protected function validateConfig(): void
    {
        // Validate configuration
    }
}

// Register custom gateway
Pardakht::extend('custom', CustomGateway::class);
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run only unit tests
vendor/bin/phpunit --testsuite=Unit

# Run only feature tests
vendor/bin/phpunit --testsuite=Feature
```

### Available Tests

- ✅ Value Objects Tests
- ✅ Gateway Manager Tests
- ✅ Transaction Model Tests
- ✅ Exception Handling Tests
- ✅ Facade Tests
- ✅ Repository Tests

## Security

### Reporting Security Vulnerabilities

If you discover a security vulnerability, please email fiachehr@example.com.

### Best Practices

- ✅ Always store credentials in `.env`
- ✅ Never commit credentials
- ✅ Always use SSL in production
- ✅ Ensure `SANDBOX=false` in production
- ✅ Review payment logs regularly

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Contribution Guidelines

- Code must follow PSR-12
- Add necessary tests
- Update documentation
- Record changes in CHANGELOG.md

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full changelog.

## License

This package is open-sourced software licensed under the MIT license. See [LICENSE](LICENSE) for details.

## Author

**Fiachehr**
- GitHub: [@fiachehr](https://github.com/fiachehr)
- Email: mailbox@fiachehr.ir

## Acknowledgments

- Laravel community
- Payment gateway developers
- All contributors

## Useful Links

- [Laravel Documentation](https://laravel.com/docs)
- [BankTest.ir](https://banktest.ir) - Bank gateway testing environment
- [ZarinPal Documentation](https://docs.zarinpal.com)

## FAQ

### How do I switch between gateways?

```php
// Method 1: Change default gateway in .env
PARDAKHT_DEFAULT_GATEWAY=zarinpal

// Method 2: Specify gateway at runtime
Pardakht::request($paymentRequest, 'zarinpal');
```

### How do I disable transaction storage?

```php
// In config/pardakht.php
'store_transactions' => false,
```

### How do I handle errors?

```php
try {
    $response = Pardakht::request($paymentRequest);
} catch (\Fiachehr\Pardakht\Exceptions\GatewayException $e) {
    // Gateway error
    $e->getMessage();
    $e->getGatewayName();
    $e->getGatewayCode();
} catch (\Exception $e) {
    // General error
}
```

---

<div align="center">

**If you find this package useful, give it a ⭐️!**

Made by Fiachehr with ❤️ for Laravel

</div>

# Order-Payment API

## Overview

This is a RESTful API for managing orders and payments, built with Laravel. The system implements a strategy pattern for payment gateways to ensure easy extensibility.

## Features

### Order Management
- Create orders with multiple items
- Update order details
- Delete orders (only if no payments are associated)
- View all orders with filtering by status (pending, confirmed, cancelled)
- Pagination for orders list

### Payment Management
- Process payments using different payment gateways
- View payment details
- Filter payments by order
- Payment gateway strategy pattern for easy extensibility

### Authentication & Security
- JWT-based authentication
- Endpoints for user registration and login
- Secure API endpoints

## Installation

### Requirements
- PHP 8.1 or higher
- Composer
- MySQL

### Setup Steps

1. Clone the repository
   ```bash
   git clone https://github.com/m7trfnet0/order-payment-api.git
   cd order-payment-api
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Set up environment variables
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure database in .env
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=order_payment_api
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Generate JWT secret
   ```bash
   php artisan jwt:secret
   ```

6. Run migrations
   ```bash
   php artisan migrate
   ```

7. (Optional) Seed the database
   ```bash
   php artisan db:seed
   ```

8. Start the development server
   ```bash
   php artisan serve
   ```

## Payment Gateway Extensibility

The system uses the Strategy Pattern to allow for easy addition of new payment gateways. Each payment gateway implements the `PaymentGatewayInterface` that defines the contract any gateway must fulfill.

### How to Add a New Payment Gateway

1. Create a new gateway class in `app/Services/PaymentGateway` directory, e.g., `NewGateway.php`
2. Extend the `AbstractPaymentGateway` class and implement required methods
3. Add the gateway to the `PaymentGatewayFactory` class
4. Add configuration for your gateway in `config/payment_gateways.php`

### Example Implementation

```php
<?php

namespace App\Services\PaymentGateway;

class NewGateway extends AbstractPaymentGateway
{
    /**
     * Process a payment.
     *
     * @param array $paymentData
     * @return array
     */
    public function processPayment(array $paymentData): array
    {
        // Implement payment processing logic
        $paymentId = $this->generatePaymentId();
        
        // Mock a successful payment
        $result = [
            'payment_id' => $paymentId,
            'status' => 'successful',
            'message' => 'Payment processed successfully',
            'transaction_details' => json_encode([
                'processor' => 'New Gateway',
                'timestamp' => now()->toIso8601String(),
                // Additional gateway-specific details
            ])
        ];
        
        $this->logTransaction(array_merge($paymentData, $result));
        
        return $result;
    }

    /**
     * Get payment status.
     *
     * @param string $paymentId
     * @return array
     */
    public function getPaymentStatus(string $paymentId): array
    {
        // Implement status check logic
        return [
            'payment_id' => $paymentId,
            'status' => 'successful',
            'message' => 'Payment is successful',
            'timestamp' => now()->toIso8601String()
        ];
    }
}
```

### Update PaymentGatewayFactory

```php
// In PaymentGatewayFactory.php, add your new gateway:
case 'new_gateway':
    return new NewGateway();
```

### Configure Gateway Settings

```php
// In config/payment_gateways.php
'new_gateway' => [
    'api_key' => env('NEW_GATEWAY_API_KEY', ''),
    'api_secret' => env('NEW_GATEWAY_API_SECRET', ''),
    'sandbox' => env('NEW_GATEWAY_SANDBOX', true),
],
```

## API Documentation

A Postman collection is included in the root directory: `Order-Payment-API.postman_collection.json`. Import this into Postman to explore all available endpoints with example requests.

### Main Endpoints

#### Authentication
- `POST /api/auth/register` - Register a new user
- `POST /api/auth/login` - Login and get JWT token
- `POST /api/auth/logout` - Logout and invalidate token
- `GET /api/auth/me` - Get authenticated user details

#### Orders
- `GET /api/orders` - List all orders
- `POST /api/orders` - Create a new order
- `GET /api/orders/{id}` - Get a specific order
- `PUT /api/orders/{id}` - Update an order
- `DELETE /api/orders/{id}` - Delete an order

#### Payments
- `GET /api/payments` - List all payments
- `GET /api/payments/{id}` - Get a specific payment
- `POST /api/payments/process` - Process a payment

## Testing

Run the automated tests with:

```bash
php artisan test
```

## Notes and Assumptions

1. The system simulates payment processing without actually interacting with real payment gateways
2. JWT authentication is used for API security
3. Orders can only be deleted if they have no associated payments
4. Payments can only be processed for orders in the 'confirmed' status
5. The strategy pattern allows for easy addition of new payment gateways

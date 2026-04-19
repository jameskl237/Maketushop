> ## Documentation Index
> Fetch the complete documentation index at: https://developer.notchpay.co/llms.txt
> Use this file to discover all available pages before exploring further.

# PHP SDK

> Integrate Notch Pay into your PHP applications

# NotchPay PHP Library

<Note>
  A PHP library to easily integrate the [NotchPay](https://notchpay.co/) API into your applications.
</Note>

## Installation

<Tabs>
  <Tab title="Composer">
    ```bash theme={null}
    composer require notchpay/notchpay-php
    ```
  </Tab>

  <Tab title="Manual Installation">
    ```php theme={null}
    // Download the latest release from GitHub
    // https://github.com/notchafrica/notchpay-php/releases

    // Include the autoloader
    require_once '/path/to/notchpay-php/autoload.php';
    ```
  </Tab>
</Tabs>

## Configuration

Before using the library, you need to configure your API key:

```php theme={null}
use NotchPay\NotchPay;

// Set your API key
NotchPay::setApiKey('b.xxxxxxx'); // Production API key
// or
NotchPay::setApiKey('sb.xxxxxxx'); // Sandbox API key

// Optional: Set a Private key for certain operations
NotchPay::setPrivateKey('private_key_here');

// Optional: Set a Sync ID for certain operations
NotchPay::setSyncId('sync_id_here');
```

## Payments

### Initialize a payment

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Payment;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $payment = Payment::initialize([
        'amount' => 5000,                // Amount according to currency format
        'email' => 'client@example.com', // Unique customer email
        'currency' => 'XAF',             // ISO currency code
        'callback' => 'https://example.com/callback', // Callback URL (optional)
        'reference' => 'order_123',      // Unique transaction reference
        'description' => 'Product purchase', // Description (optional)
        'channels' => ['mobile_money', 'card'], // Payment channels (optional)
        'metadata' => [                  // Metadata (optional)
            'customer_id' => '123',
            'order_id' => '456'
        ]
    ]);

    // Redirect user to payment URL
    header('Location: ' . $payment->authorization_url);
    exit();
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

For more details on parameters, see the [official documentation](https://developer.notchpay.co/#payment-initialize).

### Verify a payment

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Payment;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $reference = $_GET['reference']; // Get reference from callback URL
    $payment = Payment::verify($reference);

    if ($payment->transaction->status === 'complete') {
        // Payment was successful
        // Deliver product or service
    } else {
        // Payment is not yet completed or failed
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### List payments

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Payment;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $payments = Payment::all([
        'limit' => 20,           // Number of items per page (optional)
        'page' => 1,             // Page number (optional)
        'status' => 'complete',  // Filter by status (optional)
        'date_start' => '2023-01-01', // Start date (optional)
        'date_end' => '2023-12-31'    // End date (optional)
    ]);

    foreach ($payments->data as $payment) {
        echo $payment->reference . ' - ' . $payment->amount . ' ' . $payment->currency . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Cancel a payment

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Payment;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $reference = 'order_123';
    $result = Payment::cancel($reference);

    if ($result->status === 'success') {
        // Payment cancelled successfully
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Transfers

### Initialize a transfer

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Transfer;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $transfer = Transfer::initialize([
        'amount' => 5000,                // Amount according to currency format
        'currency' => 'XAF',             // ISO currency code
        'recipient' => [
            'name' => 'John Doe',
            'email' => 'recipient@example.com',
            'phone' => '+237600000000',
            'account' => '237600000000', // Account or phone number
            'provider' => 'mtn_momo'     // Provider (mtn_momo, orange_money, etc.)
        ],
        'description' => 'Salary payment', // Description (optional)
        'reference' => 'transfer_123',    // Unique reference (optional)
        'metadata' => [                   // Metadata (optional)
            'employee_id' => '123'
        ]
    ]);

    // Process response
    echo "Transfer initialized with reference: " . $transfer->reference;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Verify a transfer

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Transfer;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $reference = 'transfer_123';
    $transfer = Transfer::verify($reference);

    echo "Transfer status: " . $transfer->status;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### List transfers

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Transfer;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $transfers = Transfer::all([
        'limit' => 20,           // Number of items per page (optional)
        'page' => 1              // Page number (optional)
    ]);

    foreach ($transfers->data as $transfer) {
        echo $transfer->reference . ' - ' . $transfer->amount . ' ' . $transfer->currency . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Beneficiaries

### Create a beneficiary

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Beneficiary;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $beneficiary = Beneficiary::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+237600000000',
        'account' => '237600000000',
        'provider' => 'mtn_momo',
        'country' => 'CM',
        'currency' => 'XAF',
        'description' => 'Employee' // Optional
    ]);

    echo "Beneficiary created with ID: " . $beneficiary->id;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Retrieve a beneficiary

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Beneficiary;
NotchPay::setPrivateKey('private_key_here');

NotchPay::setApiKey('b.xxxxxxx');

try {
    $id = 'ben_123456';
    $beneficiary = Beneficiary::retrieve($id);

    echo "Beneficiary name: " . $beneficiary->name;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Update a beneficiary

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Beneficiary;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $id = 'ben_123456';
    $beneficiary = Beneficiary::update($id, [
        'name' => 'John Updated',
        'description' => 'New position'
    ]);

    echo "Beneficiary updated: " . $beneficiary->name;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### List beneficiaries

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Beneficiary;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $beneficiaries = Beneficiary::all([
        'limit' => 20,           // Number of items per page (optional)
        'page' => 1              // Page number (optional)
    ]);

    foreach ($beneficiaries->data as $beneficiary) {
        echo $beneficiary->name . ' - ' . $beneficiary->account . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Delete a beneficiary

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Beneficiary;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $id = 'ben_123456';
    $result = Beneficiary::delete($id);

    if ($result->status === 'success') {
        echo "Beneficiary deleted successfully";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Customers

### Create a customer

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');


try {
    $customer = Customer::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+237600000000',
        'metadata' => [           // Optional
            'age' => 30,
            'address' => 'Douala, Cameroon'
        ]
    ]);

    echo "Customer created with ID: " . $customer->id;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Retrieve a customer

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $id = 'cus_123456';
    $customer = Customer::retrieve($id);

    echo "Customer name: " . $customer->name;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Update a customer

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $id = 'cus_123456';
    $customer = Customer::update($id, [
        'name' => 'John Updated',
        'phone' => '+237611111111'
    ]);

    echo "Customer updated: " . $customer->name;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### List customers

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $customers = Customer::all([
        'limit' => 20,           // Number of items per page (optional)
        'page' => 1              // Page number (optional)
    ]);

    foreach ($customers->data as $customer) {
        echo $customer->name . ' - ' . $customer->email . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Block a customer

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $id = 'cus_123456';
    $result = Customer::block($id);

    if ($result->status === 'success') {
        echo "Customer blocked successfully";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Unblock a customer

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $id = 'cus_123456';
    $result = Customer::unblock($id);

    if ($result->status === 'success') {
        echo "Customer unblocked successfully";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Get customer payment methods

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $id = 'cus_123456';
    $paymentMethods = Customer::paymentMethods($id);

    foreach ($paymentMethods->data as $method) {
        echo $method->type . ' - ' . $method->last4 . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Get customer payments

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Customer;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $id = 'cus_123456';
    $payments = Customer::payments($id, [
        'limit' => 10,
        'page' => 1
    ]);

    foreach ($payments->data as $payment) {
        echo $payment->reference . ' - ' . $payment->amount . ' ' . $payment->currency . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Webhooks

### Create a webhook

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Webhook;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $webhook = Webhook::create([
        'url' => 'https://example.com/webhooks',
        'events' => ['payment.complete', 'payment.failed'],
        'description' => 'Webhook for payments', // Optional
        'active' => true // Optional
    ]);

    echo "Webhook created with ID: " . $webhook->id;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Retrieve a webhook

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Webhook;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $id = 'wh_123456';
    $webhook = Webhook::retrieve($id);

    echo "Webhook URL: " . $webhook->url;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Update a webhook

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Webhook;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $id = 'wh_123456';
    $webhook = Webhook::update($id, [
        'events' => ['payment.complete', 'payment.failed', 'transfer.complete'],
        'active' => true
    ]);

    echo "Webhook updated: " . $webhook->url;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### List webhooks

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Webhook;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $webhooks = Webhook::all();

    foreach ($webhooks->data as $webhook) {
        echo $webhook->url . ' - ' . ($webhook->active ? 'Active' : 'Inactive') . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Delete a webhook

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Webhook;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $id = 'wh_123456';
    $result = Webhook::delete($id);

    if ($result->status === 'success') {
        echo "Webhook deleted successfully";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Balance> ## Documentation Index
> Fetch the complete documentation index at: https://developer.notchpay.co/llms.txt
> Use this file to discover all available pages before exploring further.

# Payments API

> Create, retrieve, and manage payments with the Notch Pay API

# Payments API

<Note>
  The Payments API allows you to initialize, retrieve, and manage payment transactions. Use these endpoints to accept payments from your customers through various payment methods.
</Note>

## The Payment Object

<CodeGroup>
  ```json Payment Object theme={null}
  {
    "id": "pay_123456789",
    "reference": "order_123",
    "amount": 5000,
    "currency": "XAF",
    "status": "complete",
    "customer": "cus_123456789",
    "payment_method": "pm.ndzAfIh555VCPML1",
    "description": "Payment for Order #123",
    "metadata": {},
    "created_at": "2023-01-01T12:00:00Z",
    "completed_at": "2023-01-01T12:05:00Z"
  }
  ```
</CodeGroup>

### Payment Object Properties

<ResponseField name="id" type="string">
  Unique identifier for the payment
</ResponseField>

<ResponseField name="reference" type="string">
  Your custom reference for the payment
</ResponseField>

<ResponseField name="amount" type="number">
  Amount of the payment in the smallest currency unit
</ResponseField>

<ResponseField name="currency" type="string">
  Three-letter ISO currency code
</ResponseField>

<ResponseField name="status" type="string">
  Status of the payment: `pending`, `processing`, `complete`, `failed`, `canceled`, `expired`
</ResponseField>

<ResponseField name="customer" type="string">
  ID of the customer making the payment
</ResponseField>

<ResponseField name="payment_method" type="string">
  ID of the payment method used
</ResponseField>

<ResponseField name="description" type="string">
  Description of the payment
</ResponseField>

<ResponseField name="metadata" type="object">
  Additional data attached to the payment
</ResponseField>

<ResponseField name="created_at" type="string">
  Timestamp when the payment was created
</ResponseField>

<ResponseField name="completed_at" type="string">
  Timestamp when the payment was completed (if applicable)
</ResponseField>

## API Endpoints

<Card title="List All Payments" icon="list" color="#16A34A">
  <Tabs>
    <Tab title="Request">
      ```bash theme={null}
      GET /payments
      ```

      Retrieve a list of payments with pagination.

      ### Query Parameters

      <ParamField query="limit" type="integer">
        Number of items per page (default: 30, max: 100)
      </ParamField>

      <ParamField query="page" type="integer">
        Page number (default: 1)
      </ParamField>

      <ParamField query="search" type="string">
        Search by reference
      </ParamField>

      <ParamField query="status" type="string">
        Filter by status
      </ParamField>

      <ParamField query="channels" type="string">
        Filter by payment channels (comma-separated)
      </ParamField>

      <ParamField query="date_start" type="string">
        Start date filter (format: YYYY-MM-DD)
      </ParamField>

      <ParamField query="date_end" type="string">
        End date filter (format: YYYY-MM-DD)
      </ParamField>
    </Tab>

    <Tab title="Response">
      ```json theme={null}
      {
        "code": 200,
        "status": "OK",
        "message": "Payments retrieved",
        "totals": 50,
        "last_page": 2,
        "current_page": 1,
        "selected": 30,
        "items": [
          {
            "id": "pay_123456789",
            "reference": "order_123",
            "amount": 5000,
            "currency": "XAF",
            "status": "complete",
            "customer": "cus_123456789",
            "created_at": "2023-01-01T12:00:00Z"
          },
          // More payments...
        ]
      }
      ```
    </Tab>
  </Tabs>
</Card>

<Card title="Create a Payment" icon="plus" color="#16A34A">
  <Tabs>
    <Tab title="Request">
      ```bash theme={null}
      POST /payments
      POST /payments/initialize (Legacy)
      ```

      <Callout type="info">
        Initialize a new payment. Both endpoints are equivalent and can be used interchangeably.
      </Callout>

      ### Request Parameters

      <ParamField body="amount" type="number" required>
        Amount to charge in the smallest currency unit
      </ParamField>

      <ParamField body="currency" type="string" required>
        Three-letter ISO currency code (e.g., XAF)
      </ParamField>

      <ParamField body="email" type="string" required={false}>
        Customer's email address (required if `phone` and `customer` are not provided)
      </ParamField>

      <ParamField body="phone" type="string" required={false}>
        Customer's phone number (required if `email` and `customer` are not provided)
      </ParamField>

      <ParamField body="customer" type="string or object" required={false}>
        Customer ID or object (required if `email` and `phone` are not provided)
      </ParamField>

      <ParamField body="description" type="string">
        Description of the payment
      </ParamField>

      <ParamField body="reference" type="string">
        Unique reference for the payment
      </ParamField>

      <ParamField body="callback" type="string">
        URL to redirect after payment completion
      </ParamField>

      <Accordion title="Advanced Parameters">
        <ParamField body="locked_currency" type="string">
          Restrict to a specific currency
        </ParamField>

        <ParamField body="locked_channel" type="string">
          Restrict to a specific payment channel
        </ParamField>

        <ParamField body="locked_country" type="string">
          Restrict to a specific country
        </ParamField>

        <ParamField body="items" type="array">
          Array of items being purchased
        </ParamField>

        <ParamField body="shipping" type="object">
          Shipping information
        </ParamField>

        <ParamField body="address" type="object">
          Customer's address
        </ParamField>

        <ParamField body="customer_meta" type="object">
          Additional customer metadata
        </ParamField>
      </Accordion>

      ### Example Request

      <CodeGroup>
        ```json JSON theme={null}
        {
          "amount": 5000,
          "currency": "XAF",
          "customer": {
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+237600000000"
          },
          "description": "Payment for Order #123",
          "callback": "https://example.com/callback",
          "reference": "order_123"
        }
        ```

        ```javascript JavaScript theme={null}
        const payment = {
          amount: 5000,
          currency: "XAF",
          customer: {
            name: "John Doe",
            email: "john@example.com",
            phone: "+237600000000"
          },
          description: "Payment for Order #123",
          callback: "https://example.com/callback",
          reference: "order_123"
        };

        fetch('https://api.notchpay.co/payments', {
          method: 'POST',
          headers: {
            'Authorization': 'YOUR_PUBLIC_KEY',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payment)
        });
        ```
      </CodeGroup>
    </Tab>

    <Tab title="Response">
      ```json theme={null}
      {
        "status": "Accepted",
        "message": "Payment initialized",
        "code": 201,
        "transaction": {
          "id": "pay_123456789",
          "reference": "order_123",
          "amount": 5000,
          "currency": "XAF",
          "status": "pending",
          "customer": "cus_123456789",
          "created_at": "2023-01-01T12:00:00Z"
        },
        "authorization_url": "https://pay.notchpay.co/pay_123456789"
      }
      ```

      <Callout type="success">
        After creating a payment, redirect your customer to the `authorization_url` to complete the payment.
      </Callout>
    </Tab>
  </Tabs>
</Card>

<Card title="Retrieve a Payment" icon="magnifying-glass" color="#16A34A">
  <Tabs>
    <Tab title="Request">
      ```bash theme={null}
      GET /payments/{reference}
      ```

      Retrieve details of a specific payment using its reference.

      ### Path Parameters

      <ParamField path="reference" type="string" required>
        Reference of the payment to retrieve
      </ParamField>
    </Tab>

    <Tab title="Response">
      ```json theme={null}
      {
        "status": "Accepted",
        "message": "Transaction retrieved",
        "code": 202,
        "transaction": {
          "id": "pay_123456789",
          "reference": "order_123",
          "amount": 5000,
          "currency": "XAF",
          "status": "complete",
          "customer": "cus_123456789",
          "payment_method": "pm.ndzAfIh555VCPML1",
          "created_at": "2023-01-01T12:00:00Z",
          "completed_at": "2023-01-01T12:05:00Z"
        }
      }
      ```
    </Tab>
  </Tabs>
</Card>

<Card title="Cancel a Payment" icon="ban" color="#16A34A">
  <Tabs>
    <Tab title="Request">
      ```bash theme={null}
      DELETE /payments/{reference}
      ```

      Cancel a pending payment.

      ### Path Parameters

      <ParamField path="reference" type="string" required>
        Reference of the payment to cancel
      </ParamField>
    </Tab>

    <Tab title="Response">
      ```json theme={null}
      {
        "code": 202,
        "status": "Accepted",
        "message": "Your payment has been canceled"
      }
      ```

      <Callout type="warning">
        You can only cancel payments that are in the `pending` status. Payments that are already `processing`, `complete`, or `failed` cannot be canceled.
      </Callout>
    </Tab>
  </Tabs>
</Card>

<Card title="Process a Payment" icon="play" color="#16A34A">
  <Tabs>
    <Tab title="Request">
      ```bash theme={null}
      POST /payments/{reference}
      PUT /payments/{reference}
      ```

      <Callout type="info">
        Process a pending payment with a specific payment method. Both HTTP methods are equivalent.
      </Callout>

      ### Path Parameters

      <ParamField path="reference" type="string" required>
        Reference of the payment to process
      </ParamField>

      ### Request Parameters

      <ParamField body="channel" type="string" required>
        Payment channel (e.g., cm.mtn, cm.orange)
      </ParamField>

      <ParamField body="data" type="object">
        Channel-specific data
      </ParamField>

      <ParamField body="client_ip" type="string">
        Client's IP address
      </ParamField>

      <Accordion title="Mobile Money Parameters">
        For Mobile Money payments, the `data` object should include:

        <ParamField body="data.phone" type="string">
          The mobile money account number
        </ParamField>

        <ParamField body="data.account_number" type="string">
          Alternative to phone, the mobile money account number
        </ParamField>
      </Accordion>

      ### Example Request for MTN Mobile Money

      ```json theme={null}
      {
        "channel": "cm.mtn",
        "data": {
          "phone": "+237680000000"
        }
      }
      ```
    </Tab>

    <Tab title="Response">
      ```json theme={null}
      {
        "status": "Accepted",
        "message": "Payment processing initiated",
        "code": 202,
        "transaction": {
          "id": "pay_123456789",
          "reference": "order_123",
          "status": "processing"
        }
      }
      ```
    </Tab>
  </Tabs>
</Card>

## Payment Statuses

<CardGroup cols={3}>
  <Card title="pending" icon="clock" color="#f97316">
    Payment has been initialized but not yet processed
  </Card>

  <Card title="processing" icon="spinner" color="#3b82f6">
    Payment is being processed by the payment provider
  </Card>

  <Card title="complete" icon="check-circle" color="#16a34a">
    Payment has been completed
  </Card>

  <Card title="failed" icon="x-circle" color="#dc2626">
    Payment attempt failed
  </Card>

  <Card title="canceled" icon="ban" color="#6b7280">
    Payment was canceled by the merchant or customer
  </Card>

  <Card title="expired" icon="calendar-xmark" color="#9ca3af">
    Payment expired before completion
  </Card>
</CardGroup>

## Handling Callbacks

<Steps>
  <Step title="Redirect to Callback URL">
    When a payment is completed, Notch Pay will redirect the customer to the `callback` URL you provided when creating the payment. The URL will include the payment reference as a query parameter:

    ```
    https://example.com/callback?reference=order_123
    ```
  </Step>

  <Step title="Verify Payment Status">
    <Callout type="warning">
      Always verify the payment status by calling the Retrieve a Payment endpoint before fulfilling the order.
    </Callout>

    ```javascript theme={null}
    // Example verification in JavaScript
    app.get('/callback', async (req, res) => {
      const { reference } = req.query;
      
      try {
        const response = await fetch(`https://api.notchpay.co/payments/${reference}`, {
          headers: { 'Authorization': 'YOUR_PUBLIC_KEY' }
        });
        
        const data = await response.json();
        
        if (data.transaction.status === 'complete') {
          // Payment complete, fulfill the order
          // ...
          res.send('Payment complete!');
        } else {
          // Payment not complete
          res.send('Payment not completed.');
        }
      } catch (error) {
        console.error('Error verifying payment:', error);
        res.status(500).send('Error verifying payment');
      }
    });
    ```
  </Step>

  <Step title="Fulfill the Order">
    Once you've verified that the payment is complete, you can fulfill the order or provide access to the purchased product or service.
  </Step>
</Steps>

## Webhooks

<Callout type="info">
  For more reliable payment notifications, we recommend setting up webhooks to receive real-time updates about payment status changes.
</Callout>

Webhooks provide a more reliable way to receive payment notifications than callbacks, as they don't depend on the customer's browser. See the [Webhooks API](/api-reference/webhooks) documentation for more information.

## Error Handling

<Accordion title="Common Error Codes">
  <ResponseField name="400" type="Bad Request">
    Invalid request parameters

    This typically occurs when required parameters are missing or have invalid values.
  </ResponseField>

  <ResponseField name="401" type="Unauthorized">
    Invalid API key

    Check that you're using the correct API key and that it's properly included in the Authorization header.
  </ResponseField>

  <ResponseField name="404" type="Not Found">
    Payment not found

    The payment reference you provided doesn't exist or belongs to another account.
  </ResponseField>

  <ResponseField name="422" type="Unprocessable Entity">
    Payment cannot be processed

    This occurs when trying to process a payment that's already completed, canceled, or in a state that doesn't allow the requested operation.
  </ResponseField>
</Accordion>

## Best Practices

<CardGroup cols={2}>
  <Card title="Store Payment References" icon="database" color="#16A34A">
    Always store the payment ID and reference in your database for future reference and reconciliation.
  </Card>

  <Card title="Verify Payment Status" icon="check-double" color="#16A34A">
    Always verify the payment status using the API before fulfilling orders or providing services.
  </Card>

  <Card title="Use Webhooks" icon="bell" color="#16A34A">
    Set up webhooks for reliable payment notifications, especially for asynchronous payment methods like mobile money.
  </Card>

  <Card title="Idempotent References" icon="fingerprint" color="#16A34A">
    Use unique, idempotent references for each payment to prevent duplicate payments and simplify reconciliation.
  </Card>

  <Card title="Error Handling" icon="triangle-exclamation" color="#16A34A">
    Implement proper error handling for failed payments, including user-friendly error messages and recovery options.
  </Card>
</CardGroup>


### Check account balance

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Balance;

NotchPay::setApiKey('b.xxxxxxx');
NotchPay::setPrivateKey('private_key_here');

try {
    $balance = Balance::check();

    echo "Available balance: " . $balance->available . " " . $balance->currency . "\n";
    echo "Pending balance: " . $balance->pending . " " . $balance->currency;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Payment Channels

### List payment channels

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Channel;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $channels = Channel::all();

    foreach ($channels->data as $channel) {
        echo $channel->name . ' - ' . $channel->code . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Retrieve a payment channel

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Channel;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $code = 'mtn_momo';
    $channel = Channel::retrieve($code);

    echo "Channel name: " . $channel->name;
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Countries

### List countries

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Country;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $countries = Country::all();

    foreach ($countries->data as $country) {
        echo $country->name . ' - ' . $country->code . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Currencies

### List currencies

```php theme={null}
use NotchPay\NotchPay;
use NotchPay\Currency;

NotchPay::setApiKey('b.xxxxxxx');

try {
    $currencies = Currency::all();

    foreach ($currencies->data as $currency) {
        echo $currency->name . ' - ' . $currency->code . "\n";
    }
} catch(\NotchPay\Exceptions\ApiException $e) {
    // Handle error
    echo $e->getMessage();
}
```

## Error Handling

The library throws different exceptions that you can catch to handle errors:

```php theme={null}
try {
    // Your NotchPay code here
} catch(\NotchPay\Exceptions\ApiException $e) {
    // API errors (validation errors, server errors, etc.)
    echo "API Error: " . $e->getMessage();
    print_r($e->errors); // Error details
} catch(\NotchPay\Exceptions\InvalidArgumentException $e) {
    // Invalid argument errors
    echo "Invalid Argument: " . $e->getMessage();
} catch(\NotchPay\Exceptions\NotchPayException $e) {
    // Other NotchPay errors
    echo "NotchPay Error: " . $e->getMessage();
}
```

## Official Documentation

For more information on API parameters and responses, see the [official NotchPay documentation](https://developer.notchpay.co/).

## Related Resources

<CardGroup cols={3}>
  <Card title="API Reference" icon="book" href="/api-reference">
    Complete API documentation
  </Card>

  <Card title="JavaScript SDK" icon="js" href="/sdks/javascript">
    Integrate with JavaScript applications
  </Card>

  <Card title="Laravel Integration" icon="laravel" href="/sdks/laravel">
    Dedicated Laravel package
  </Card>
</CardGroup>

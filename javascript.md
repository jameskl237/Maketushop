> ## Documentation Index
> Fetch the complete documentation index at: https://developer.notchpay.co/llms.txt
> Use this file to discover all available pages before exploring further.

# JavaScript SDK

> Integrate Notch Pay into your JavaScript applications

# Notch Pay JavaScript SDK

<Note>
  The Notch Pay JavaScript SDK provides a convenient way to integrate Notch Pay into your JavaScript applications, both on the client-side and server-side with Node.js.
</Note>

## Installation

<Tabs>
  <Tab title="npm">
    ```bash theme={null}
    npm install notchpay-js
    ```
  </Tab>

  <Tab title="yarn">
    ```bash theme={null}
    yarn add notchpay-js
    ```
  </Tab>

  <Tab title="CDN">
    ```html theme={null}
    <script src="https://cdn.jsdelivr.net/npm/notchpay-js@latest/dist/notchpay.min.js"></script>
    ```
  </Tab>
</Tabs>

## Client-Side Usage

### Initialize the SDK

<CodeGroup>
  ```javascript ES Modules theme={null}
  import { NotchPay } from 'notchpay-js';

  // Initialize with your public key
  const notchpay = new NotchPay('YOUR_PUBLIC_KEY');
  ```

  ```javascript CommonJS theme={null}
  const { NotchPay } = require('notchpay-js');

  // Initialize with your public key
  const notchpay = new NotchPay('YOUR_PUBLIC_KEY');
  ```

  ```html Script Tag theme={null}
  <script>
    // The SDK is available as window.NotchPay
    const notchpay = new NotchPay('YOUR_PUBLIC_KEY');
  </script>
  ```
</CodeGroup>

### Open Checkout

```javascript theme={null}
// Create a payment on your server first, then use the payment ID to open checkout
const paymentId = 'pay_123456789'; // Get this from your server

notchpay.checkout({
  paymentId: paymentId,
  onSuccess: function(transaction) {
    console.log('Payment successful:', transaction);
    // Redirect to success page or update UI
    window.location.href = '/success?reference=' + transaction.reference;
  },
  onError: function(error) {
    console.error('Payment failed:', error);
    // Show error message
  },
  onClose: function() {
    console.log('Checkout closed');
    // Handle checkout closure
  }
});
```

### Inline Checkout

You can also embed the checkout directly in your page:

```javascript theme={null}
notchpay.inlineCheckout({
  paymentId: paymentId,
  container: '#checkout-container', // CSS selector for the container element
  onSuccess: function(transaction) {
    console.log('Payment successful:', transaction);
  },
  onError: function(error) {
    console.error('Payment failed:', error);
  }
});
```

```html theme={null}
<!-- Add this to your HTML -->
<div id="checkout-container"></div>
```

### Payment Button

Create a payment button with minimal code:

```javascript theme={null}
notchpay.createButton({
  amount: 5000,
  currency: 'XAF',
  customer: {
    email: 'customer@example.com'
  },
  reference: 'order_123',
  container: '#payment-button-container',
  text: 'Pay Now',
  className: 'my-custom-button-class'
});
```

```html theme={null}
<!-- Add this to your HTML -->
<div id="payment-button-container"></div>
```

## Server-Side Usage (Node.js)

### Initialize the SDK

```javascript theme={null}
const { NotchPayAPI } = require('notchpay-js');

// Initialize with your secret key
const notchpay = new NotchPayAPI('YOUR_SECRET_KEY');

// For endpoints requiring advanced authentication
const notchpayWithGrant = new NotchPayAPI('YOUR_SECRET_KEY', {
  grantKey: 'YOUR_PRIVATE_KEY'
});
```

### Create a Payment

```javascript theme={null}
async function createPayment() {
  try {
    const payment = await notchpay.payments.create({
      amount: 5000,
      currency: 'XAF',
      customer: {
        email: 'customer@example.com',
        name: 'John Doe'
      },
      reference: 'order_123',
      callback: 'https://example.com/callback',
      description: 'Payment for Order #123'
    });
    
    console.log('Payment created:', payment);
    return payment;
  } catch (error) {
    console.error('Error creating payment:', error);
    throw error;
  }
}
```

### Retrieve a Payment

```javascript theme={null}
async function getPayment(reference) {
  try {
    const payment = await notchpay.payments.retrieve(reference);
    console.log('Payment retrieved:', payment);
    return payment;
  } catch (error) {
    console.error('Error retrieving payment:', error);
    throw error;
  }
}
```

### List Payments

```javascript theme={null}
async function listPayments() {
  try {
    const payments = await notchpay.payments.list({
      limit: 10,
      page: 1,
      status: 'complete'
    });
    
    console.log('Payments retrieved:', payments);
    return payments;
  } catch (error) {
    console.error('Error listing payments:', error);
    throw error;
  }
}
```

### Create a Transfer

```javascript theme={null}
async function createTransfer() {
  try {
    const transfer = await notchpayWithGrant.transfers.create({
      amount: 5000,
      currency: 'XAF',
      beneficiary: {
        name: 'John Doe',
        phone: '+237600000000'
      },
      description: 'Salary payment',
      reference: 'transfer_123'
    });
    
    console.log('Transfer created:', transfer);
    return transfer;
  } catch (error) {
    console.error('Error creating transfer:', error);
    throw error;
  }
}
```

### Check Balance

```javascript theme={null}
async function checkBalance() {
  try {
    const balance = await notchpayWithGrant.balance.retrieve();
    console.log('Balance:', balance);
    return balance;
  } catch (error) {
    console.error('Error checking balance:', error);
    throw error;
  }
}
```

## Express.js Integration Example

Here's a complete example of integrating Notch Pay with an Express.js application:

```javascript theme={null}
const express = require('express');
const { NotchPayAPI } = require('notchpay-js');
const app = express();

// Initialize Notch Pay SDK
const notchpay = new NotchPayAPI('YOUR_SECRET_KEY');

// Parse JSON request body
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files
app.use(express.static('public'));

// Create a payment
app.post('/create-payment', async (req, res) => {
  try {
    const { amount, email, name } = req.body;
    
    const payment = await notchpay.payments.create({
      amount: parseInt(amount),
      currency: 'XAF',
      customer: {
        email,
        name
      },
      reference: `order_${Date.now()}`,
      callback: `${req.protocol}://${req.get('host')}/payment-callback`,
      description: 'Payment from Express.js app'
    });
    
    res.json({ success: true, payment });
  } catch (error) {
    console.error('Error creating payment:', error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// Payment callback
app.get('/payment-callback', async (req, res) => {
  try {
    const { reference } = req.query;
    
    // Verify payment status
    const payment = await notchpay.payments.retrieve(reference);
    
    if (payment.transaction.status === 'complete') {
      // Payment complete, fulfill the order
      res.send(`
        <html>
          <body>
            <h1>Payment Complete!</h1>
            <p>Thank you for your payment of ${payment.transaction.amount} ${payment.transaction.currency}</p>
            <p>Reference: ${payment.transaction.reference}</p>
            <a href="/">Return to Home</a>
          </body>
        </html>
      `);
    } else {
      // Payment not complete
      res.send(`
        <html>
          <body>
            <h1>Payment Not Completed</h1>
            <p>Status: ${payment.transaction.status}</p>
            <p>Reference: ${payment.transaction.reference}</p>
            <a href="/">Return to Home</a>
          </body>
        </html>
      `);
    }
  } catch (error) {
    console.error('Error verifying payment:', error);
    res.status(500).send('Error verifying payment');
  }
});

// Webhook endpoint
app.post('/webhooks/notchpay', (req, res) => {
  const event = req.body;
  
  console.log('Received webhook event:', event.type);
  
  // Process the event based on its type
  switch (event.type) {
    case 'payment.complete':
      // Handle completed payment
      console.log(`Payment ${event.data.reference} completed`);
      break;
      
    case 'payment.failed':
      // Handle failed payment
      console.log(`Payment ${event.data.reference} failed`);
      break;
      
    // Handle other event types...
  }
  
  // Always return a 200 response quickly
  res.status(200).send('Webhook received');
});

// Start the server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
```

## React Integration Example

Here's how to integrate Notch Pay with a React application:

```jsx theme={null}
import React, { useState } from 'react';
import { NotchPay } from 'notchpay-js';

// Initialize Notch Pay SDK
const notchpay = new NotchPay('YOUR_PUBLIC_KEY');

function PaymentForm() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    
    try {
      // Create payment on your server
      const response = await fetch('/api/create-payment', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          amount: 5000,
          email: 'customer@example.com',
          name: 'John Doe'
        }),
      });
      
      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.error || 'Failed to create payment');
      }
      
      // Open checkout
      notchpay.checkout({
        paymentId: data.payment.transaction.id,
        onSuccess: function(transaction) {
          console.log('Payment successful:', transaction);
          // Redirect or update UI
          window.location.href = `/success?reference=${transaction.reference}`;
        },
        onError: function(error) {
          console.error('Payment failed:', error);
          setError('Payment failed: ' + error.message);
        },
        onClose: function() {
          console.log('Checkout closed');
        }
      });
    } catch (error) {
      console.error('Error:', error);
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="payment-form">
      <h2>Complete Your Payment</h2>
      
      {error && (
        <div className="error-message">
          {error}
        </div>
      )}
      
      <form onSubmit={handleSubmit}>
        <button 
          type="submit" 
          disabled={loading}
          className="payment-button"
        >
          {loading ? 'Processing...' : 'Pay 5,000 XAF'}
        </button>
      </form>
    </div>
  );
}

export default PaymentForm;
```

## API Reference

The JavaScript SDK provides access to all Notch Pay API endpoints:

### Payments

* `notchpay.payments.create(data)` - Create a payment
* `notchpay.payments.retrieve(reference)` - Retrieve a payment
* `notchpay.payments.list(params)` - List payments
* `notchpay.payments.cancel(reference)` - Cancel a payment

### Transfers

* `notchpay.transfers.create(data)` - Create a transfer
* `notchpay.transfers.retrieve(reference)` - Retrieve a transfer
* `notchpay.transfers.list(params)` - List transfers
* `notchpay.transfers.cancel(reference)` - Cancel a transfer
* `notchpay.transfers.createBulk(data)` - Create a bulk transfer

### Customers

* `notchpay.customers.create(data)` - Create a customer
* `notchpay.customers.retrieve(id)` - Retrieve a customer
* `notchpay.customers.update(id, data)` - Update a customer
* `notchpay.customers.list(params)` - List customers
* `notchpay.customers.delete(id)` - Delete a customer

### Beneficiaries

* `notchpay.beneficiaries.create(data)` - Create a beneficiary
* `notchpay.beneficiaries.retrieve(id)` - Retrieve a beneficiary
* `notchpay.beneficiaries.update(id, data)` - Update a beneficiary
* `notchpay.beneficiaries.list(params)` - List beneficiaries
* `notchpay.beneficiaries.delete(id)` - Delete a beneficiary

### Balance

* `notchpay.balance.retrieve()` - Check balance
* `notchpay.balance.retrieveCurrency(currency)` - Check balance for a specific currency
* `notchpay.balance.history(params)` - List balance history

### Webhooks

* `notchpay.webhooks.create(data)` - Create a webhook
* `notchpay.webhooks.retrieve(id)` - Retrieve a webhook
* `notchpay.webhooks.update(id, data)` - Update a webhook
* `notchpay.webhooks.list(params)` - List webhooks
* `notchpay.webhooks.delete(id)` - Delete a webhook

## Error Handling

The SDK throws detailed errors that you can catch and handle:

```javascript theme={null}
try {
  const payment = await notchpay.payments.create({
    // Payment data
  });
} catch (error) {
  if (error.type === 'validation_error') {
    console.error('Validation error:', error.errors);
  } else if (error.type === 'authentication_error') {
    console.error('Authentication error:', error.message);
  } else {
    console.error('Unexpected error:', error.message);
  }
}
```

## TypeScript Support

The SDK includes TypeScript definitions for all methods and objects:

```typescript theme={null}
import { NotchPay, Payment, Customer, Transfer } from 'notchpay-js';

const notchpay = new NotchPay('YOUR_PUBLIC_KEY');

async function createTypedPayment(): Promise<Payment> {
  const payment = await notchpay.payments.create({
    amount: 5000,
    currency: 'XAF',
    customer: {
      email: 'customer@example.com'
    } as Customer,
    reference: 'order_123'
  });
  
  return payment;
}
```

## Related Resources

<CardGroup cols={3}>
  <Card title="API Reference" icon="book" href="/api-reference">
    Complete API documentation
  </Card>

  <Card title="PHP SDK" icon="php" href="/sdks/php">
    Integrate with PHP applications
  </Card>

  <Card title="Python SDK" icon="python" href="/sdks/python">
    Integrate with Python applications
  </Card>
</CardGroup>

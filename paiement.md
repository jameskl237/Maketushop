> ## Documentation Index
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

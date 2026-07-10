import React from 'react';

export default function PaymentGatewaySelector() {
  return (
    <div>
      <label>
        <input type="radio" name="gateway" value="flutterwave" /> Flutterwave
      </label>
      <label style={{ marginLeft: 8 }}>
        <input type="radio" name="gateway" value="paystack" /> Paystack
      </label>
    </div>
  );
}


# EventIQ - Payment wiring task

## Step 0 - Repo understanding
- [x] Read key payment files: PaymentGatewayService, PaymentServiceProvider, WebhookController, PaystackService, FlutterwaveService, Payment routes

## Step 1 - Wire PaymentGatewayService to concrete adapters
- [ ] Create adapter interface + Paystack/Flutterwave adapter implementations
- [ ] Update PaymentGatewayService to select adapter from env/config and delegate
- [ ] Update PaymentServiceProvider bindings for adapters


## Step 2 - Standardize normalized DTOs/errors
- [ ] Add canonical DTOs (init, verify, webhook outcome, payout outcome)
- [ ] Add canonical error model + mapper from provider exceptions

## Step 3 - Ensure webhook controller flows are correct
- [ ] Implement shared webhook flow in Features/Payment/Controllers/WebhookController.php
- [ ] Refactor PaystackController/FlutterwaveController to use the shared flow

## Step 4 - Add env validation at boot
- [ ] Implement Env validator for payment gateway config
- [ ] Call validator during app boot

## Step 5 - “make ripgrep binary”
- [ ] Add ripgrep installation / local binary setup so code search works

## Step 6 - Verification
- [ ] Run PHP lint / artisan boot
- [ ] Smoke-test webhook signature verification paths


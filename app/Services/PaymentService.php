<?php

namespace App\Services;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class PaymentService
{
    /**
     * Initiate a deposit transaction via the selected gateway.
     * Returns a redirect URL for the user to complete payment.
     */
    public function initiateDeposit(Transaction $transaction, PaymentGateway $gateway): string
    {
        $credentials = $gateway->credentials;
        $user = $transaction->account->user;
        $amount = $transaction->amount;
        $currency = 'USD'; // Or from settings
        $callbackUrl = route('payment.callback', ['provider' => $gateway->code, 'trx' => $transaction->id]);
        
        // Fee Calculation (if not already applied in transaction amount)
        // Ideally transaction->amount is the final amount to charge.
        // If limits were checked before, we assume $transaction->amount is correct.

        if ($gateway->type === 'manual') {
            return route('deposit.manual.instructions', ['transaction' => $transaction->id]);
        }

        switch ($gateway->code) {
            case 'stripe':
                return $this->initiateStripe($credentials, $amount, $currency, $callbackUrl, $transaction);
                
            case 'paypal':
                return $this->initiatePayPal($credentials, $amount, $currency, $callbackUrl, $transaction);
                
            case 'paystack':
                return $this->initiatePaystack($credentials, $user->email, $amount, $callbackUrl, $transaction);
                
            case 'flutterwave':
                return $this->initiateFlutterwave($credentials, $user, $amount, $currency, $callbackUrl, $transaction);
                
            case 'monnify':
                return $this->initiateMonnify($credentials, $user, $amount, $currency, $callbackUrl, $transaction);
                
            default:
                throw new \Exception("Unsupported payment provider: {$gateway->code}");
        }
    }

    protected function initiateStripe($creds, $amount, $currency, $callbackUrl, $trx)
    {
        Stripe::setApiKey($creds['secret_key']);
        
        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product_data' => [
                        'name' => 'Deposit to Account',
                    ],
                    'unit_amount' => (int) ($amount * 100), // Cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $callbackUrl . '?status=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $callbackUrl . '?status=cancel',
            'client_reference_id' => (string) $trx->id,
            'metadata' => [
                'transaction_id' => (string) $trx->id,
            ],
        ]);

        return $session->url;
    }

    protected function initiatePayPal($creds, $amount, $currency, $callbackUrl, $trx)
    {
        $baseUrl = $creds['mode'] === 'sandbox' 
            ? 'https://api-m.sandbox.paypal.com' 
            : 'https://api-m.paypal.com';

        // 1. Get Access Token
        $response = Http::withBasicAuth($creds['client_id'], $creds['client_secret'])
            ->asForm()
            ->post("$baseUrl/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);
            
        if (!$response->successful()) {
            throw new \Exception('PayPal Auth Failed: ' . $response->body());
        }
        
        $accessToken = $response->json()['access_token'];

        // 2. Create Order
        $orderRes = Http::withToken($accessToken)
            ->post("$baseUrl/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $trx->id,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $callbackUrl . '?status=success',
                    'cancel_url' => $callbackUrl . '?status=cancel',
                ],
            ]);
            
        if (!$orderRes->successful()) {
             throw new \Exception('PayPal Order Failed: ' . $orderRes->body());
        }
        
        $links = $orderRes->json()['links'];
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        
        throw new \Exception('PayPal approval link not found.');
    }

    protected function initiatePaystack($creds, $email, $amount, $callbackUrl, $trx)
    {
        // Paystack amount is in kobo (NGN * 100), but if using USD check support.
        // Assuming NGN or USD supported by account.
        
        $response = Http::withToken($creds['secret_key'])
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email,
                'amount' => (int) ($amount * 100),
                'callback_url' => $callbackUrl,
                'reference' => $trx->id, // Use our ID or generate unique
                'metadata' => ['custom_fields' => [['display_name' => 'Transaction ID', 'variable_name' => 'transaction_id', 'value' => $trx->id]]],
            ]);
            
        if (!$response->successful()) {
            throw new \Exception('Paystack Init Failed: ' . $response->body());
        }

        return $response->json()['data']['authorization_url'];
    }

    protected function initiateFlutterwave($creds, $user, $amount, $currency, $callbackUrl, $trx)
    {
        $response = Http::withToken($creds['secret_key'])
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $trx->id,
                'amount' => $amount,
                'currency' => $currency,
                'redirect_url' => $callbackUrl,
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'customizations' => [
                    'title' => 'Deposit',
                ],
            ]);
            
        if (!$response->successful()) {
            throw new \Exception('Flutterwave Init Failed: ' . $response->body());
        }

        return $response->json()['data']['link'];
    }

    protected function initiateMonnify($creds, $user, $amount, $currency, $callbackUrl, $trx)
    {
        // 1. Auth (Basic Auth with API Key : Secret Key) encoded base64
        $authStr = base64_encode("{$creds['api_key']}:{$creds['secret_key']}");
        
        $baseUrl = 'https://api.monnify.com'; // Production. Sandbox is different usually.
        // Assuming production or needing specific URL in creds? usually sandbox is sandbox.monnify.com
        // Let's assume production for simplicity or define via an extra field if needed. 
        // Better: Try standard prod URL, user can override via setting if we added 'base_url' field.
        // For now, let's use the standard base URL structure.
        
        $loginRes = Http::withHeaders(['Authorization' => "Basic $authStr"])
            ->post("$baseUrl/api/v1/auth/login");
            
        // Monnify sometimes handles auth differently.
        // If failing, check specific docs. Monnify uses Bearer token after login.
        
        if (!$loginRes->successful()) {
             // Fallback to sandbox if prod fails? Or throw.
             throw new \Exception('Monnify Auth Failed');
        }
        
        $accessToken = $loginRes->json()['responseBody']['accessToken'];
        
        $response = Http::withToken($accessToken)
            ->post("$baseUrl/api/v1/merchant/transactions/init-transaction", [
                'amount' => $amount,
                'customerName' => $user->name,
                'customerEmail' => $user->email,
                'paymentReference' => $trx->id,
                'paymentDescription' => "Deposit",
                'currencyCode' => $currency, // NGN usually
                'contractCode' => $creds['contract_code'],
                'redirectUrl' => $callbackUrl,
            ]);
            
        if (!$response->successful()) {
            throw new \Exception('Monnify Init Failed: ' . $response->body());
        }

        return $response->json()['responseBody']['checkoutUrl'];
    }

    /**
     * Process a withdrawal/payout via the selected gateway.
     * This logic handles the transfer of funds FROM the system TO the user.
     */
    public function processPayout(Transaction $transaction, PaymentGateway $gateway)
    {
        // Security check: Ensure this is a withdrawal
        if ($transaction->transaction_type !== 'withdrawal') {
            throw new \Exception('Invalid transaction type for payout.');
        }

        $credentials = $gateway->credentials;
        $user = $transaction->account->user;
        $amount = $transaction->amount;
        $currency = 'USD'; 

        // Important: Recipient details needed. 
        // We assume transaction metadata or user profile has this info.
        // For this implementation, we will log the intent or call generic transfer endpoints where possible.
        // In a real system, you'd look up $user->bank_account or $transaction->metadata['destination'].

        switch ($gateway->code) {
            case 'stripe':
                // Stripe Connect Transfer or Payout
                // Requires destination account ID (e.g., acct_12345)
                // return $this->payoutStripe($credentials, $amount, $currency, $transaction);
                throw new \Exception('Stripe Automatic Payout requires Connected Accounts setup.');

            case 'paypal':
                // PayPal Payouts API
                // Requires Email or Payer ID
                // return $this->payoutPayPal($credentials, $amount, $currency, $transaction);
                throw new \Exception('PayPal Payouts requires Payouts API enabled.');

            case 'paystack':
                 // Transfer to Bank Account
                 // Needs recipient code (create recipient first)
                 return $this->payoutPaystack($credentials, $amount, $transaction);

            case 'flutterwave':
                 // Transfer to Bank or Mobile Money
                 return $this->payoutFlutterwave($credentials, $amount, $currency, $transaction);

            case 'monnify':
                 // Disbursement
                 return $this->payoutMonnify($credentials, $amount, $currency, $transaction);

            default:
                throw new \Exception("Unsupported payout provider: {$gateway->code}");
        }
    }

    protected function payoutPaystack($creds, $amount, $trx)
    {
        // Simplified Transfer (Needs Recipient Code usually stored in user profile)
        // $recipient = $trx->account->bank_recipient_code; 
        
        throw new \Exception('Paystack Payout: Recipient Account integration via Profile needed.');
    }

    protected function payoutFlutterwave($creds, $amount, $currency, $trx)
    {
        // Simplified Transfer
        throw new \Exception('Flutterwave Payout: Recipient Account integration via Profile needed.');
    }

    protected function payoutMonnify($creds, $amount, $currency, $trx)
    {
        // Simplified Disbursement
        throw new \Exception('Monnify Payout: Recipient Account integration via Profile needed.');
    }

    /**
     * Initialize a generic payment (for loan repayments, fees, etc.)
     */
    public function initializeGenericPayment(PaymentGateway $gateway, array $data): array
    {
        $credentials = $gateway->credentials ?? [];
        $amount = $data['amount'];
        $currency = $data['currency'] ?? 'USD';
        $reference = $data['reference'] ?? $this->generateReference();
        $successUrl = $data['success_url'] ?? route('payment.success');
        $cancelUrl = $data['cancel_url'] ?? route('payment.cancel');

        // For manual gateways, return payment details
        if ($gateway->type === 'manual') {
            return [
                'success' => true,
                'type' => 'manual',
                'gateway' => [
                    'id' => $gateway->id,
                    'name' => $gateway->name,
                    'instructions' => $gateway->instructions,
                    'details' => $this->extractManualDetails($credentials),
                ],
                'reference' => $reference,
                'amount' => $amount,
                'fee' => $gateway->calculateFee($amount),
                'total' => $gateway->calculateTotal($amount),
            ];
        }

        // For automatic gateways
        $apiKeys = $gateway->getApiKeys();
        
        try {
            return match ($gateway->provider) {
                'stripe' => $this->initStripeGenericPayment($apiKeys, $data, $gateway),
                'paystack' => $this->initPaystackGenericPayment($apiKeys, $data, $gateway),
                'flutterwave' => $this->initFlutterwaveGenericPayment($apiKeys, $data, $gateway),
                default => ['success' => false, 'error' => 'Unsupported provider: ' . $gateway->provider],
            };
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Payment initialization failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize Stripe generic payment
     */
    protected function initStripeGenericPayment(array $apiKeys, array $data, PaymentGateway $gateway): array
    {
        Stripe::setApiKey($apiKeys['secret_key']);

        $reference = $data['reference'] ?? $this->generateReference();
        $amount = $data['amount'];
        $currency = strtolower($data['currency'] ?? 'usd');

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $data['description'] ?? 'Payment',
                    ],
                    'unit_amount' => (int) ($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => ($data['success_url'] ?? url('/payment/success')) . '?session_id={CHECKOUT_SESSION_ID}&reference=' . $reference,
            'cancel_url' => $data['cancel_url'] ?? url('/payment/cancel'),
            'client_reference_id' => $reference,
            'metadata' => array_merge($data['metadata'] ?? [], [
                'gateway_id' => $gateway->id,
                'reference' => $reference,
                'purpose' => $data['purpose'] ?? 'payment',
            ]),
        ]);

        return [
            'success' => true,
            'type' => 'redirect',
            'provider' => 'stripe',
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            'reference' => $reference,
        ];
    }

    /**
     * Initialize Paystack generic payment
     */
    protected function initPaystackGenericPayment(array $apiKeys, array $data, PaymentGateway $gateway): array
    {
        $reference = $data['reference'] ?? $this->generateReference();
        
        // Validate secret key exists
        if (empty($apiKeys['secret_key'])) {
            return [
                'success' => false,
                'error' => 'Paystack secret key is not configured. Please check gateway settings.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKeys['secret_key'],
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => $data['customer_email'],
                'amount' => (int) ($data['amount'] * 100),
                'currency' => strtoupper($data['currency'] ?? 'NGN'),
                'reference' => $reference,
                'callback_url' => $data['success_url'] ?? url('/payment/callback/paystack'),
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'gateway_id' => $gateway->id,
                    'purpose' => $data['purpose'] ?? 'payment',
                ]),
            ]);

            $result = $response->json();

            // Log for debugging
            \Log::info('Paystack Response', ['status' => $response->status(), 'body' => $result]);

            if ($response->successful() && ($result['status'] ?? false)) {
                return [
                    'success' => true,
                    'type' => 'redirect',
                    'provider' => 'paystack',
                    'checkout_url' => $result['data']['authorization_url'],
                    'access_code' => $result['data']['access_code'],
                    'reference' => $reference,
                ];
            }

            // Better error message handling
            $errorMessage = $result['message'] ?? 'Payment initialization failed';
            if ($response->status() === 401) {
                $errorMessage = 'Invalid API keys. Please verify your Paystack credentials in the gateway settings.';
            }

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            \Log::error('Paystack Error', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to connect to Paystack: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize Flutterwave generic payment
     */
    protected function initFlutterwaveGenericPayment(array $apiKeys, array $data, PaymentGateway $gateway): array
    {
        $reference = $data['reference'] ?? $this->generateReference();

        $response = Http::withToken($apiKeys['secret_key'])
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $reference,
                'amount' => $data['amount'],
                'currency' => strtoupper($data['currency'] ?? 'NGN'),
                'redirect_url' => $data['success_url'] ?? url('/payment/callback/flutterwave'),
                'customer' => [
                    'email' => $data['customer_email'],
                    'name' => $data['customer_name'] ?? null,
                ],
                'meta' => array_merge($data['metadata'] ?? [], [
                    'gateway_id' => $gateway->id,
                    'purpose' => $data['purpose'] ?? 'payment',
                ]),
                'customizations' => [
                    'title' => $data['description'] ?? 'Payment',
                ],
            ]);

        $result = $response->json();

        if ($response->successful() && ($result['status'] ?? '') === 'success') {
            return [
                'success' => true,
                'type' => 'redirect',
                'provider' => 'flutterwave',
                'checkout_url' => $result['data']['link'],
                'reference' => $reference,
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Payment initialization failed',
        ];
    }

    /**
     * Verify a generic payment
     */
    public function verifyGenericPayment(PaymentGateway $gateway, string $reference): array
    {
        if ($gateway->type === 'manual') {
            return ['success' => true, 'status' => 'pending_verification', 'paid' => false];
        }

        $apiKeys = $gateway->getApiKeys();

        try {
            return match ($gateway->provider) {
                'stripe' => $this->verifyStripePayment($apiKeys, $reference),
                'paystack' => $this->verifyPaystackPayment($apiKeys, $reference),
                'flutterwave' => $this->verifyFlutterwavePayment($apiKeys, $reference),
                default => ['success' => false, 'error' => 'Unsupported provider'],
            };
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify Stripe payment by session ID
     */
    protected function verifyStripePayment(array $apiKeys, string $sessionId): array
    {
        Stripe::setApiKey($apiKeys['secret_key']);
        
        $session = StripeSession::retrieve($sessionId);
        
        return [
            'success' => true,
            'status' => $session->payment_status,
            'paid' => $session->payment_status === 'paid',
            'amount' => $session->amount_total / 100,
            'currency' => strtoupper($session->currency),
            'metadata' => $session->metadata?->toArray() ?? [],
        ];
    }

    /**
     * Verify Paystack payment
     */
    protected function verifyPaystackPayment(array $apiKeys, string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKeys['secret_key'],
            'Content-Type' => 'application/json',
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        $result = $response->json();

        if ($response->successful() && ($result['status'] ?? false)) {
            $data = $result['data'];
            
            return [
                'success' => true,
                'status' => $data['status'],
                'paid' => $data['status'] === 'success',
                'amount' => $data['amount'] / 100,
                'currency' => $data['currency'],
                'metadata' => $data['metadata'] ?? [],
            ];
        }

        return ['success' => false, 'error' => $result['message'] ?? 'Verification failed'];
    }

    /**
     * Verify Flutterwave payment
     */
    protected function verifyFlutterwavePayment(array $apiKeys, string $transactionId): array
    {
        $response = Http::withToken($apiKeys['secret_key'])
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

        $result = $response->json();

        if ($response->successful() && ($result['status'] ?? '') === 'success') {
            $data = $result['data'];
            
            return [
                'success' => true,
                'status' => $data['status'],
                'paid' => $data['status'] === 'successful',
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'metadata' => $data['meta'] ?? [],
            ];
        }

        return ['success' => false, 'error' => $result['message'] ?? 'Verification failed'];
    }

    /**
     * Extract manual payment details (bank info, crypto wallet, etc.)
     */
    protected function extractManualDetails(array $credentials): array
    {
        $publicFields = ['bank_name', 'account_name', 'account_number', 'routing_number', 
                        'swift_code', 'iban', 'wallet_address', 'network', 'qr_code', 'custom_fields'];
        
        return collect($credentials)
            ->only($publicFields)
            ->filter()
            ->toArray();
    }

    /**
     * Generate unique payment reference
     */
    public function generateReference(string $prefix = 'PAY'): string
    {
        return strtoupper($prefix . '-' . uniqid() . '-' . substr(md5(microtime()), 0, 6));
    }

    /**
     * Get available gateways for a category
     */
    public static function getAvailableGateways(string $category = 'payment'): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentGateway::active()
            ->forCategory($category)
            ->ordered()
            ->get();
    }

    /**
     * Get gateways formatted for frontend selection
     */
    public static function getGatewaysForFrontend(string $category = 'payment'): array
    {
        $gateways = self::getAvailableGateways($category);
        
        return $gateways->map(function (PaymentGateway $gateway) {
            return [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'code' => $gateway->code,
                'type' => $gateway->type,
                'provider' => $gateway->provider,
                'logo' => $gateway->logo_url,
                'description' => $gateway->description,
                'min_limit' => (float) $gateway->min_limit,
                'max_limit' => $gateway->max_limit ? (float) $gateway->max_limit : null,
                'fee_fixed' => (float) $gateway->fee_fixed,
                'fee_percentage' => (float) $gateway->fee_percentage,
                'instructions' => $gateway->isManual() ? $gateway->instructions : null,
            ];
        })->toArray();
    }
}

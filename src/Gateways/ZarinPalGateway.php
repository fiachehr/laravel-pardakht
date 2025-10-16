<?php

namespace Fiachehr\Pardakht\Gateways;

use Fiachehr\Pardakht\Exceptions\GatewayException;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\PaymentResponse;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationResponse;

/**
 * Class ZarinPalGateway
 *
 * Implementation for ZarinPal payment gateway
 */
class ZarinPalGateway extends AbstractGateway
{
    protected const API_PRODUCTION = 'https://api.zarinpal.com/pg/v4/payment';
    protected const API_SANDBOX = 'https://sandbox.zarinpal.com/pg/v4/payment';
    protected const PAYMENT_URL_PRODUCTION = 'https://www.zarinpal.com/pg/StartPay';
    protected const PAYMENT_URL_SANDBOX = 'https://sandbox.zarinpal.com/pg/StartPay';

    /**
     * @inheritDoc
     */
    protected function validateConfig(): void
    {
        $required = ['merchant_id', 'callback_url'];

        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                throw GatewayException::invalidConfiguration('zarinpal', $field);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'zarinpal';
    }

    /**
     * @inheritDoc
     */
    public function request(PaymentRequest $request): PaymentResponse
    {
        $apiUrl = $this->sandbox ? self::API_SANDBOX : self::API_PRODUCTION;

        $payload = [
            'merchant_id' => $this->getConfig('merchant_id'),
            'amount' => $request->amount,
            'description' => $request->description ?? $this->getConfig('description', 'Payment'),
            'callback_url' => $request->callbackUrl,
            'metadata' => array_merge([
                'order_id' => $request->orderId,
            ], $request->metadata),
        ];

        // Add optional fields
        if ($request->mobile) {
            $payload['metadata']['mobile'] = $request->mobile;
        }

        if ($request->email) {
            $payload['metadata']['email'] = $request->email;
        }

        try {
            $response = $this->makeHttpRequest('POST', $apiUrl . '/request.json', [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = $response['data'] ?? [];
            $code = (int) ($data['code'] ?? -1);
            $authority = $data['authority'] ?? null;

            if ($code === 100 && $authority) {
                $trackingCode = $this->generateTrackingCode();
                $paymentUrlBase = $this->sandbox ? self::PAYMENT_URL_SANDBOX : self::PAYMENT_URL_PRODUCTION;
                $paymentUrl = $paymentUrlBase . '/' . $authority;

                return new PaymentResponse(
                    success: true,
                    trackingCode: $trackingCode,
                    paymentUrl: $paymentUrl,
                    referenceId: $authority,
                    message: $data['message'] ?? 'Payment request successful',
                    rawResponse: $response
                );
            }

            throw GatewayException::requestFailed(
                'zarinpal',
                $this->getErrorMessage($code),
                $code
            );
        } catch (\Exception $e) {
            if ($e instanceof GatewayException) {
                throw $e;
            }

            throw GatewayException::requestFailed('zarinpal', $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function verify(VerificationRequest $request): VerificationResponse
    {
        $apiUrl = $this->sandbox ? self::API_SANDBOX : self::API_PRODUCTION;

        // Get data from callback
        $authority = $request->getGatewayData('Authority');
        $status = $request->getGatewayData('Status');

        // Check callback status
        if ($status !== 'OK') {
            throw GatewayException::verificationFailed(
                'zarinpal',
                'Transaction cancelled by user or failed'
            );
        }

        // Get transaction data from tracking code
        // This would typically come from your database
        $payload = [
            'merchant_id' => $this->getConfig('merchant_id'),
            'authority' => $authority,
            'amount' => $request->getGatewayData('amount'), // Should be retrieved from database
        ];

        try {
            $response = $this->makeHttpRequest('POST', $apiUrl . '/verify.json', [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = $response['data'] ?? [];
            $code = (int) ($data['code'] ?? -1);
            $refId = $data['ref_id'] ?? null;
            $cardNumber = $data['card_pan'] ?? null;

            if ($code === 100 || $code === 101) { // 100 = success, 101 = already verified
                return new VerificationResponse(
                    success: true,
                    referenceId: (string) $refId,
                    cardNumber: $cardNumber,
                    amount: (int) $payload['amount'],
                    transactionId: $authority,
                    message: $data['message'] ?? 'Payment verified successfully',
                    rawResponse: $response
                );
            }

            throw GatewayException::verificationFailed(
                'zarinpal',
                $this->getErrorMessage($code),
                $code
            );
        } catch (\Exception $e) {
            if ($e instanceof GatewayException) {
                throw $e;
            }

            throw GatewayException::verificationFailed('zarinpal', $e->getMessage());
        }
    }

    /**
     * Get error message for ZarinPal error codes
     *
     * @param int $code
     * @return string
     */
    protected function getErrorMessage(int $code): string
    {
        $errors = [
            -1 => 'Incomplete data submitted',
            -2 => 'Invalid IP or merchant code',
            -3 => 'Payment amount not allowed due to Shaparak limitations',
            -4 => 'Merchant verification level below silver',
            -11 => 'Request not found',
            -12 => 'Cannot edit request',
            -21 => 'No financial operation found for this transaction',
            -22 => 'Transaction unsuccessful',
            -33 => 'Transaction amount mismatch with paid amount',
            -34 => 'Transaction split limit exceeded',
            -40 => 'Access to method not allowed',
            -41 => 'Invalid AdditionalData information',
            -42 => 'Payment identifier lifetime must be between 30 minutes and 45 days',
            -54 => 'Request has been archived',
            100 => 'Operation successful',
            101 => 'Payment successful and already verified',
        ];

        return $errors[$code] ?? "Unknown error (code: {$code})";
    }
}

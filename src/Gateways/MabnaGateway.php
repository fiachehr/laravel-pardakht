<?php

namespace Fiachehr\Pardakht\Gateways;

use Fiachehr\Pardakht\Exceptions\GatewayException;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\PaymentResponse;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationResponse;

/**
 * Class MabnaGateway
 *
 * Implementation for Mabna Card (Sepehr) payment gateway
 */
class MabnaGateway extends AbstractGateway
{
    // Production URLs
    protected const TOKEN_URL_PRODUCTION = 'https://sepehr.shaparak.ir:8081/V1/PeymentApi/GetToken';
    protected const PAYMENT_URL_PRODUCTION = 'https://sepehr.shaparak.ir:8080/Pay';
    protected const VERIFY_URL_PRODUCTION = 'https://sepehr.shaparak.ir:8081/V1/PeymentApi/Advice';
    protected const ROLLBACK_URL_PRODUCTION = 'https://sepehr.shaparak.ir:8081/V1/PeymentApi/Rollback';

    // Sandbox URLs
    protected const TOKEN_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/V1/PeymentApi/GetToken';
    protected const PAYMENT_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/Pay';
    protected const VERIFY_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/V1/PeymentApi/Advice';
    protected const ROLLBACK_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/V1/PeymentApi/Rollback';

    /**
     * @inheritDoc
     */
    protected function validateConfig(): void
    {
        $required = ['terminal_id', 'callback_url'];

        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                throw GatewayException::invalidConfiguration('mabna', $field);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'mabna';
    }

    /**
     * @inheritDoc
     */
    public function request(PaymentRequest $request): PaymentResponse
    {
        $tokenUrl = $this->sandbox ? self::TOKEN_URL_SANDBOX : self::TOKEN_URL_PRODUCTION;
        $paymentUrl = $this->sandbox ? self::PAYMENT_URL_SANDBOX : self::PAYMENT_URL_PRODUCTION;

        // Build query string for token request
        $params = [
            'Amount' => $request->amount,
            'callbackURL' => $request->callbackUrl,
            'InvoiceID' => $request->orderId,
            'TerminalID' => $this->getConfig('terminal_id'),
        ];

        $data = http_build_query($params);

        try {
            $response = $this->makeHttpRequest('POST', $tokenUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $data,
            ]);

            $status = (int) ($response['Status'] ?? -1);
            $accessToken = $response['Accesstoken'] ?? $response['AccessToken'] ?? $response['Token'] ?? null;

            if ($status === 0 && $accessToken) {
                $trackingCode = $this->generateTrackingCode();

                return new PaymentResponse(
                    success: true,
                    trackingCode: $trackingCode,
                    paymentUrl: $paymentUrl,
                    referenceId: $accessToken,
                    message: 'Payment token received successfully',
                    rawResponse: [
                        'status' => $status,
                        'token' => $accessToken,
                        'terminal_id' => $this->getConfig('terminal_id'),
                    ],
                    formParams: [
                        'token' => $accessToken,
                    ]
                );
            }

            throw GatewayException::requestFailed(
                'mabna',
                $this->getErrorMessage($status),
                $status
            );
        } catch (\Exception $e) {
            if ($e instanceof GatewayException) {
                throw $e;
            }

            throw GatewayException::requestFailed('mabna', $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function verify(VerificationRequest $request): VerificationResponse
    {
        $verifyUrl = $this->sandbox ? self::VERIFY_URL_SANDBOX : self::VERIFY_URL_PRODUCTION;

        // Get data from callback
        $digitalReceipt = $request->getGatewayData('digitalreceipt') ?? $request->getGatewayData('CRN');
        $status = $request->getGatewayData('status');

        // Check callback status
        if ($status != 0) {
            throw GatewayException::verificationFailed(
                'mabna',
                $this->getErrorMessage((int) $status),
                (int) $status
            );
        }

        if (!$digitalReceipt) {
            throw GatewayException::verificationFailed(
                'mabna',
                'Digital receipt not found'
            );
        }

        // Build verify request
        $data = http_build_query([
            'digitalreceipt' => $digitalReceipt,
            'Tid' => $this->getConfig('terminal_id'),
        ]);

        try {
            $response = $this->makeHttpRequest('POST', $verifyUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $data,
            ]);

            $verifyStatus = (int) ($response['Status'] ?? -1);
            $returnId = $response['ReturnId'] ?? null;
            $message = $response['Message'] ?? '';

            if ($verifyStatus === 0) {
                return new VerificationResponse(
                    success: true,
                    referenceId: (string) $returnId,
                    cardNumber: null, // Mabna doesn't return card number
                    amount: 0, // Mabna doesn't return amount in verify
                    transactionId: $digitalReceipt,
                    message: $message ?: 'Payment verified successfully',
                    rawResponse: [
                        'status' => $verifyStatus,
                        'return_id' => $returnId,
                        'message' => $message,
                        'digital_receipt' => $digitalReceipt,
                    ]
                );
            }

            throw GatewayException::verificationFailed(
                'mabna',
                $message ?: $this->getErrorMessage($verifyStatus),
                $verifyStatus
            );
        } catch (\Exception $e) {
            if ($e instanceof GatewayException) {
                throw $e;
            }

            throw GatewayException::verificationFailed('mabna', $e->getMessage());
        }
    }

    /**
     * Get error message for Mabna error codes
     *
     * @param int $code
     * @return string
     */
    protected function getErrorMessage(int $code): string
    {
        $errors = [
            0 => 'Transaction successful',
            -1 => 'System error',
            -2 => 'Invalid input parameters',
            -3 => 'Terminal is inactive',
            -4 => 'Invalid transaction amount',
            -5 => 'Duplicate order number',
            -6 => 'Invalid date or time',
            -7 => 'Invalid callback URL',
            -8 => 'Invalid token',
            -9 => 'Token expired',
            -10 => 'Transaction not found',
            -11 => 'Transaction cancelled by user',
            -12 => 'Transaction already verified',
            -13 => 'Invalid reference number',
            -14 => 'Transaction failed',
            -15 => 'Invalid merchant',
        ];

        return $errors[$code] ?? "Unknown error (code: {$code})";
    }
}

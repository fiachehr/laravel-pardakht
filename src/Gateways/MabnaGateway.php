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
    // Sepehr token API 4.0.2. The previous :8081 host serves a certificate cURL rejects.
    protected const TOKEN_URL_PRODUCTION = 'https://sepehr.shaparak.ir/Rest/V1/PeymentApi/GetToken';
    protected const PAYMENT_URL_PRODUCTION = 'https://sepehr.shaparak.ir/Pay';
    protected const VERIFY_URL_PRODUCTION = 'https://sepehr.shaparak.ir/Rest/V1/PeymentApi/AdviceWithInvoicId';
    protected const ROLLBACK_URL_PRODUCTION = 'https://sepehr.shaparak.ir/Rest/V1/PeymentApi/Rollback';

    protected const TOKEN_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/Rest/V1/PeymentApi/GetToken';
    protected const PAYMENT_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/Pay';
    protected const VERIFY_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/Rest/V1/PeymentApi/AdviceWithInvoicId';
    protected const ROLLBACK_URL_SANDBOX = 'https://sandbox.banktest.ir/saderat/sepehr.shaparak.ir/Rest/V1/PeymentApi/Rollback';

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

        $params = [
            'TerminalID' => (string) $this->getConfig('terminal_id'),
            'Amount' => (string) $request->amount,
            'InvoiceID' => (string) $request->orderId,
            'callbackURL' => $request->callbackUrl,
            'payload' => (string) ($request->metadata['payload'] ?? ''),
        ];

        if ($request->mobile) {
            $params['CellNumber'] = $request->mobile;
        }

        try {
            $response = $this->makeHttpRequest('POST', $tokenUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => $params,
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
                        'TerminalID' => (string) $this->getConfig('terminal_id'),
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

        $digitalReceipt = $request->getGatewayData('digitalreceipt')
            ?? $request->getGatewayData('DigitalReceipt')
            ?? $request->getGatewayData('CRN');
        $respCode = $request->getGatewayData('respcode', $request->getGatewayData('status'));
        $invoiceId = $request->getGatewayData('invoiceid', $request->getGatewayData('InvoiceID'));

        if ((string) $respCode !== '0') {
            throw GatewayException::verificationFailed(
                'mabna',
                $this->getErrorMessage((int) $respCode),
                (int) $respCode
            );
        }

        if (!$digitalReceipt || $invoiceId === null || $invoiceId === '') {
            throw GatewayException::verificationFailed(
                'mabna',
                'Digital receipt or invoice id not found'
            );
        }

        try {
            $response = $this->makeHttpRequest('POST', $verifyUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'digitalreceipt' => $digitalReceipt,
                    'InvoiceID' => (string) $invoiceId,
                    'Tid' => (string) $this->getConfig('terminal_id'),
                ],
            ]);

            $verifyStatus = strtolower((string) ($response['Status'] ?? ''));
            $returnId = $response['ReturnId'] ?? null;
            $message = $response['Message'] ?? '';
            $expectedAmount = $request->getGatewayData('amount');

            if (in_array($verifyStatus, ['ok', 'duplicate'], true)) {
                if ($expectedAmount !== null && (int) $returnId !== (int) $expectedAmount) {
                    throw GatewayException::verificationFailed(
                        'mabna',
                        'Verified amount does not match the requested amount',
                        -4
                    );
                }

                return new VerificationResponse(
                    success: true,
                    referenceId: (string) $returnId,
                    cardNumber: $request->getGatewayData('cardnumber'),
                    amount: (int) $returnId,
                    transactionId: $digitalReceipt,
                    message: $message ?: 'Payment verified successfully',
                    rawResponse: [
                        'status' => $response['Status'] ?? null,
                        'return_id' => $returnId,
                        'message' => $message,
                        'digital_receipt' => $digitalReceipt,
                    ]
                );
            }

            $errorCode = is_numeric($returnId) ? (int) $returnId : -3;

            throw GatewayException::verificationFailed(
                'mabna',
                $message ?: $this->getErrorMessage($errorCode),
                $errorCode
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
            -1 => 'Transaction not found',
            -2 => 'IP mismatch or transaction already reversed',
            -3 => 'General gateway error',
            -4 => 'Callback URL does not match',
            -5 => 'IP address is not allowed',
            -6 => 'Rollback service is not enabled',
            -7 => 'Invoice id does not match the original transaction',
        ];

        return $errors[$code] ?? "Unknown error (code: {$code})";
    }
}

<?php

namespace Fiachehr\Pardakht\Gateways;

use Fiachehr\Pardakht\Exceptions\GatewayException;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\PaymentResponse;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationResponse;

/**
 * Class MellatGateway
 *
 * Implementation for Mellat Bank (Bank-e Mellat) payment gateway
 */
class MellatGateway extends AbstractGateway
{
    protected const WSDL_PRODUCTION = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    protected const WSDL_SANDBOX = 'https://sandbox.banktest.ir/mellat/bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    protected const PAYMENT_URL_PRODUCTION = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';
    protected const PAYMENT_URL_SANDBOX = 'https://sandbox.banktest.ir/mellat/bpm.shaparak.ir/pgwchannel/startpay.mellat';

    /**
     * @inheritDoc
     */
    protected function validateConfig(): void
    {
        $required = ['terminal_id', 'username', 'password', 'callback_url'];

        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                throw GatewayException::invalidConfiguration('mellat', $field);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'mellat';
    }

    /**
     * @inheritDoc
     */
    public function request(PaymentRequest $request): PaymentResponse
    {
        $wsdl = $this->sandbox ? self::WSDL_SANDBOX : self::WSDL_PRODUCTION;

        $parameters = [
            'terminalId' => (int) $this->getConfig('terminal_id'),
            'userName' => $this->getConfig('username'),
            'userPassword' => $this->getConfig('password'),
            'orderId' => (int) $request->orderId,
            'amount' => (int) $request->amount,
            'localDate' => date('Ymd'),
            'localTime' => date('His'),
            'additionalData' => substr($request->description ?? '', 0, 100),
            'callBackUrl' => $request->callbackUrl,
            'payerId' => (int) ($request->metadata['user_id'] ?? 0),
        ];

        // Retry logic for handling 502 Bad Gateway errors
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $soapOptions = [
                    'encoding' => 'UTF-8',
                    'trace' => true,
                    'exceptions' => true,
                    'connection_timeout' => 30,
                    'default_socket_timeout' => 30,
                ];

                if ($this->sandbox) {
                    $soapOptions['stream_context'] = stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                        'http' => [
                            'timeout' => 30,
                            'user_agent' => 'MellatGateway/1.0',
                        ]
                    ]);
                }

                $client = new \SoapClient($wsdl, $soapOptions);

                $soapResult = $client->__soapCall('bpPayRequest', [$parameters]);
                $result = is_array($soapResult) || is_object($soapResult) ? $soapResult->return : "-1";

                // Parse response: Format is "ResCode,RefId"
                $parts = explode(',', $result);
                $resCode = (int) ($parts[0] ?? -1);
                $refId = $parts[1] ?? null;

                if ($resCode === 0 && $refId) {
                    $trackingCode = $this->generateTrackingCode();
                    $paymentUrl = $this->sandbox ? self::PAYMENT_URL_SANDBOX : self::PAYMENT_URL_PRODUCTION;

                    return new PaymentResponse(
                        success: true,
                        trackingCode: $trackingCode,
                        paymentUrl: $paymentUrl,
                        referenceId: $refId,
                        message: 'Payment request successful',
                        rawResponse: [
                            'res_code' => $resCode,
                            'ref_id' => $refId,
                        ],
                        formParams: [
                            'RefId' => $refId,
                        ]
                    );
                }

                throw GatewayException::requestFailed(
                    'mellat',
                    $this->getErrorMessage($resCode),
                    $resCode
                );
            } catch (\SoapFault $e) {
                $lastException = $e;
                $errorMessage = $e->getMessage();

                // Check if it's a connection/gateway error (502, timeout, etc.)
                $isRetryableError = (
                    str_contains($errorMessage, '502') ||
                    str_contains($errorMessage, 'Bad Gateway') ||
                    str_contains($errorMessage, 'timeout') ||
                    str_contains($errorMessage, 'Connection') ||
                    str_contains($errorMessage, 'HTTP') ||
                    $e->getCode() === 0
                );

                if ($isRetryableError && $attempt < $maxRetries) {
                    // Wait before retrying
                    sleep($retryDelay * $attempt);
                    continue;
                }

                throw GatewayException::connectionFailed(
                    'mellat',
                    "Connection failed after {$attempt} attempt(s): " . $errorMessage
                );
            } catch (\Exception $e) {
                if ($e instanceof GatewayException) {
                    throw $e;
                }

                $lastException = $e;

                // Check if it's a retryable error
                $isRetryableError = (
                    str_contains($e->getMessage(), '502') ||
                    str_contains($e->getMessage(), 'Bad Gateway') ||
                    str_contains($e->getMessage(), 'timeout') ||
                    str_contains($e->getMessage(), 'Connection')
                );

                if ($isRetryableError && $attempt < $maxRetries) {
                    sleep($retryDelay * $attempt);
                    continue;
                }

                throw GatewayException::requestFailed(
                    'mellat',
                    "Request failed after {$attempt} attempt(s): " . $e->getMessage()
                );
            }
        }

        // If we get here, all retries failed
        throw GatewayException::connectionFailed(
            'mellat',
            "Failed to connect to gateway after {$maxRetries} attempts. Last error: " .
                ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * @inheritDoc
     */
    public function verify(VerificationRequest $request): VerificationResponse
    {
        $wsdl = $this->sandbox ? self::WSDL_SANDBOX : self::WSDL_PRODUCTION;

        // Get data from callback
        $refId = $request->getGatewayData('RefId');
        $resCode = $request->getGatewayData('ResCode');
        $saleOrderId = $request->getGatewayData('SaleOrderId');
        $saleReferenceId = $request->getGatewayData('SaleReferenceId');
        $cardHolderInfo = $request->getGatewayData('CardHolderInfo');

        // First check callback result
        if ($resCode != 0) {
            throw GatewayException::verificationFailed(
                'mellat',
                $this->getErrorMessage((int) $resCode),
                (int) $resCode
            );
        }

        // Retry logic for handling 502 Bad Gateway errors
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $soapOptions = [
                    'encoding' => 'UTF-8',
                    'trace' => true,
                    'exceptions' => true,
                    'connection_timeout' => 30,
                    'default_socket_timeout' => 30,
                ];

                if ($this->sandbox) {
                    $soapOptions['stream_context'] = stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                        'http' => [
                            'timeout' => 30,
                            'user_agent' => 'MellatGateway/1.0',
                        ]
                    ]);
                }

                $client = new \SoapClient($wsdl, $soapOptions);

                // Verify transaction
                $verifyParams = [
                    'terminalId' => (int) $this->getConfig('terminal_id'),
                    'userName' => $this->getConfig('username'),
                    'userPassword' => $this->getConfig('password'),
                    'orderId' => (int) $saleOrderId,
                    'saleOrderId' => (int) $saleOrderId,
                    'saleReferenceId' => (int) $saleReferenceId,
                ];

                $verifyResponse = $client->__soapCall('bpVerifyRequest', [$verifyParams]);
                $verifyCode = (int) ($verifyResponse->return ?? -1);

                if ($verifyCode !== 0) {
                    throw GatewayException::verificationFailed(
                        'mellat',
                        $this->getErrorMessage($verifyCode),
                        $verifyCode
                    );
                }

                // Settle (confirm) transaction
                $settleParams = [
                    'terminalId' => (int) $this->getConfig('terminal_id'),
                    'userName' => $this->getConfig('username'),
                    'userPassword' => $this->getConfig('password'),
                    'orderId' => (int) $saleOrderId,
                    'saleOrderId' => (int) $saleOrderId,
                    'saleReferenceId' => (int) $saleReferenceId,
                ];

                $settleResponse = $client->__soapCall('bpSettleRequest', [$settleParams]);
                $settleCode = (int) ($settleResponse->return ?? -1);

                if ($settleCode !== 0 && $settleCode !== 45) { // 45 means already settled
                    throw GatewayException::verificationFailed(
                        'mellat',
                        $this->getErrorMessage($settleCode),
                        $settleCode
                    );
                }

                return new VerificationResponse(
                    success: true,
                    referenceId: $saleReferenceId,
                    cardNumber: $cardHolderInfo,
                    transactionId: $refId,
                    message: 'Payment verified and settled successfully',
                    rawResponse: [
                        'verify_code' => $verifyCode,
                        'settle_code' => $settleCode,
                        'sale_order_id' => $saleOrderId,
                        'sale_reference_id' => $saleReferenceId,
                    ]
                );
            } catch (\SoapFault $e) {
                $lastException = $e;
                $errorMessage = $e->getMessage();

                // Check if it's a connection/gateway error (502, timeout, etc.)
                $isRetryableError = (
                    str_contains($errorMessage, '502') ||
                    str_contains($errorMessage, 'Bad Gateway') ||
                    str_contains($errorMessage, 'timeout') ||
                    str_contains($errorMessage, 'Connection') ||
                    str_contains($errorMessage, 'HTTP') ||
                    $e->getCode() === 0
                );

                if ($isRetryableError && $attempt < $maxRetries) {
                    // Wait before retrying
                    sleep($retryDelay * $attempt);
                    continue;
                }

                throw GatewayException::connectionFailed(
                    'mellat',
                    "Connection failed during verification after {$attempt} attempt(s): " . $errorMessage
                );
            } catch (\Exception $e) {
                if ($e instanceof GatewayException) {
                    throw $e;
                }

                $lastException = $e;

                // Check if it's a retryable error
                $isRetryableError = (
                    str_contains($e->getMessage(), '502') ||
                    str_contains($e->getMessage(), 'Bad Gateway') ||
                    str_contains($e->getMessage(), 'timeout') ||
                    str_contains($e->getMessage(), 'Connection')
                );

                if ($isRetryableError && $attempt < $maxRetries) {
                    sleep($retryDelay * $attempt);
                    continue;
                }

                throw GatewayException::verificationFailed(
                    'mellat',
                    "Verification failed after {$attempt} attempt(s): " . $e->getMessage()
                );
            }
        }

        // If we get here, all retries failed
        throw GatewayException::connectionFailed(
            'mellat',
            "Failed to verify payment after {$maxRetries} attempts. Last error: " .
                ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Get error message for Mellat error codes
     *
     * @param int $code
     * @return string
     */
    protected function getErrorMessage(int $code): string
    {
        $errors = [
            -1 => 'Transaction failed',
            0 => 'Transaction successful',
            11 => 'Invalid card number',
            12 => 'Insufficient balance',
            13 => 'Incorrect PIN',
            14 => 'PIN entry attempts exceeded',
            15 => 'Invalid card',
            16 => 'Withdrawal limit exceeded',
            17 => 'Transaction cancelled by user',
            18 => 'Card has expired',
            19 => 'Withdrawal amount exceeded',
            21 => 'Invalid merchant',
            23 => 'Security error',
            24 => 'Invalid merchant credentials',
            25 => 'Invalid amount',
            31 => 'Invalid response',
            32 => 'Invalid data format',
            33 => 'Invalid account',
            34 => 'System error',
            35 => 'Invalid date',
            41 => 'Duplicate request number',
            42 => 'Sale transaction not found',
            43 => 'Verify request already submitted',
            44 => 'Verify request not found',
            45 => 'Transaction already settled',
            46 => 'Transaction not settled',
            47 => 'Settle transaction not found',
            48 => 'Transaction reversed',
            49 => 'Refund transaction not found',
            51 => 'Duplicate transaction',
            54 => 'Reference transaction not found',
            55 => 'Invalid transaction',
            61 => 'Deposit error',
            62 => 'Callback URL domain not registered',
            98 => 'Static PIN usage limit reached',
            111 => 'Invalid card issuer',
            112 => 'Card issuer switch error',
            113 => 'No response from card issuer',
            114 => 'Cardholder not authorized for this transaction',
            412 => 'Invalid bill identifier',
            413 => 'Invalid payment identifier',
            414 => 'Invalid bill issuer',
            415 => 'Session timeout',
            416 => 'Error registering data',
            417 => 'Invalid payer identifier',
            418 => 'Customer information error',
            419 => 'Data entry attempts exceeded',
            421 => 'Invalid IP address',
        ];

        return $errors[$code] ?? "Unknown error (code: {$code})";
    }
}

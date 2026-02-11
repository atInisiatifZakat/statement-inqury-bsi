<?php

declare(strict_types=1);

namespace Inisiatif\Bsi;

use DateTimeInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Illuminate\Support\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

final class BsiClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly BsiCredentials $credentials)
    {
        $this->logger = new NullLogger;
    }

    public function getToken(): string
    {
        $params = [
            'api_key' => $this->credentials->apiKey,
            'cust_id' => $this->credentials->custId,
            'user_id' => $this->credentials->userId,
            'password' => md5($this->credentials->password),
        ];

        $this->loggerRequest('Get Token', $params);

        $queryString = http_build_query($params);
        $response = $this->getHttpClient()->post('/api/gettoken?' . $queryString);

        $this->loggerResponse('Get Token', $response);

        return (string) $response->json('token');
    }

    public function generateSignature(array $params): string
    {
        $filteredParams = array_filter($params, function ($value) {
            return ! is_null($value) && $value !== '' && ! is_array($value) && ! is_object($value);
        });

        ksort($filteredParams);

        $data = urldecode(http_build_query($filteredParams));

        $this->loggerRequest('Generate Signature RSA', ['data' => $data]);

        $response = $this->getHttpClient()
            ->post('/api/generate/signature/rsa', ['data' => $data]);

        $this->loggerResponse('Generate Signature RSA', $response);

        return (string) $response->json('signature');
    }

    public function getAccountStatement(string $accountNumber, DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        $token = $this->getToken();

        $params = [
            'api_key' => $this->credentials->apiKey,
            'channel_id' => $this->credentials->channelId,
            'cust_id' => $this->credentials->custId,
            'user_id' => $this->credentials->userId,
            'account_number' => $accountNumber,
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $signature = $this->generateSignature($params);

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-SIGNATURE' => $signature,
        ];

        $this->loggerRequest('Account Statement Single', $params, $headers);

        $queryString = http_build_query($params);
        $response = $this->getHttpClient()
            ->withHeaders($headers)
            ->post('/api/accountstatement/single?' . $queryString);

        $this->loggerResponse('Account Statement Single', $response);

        return collect($response->json('data.record') ?? []);
    }

    public function getInformationBalance(string $accountNumber): array
    {
        $token = $this->getToken();

        $params = [
            'api_key' => $this->credentials->apiKey,
            'channel_id' => $this->credentials->channelId,
            'cust_id' => $this->credentials->custId,
            'user_id' => $this->credentials->userId,
            'account_number' => $accountNumber,
        ];

        $signature = $this->generateSignature($params);

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-SIGNATURE' => $signature,
        ];

        $this->loggerRequest('Information Balance', $params, $headers);

        $queryString = http_build_query($params);
        $response = $this->getHttpClient()
            ->withHeaders($headers)
            ->post('/api/informationbalance?' . $queryString);

        $this->loggerResponse('Information Balance', $response);

        return $response->json('data') ?? [];
    }

    private function loggerRequest(string $name, array $params = [], array $headers = []): void
    {
        $this->logger->debug('Request - '.$name, [
            'params' => $this->maskSensitiveData($params),
            'headers' => $this->maskSensitiveData($headers),
        ]);
    }

    private function loggerResponse(string $name, Response $response): void
    {
        $this->logger->debug('Response - '.$name, [
            'status' => $response->status(),
            'body' => $this->maskSensitiveData($response->json() ?: []),
        ]);
    }

    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'api_key', 'password', 'cust_id', 'user_id',
            'Authorization', 'X-SIGNATURE', 'token', 'signature',
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveKeys, true)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }

    private function getHttpClient(): PendingRequest
    {
        $baseUrl = $this->credentials->isDevelopment ? $this->credentials->sandboxUrl : $this->credentials->productionUrl;

        $client = Http::baseUrl($baseUrl)->asJson()->acceptJson();

        // Disable SSL verification for self-signed certificates if needed
        if (! $this->credentials->verifySsl) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }
}

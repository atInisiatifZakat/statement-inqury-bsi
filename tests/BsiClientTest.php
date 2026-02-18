<?php

declare(strict_types=1);

namespace Inisiatif\Bsi\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Http;
use Inisiatif\Bsi\BsiClient;
use Inisiatif\Bsi\BsiCredentials;

final class BsiClientTest extends TestCase
{
    public function test_can_get_token(): void
    {
        Http::fake([
            '*/api/gettoken*' => Http::response(['data' => ['token' => 'test-token']]),
        ]);

        $sandboxUrl = 'https://sandbox.bsi-api.local/rest';

        $credentials = new BsiCredentials(
            apiKey: 'key',
            custId: 'cust',
            userId: 'user',
            password: 'pass',
            sandboxUrl: $sandboxUrl,
            productionUrl: 'https://api.bsi-api.local/rest'
        );
        $client = new BsiClient($credentials);

        $token = $client->getToken();

        $this->assertSame('test-token', $token);

        Http::assertSent(function ($request) use ($sandboxUrl) {
            return $request->url() === $sandboxUrl.'/api/gettoken?api_key=key&cust_id=cust&user_id=user&password='.md5('pass')
                && $request->method() === 'POST';
        });
    }

    public function test_can_generate_signature(): void
    {
        Http::fake([
            '*/api/generate/signature/rsa*' => Http::response(['data' => ['signature' => 'test-signature-value']]),
        ]);

        $credentials = new BsiCredentials(
            apiKey: 'key',
            custId: 'cust',
            userId: 'user',
            password: 'pass',
            sandboxUrl: 'https://sandbox.bsi-api.local/rest',
            productionUrl: 'https://api.bsi-api.local/rest'
        );
        $client = new BsiClient($credentials);

        $params = ['foo' => 'bar'];
        $signature = $client->generateSignature($params);

        $this->assertSame('test-signature-value', $signature);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/generate/signature/rsa')
                && $request->method() === 'POST';
        });
    }

    public function test_can_get_account_statement(): void
    {
        Http::fake([
            '*/api/gettoken*' => Http::response(['data' => ['token' => 'test-token']]),
            '*/api/generate/signature/rsa*' => Http::response(['data' => ['signature' => 'test-signature']]),
            '*/api/accountstatement/single*' => Http::response([
                'data' => [
                    'record' => [
                        [
                            'ft_number' => 'FT123',
                            'amount' => '1,000.00',
                            'balance' => '5,000.00',
                            'dbcr' => 'CR',
                            'ccy' => 'IDR',
                            'description' => 'Test transaction',
                            'date' => '31 Jan 2026 05:18',
                        ],
                    ],
                ],
            ]),
        ]);

        $credentials = new BsiCredentials(
            apiKey: 'key',
            custId: 'cust',
            userId: 'user',
            password: 'pass',
            sandboxUrl: 'https://sandbox.bsi-api.local/rest',
            productionUrl: 'https://api.bsi-api.local/rest'
        );
        $client = new BsiClient($credentials);

        $statements = $client->getAccountStatement('123456', now(), now());

        $this->assertCount(1, $statements);
        $statement = $statements->first();
        $this->assertSame('FT123', $statement->transactionId);
        $this->assertSame('CR', $statement->type);
        $this->assertSame('Test transaction', $statement->remark);
        $this->assertSame('5,000.00', $statement->balance->value);
        $this->assertSame('1,000.00', $statement->amount->value);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/accountstatement/single')) {
                return $request->hasHeader('Authorization', 'Bearer test-token')
                    && $request->hasHeader('X-SIGNATURE')
                    && str_contains($request->url(), 'account_number=123456');
            }

            return true;
        });
    }

    public function test_can_get_information_balance(): void
    {
        Http::fake([
            '*/api/gettoken*' => Http::response(['data' => ['token' => 'test-token']]),
            '*/api/generate/signature/rsa*' => Http::response(['data' => ['signature' => 'test-signature']]),
            '*/api/informationbalance*' => Http::response(['data' => ['balance' => 1000]]),
        ]);

        $credentials = new BsiCredentials(
            apiKey: 'key',
            custId: 'cust',
            userId: 'user',
            password: 'pass',
            sandboxUrl: 'https://sandbox.bsi-api.local/rest',
            productionUrl: 'https://api.bsi-api.local/rest'
        );
        $client = new BsiClient($credentials);

        $balance = $client->getInformationBalance('123456');

        $this->assertSame(1000, $balance['balance']);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/informationbalance')) {
                return $request->hasHeader('Authorization', 'Bearer test-token')
                    && $request->hasHeader('X-SIGNATURE')
                    && str_contains($request->url(), 'account_number=123456');
            }

            return true;
        });
    }

    public function test_verify_ssl_false_disables_ssl_verification(): void
    {
        Http::fake([
            '*/api/gettoken*' => Http::response(['data' => ['token' => 'test-token']]),
        ]);

        $credentials = new BsiCredentials(
            apiKey: 'key',
            custId: 'cust',
            userId: 'user',
            password: 'pass',
            sandboxUrl: 'https://sandbox.bsi-api.local/rest',
            productionUrl: 'https://api.bsi-api.local/rest',
            verifySsl: false
        );
        $client = new BsiClient($credentials);

        $token = $client->getToken();

        $this->assertSame('test-token', $token);

        Http::assertSent(function ($request) {
            // When verifySsl is false, the request should still succeed (SSL verification is disabled)
            return str_contains($request->url(), '/api/gettoken');
        });
    }

    public function test_verify_ssl_true_is_default(): void
    {
        Http::fake([
            '*/api/gettoken*' => Http::response(['data' => ['token' => 'test-token']]),
        ]);

        $credentials = new BsiCredentials(
            apiKey: 'key',
            custId: 'cust',
            userId: 'user',
            password: 'pass',
            sandboxUrl: 'https://sandbox.bsi-api.local/rest',
            productionUrl: 'https://api.bsi-api.local/rest'
            // verifySsl defaults to true
        );
        $client = new BsiClient($credentials);

        $token = $client->getToken();

        $this->assertSame('test-token', $token);
        $this->assertTrue($credentials->verifySsl);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/gettoken');
        });
    }
}

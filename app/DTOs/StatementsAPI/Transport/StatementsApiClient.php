<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Transport;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;
use App\DTOs\StatementsAPI\Requests\GetAccountBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetAllStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetHealthRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetIntraDayStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementTransactionsRequestDTO;
use App\DTOs\StatementsAPI\Responses\Balances\BalancesReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\Health\HealthResponseDTO;
use App\DTOs\StatementsAPI\Responses\Statements\StatementReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\StatementTransactions\TransactionReadResponseDTO;
use App\DTOs\StatementsAPI\Support\FromArray;
use App\DTOs\StatementsAPI\Support\StatementsApiClientInterface;
use App\Traits\InteractsWithDatabaseLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Concrete transport for the ABSA Statements Facade API.
 *
 * Each operation converts a `*RequestDTO` into a typed outbound GET (path
 * params, query string, ABSA headers, mTLS options, bearer auth) and maps a
 * 2xx JSON body into the matching `*ResponseDTO`, or a non-2xx into a
 * `StatementsApiException` carrying an `ErrorResponseDTO`.
 *
 * Transient failures (connection errors and the server-side / rate-limit
 * responses in {@see self::RETRYABLE_STATUSES}) are retried up to
 * `config->retryAttempts` with a `config->retryDelayMs` backoff; a non-2xx that
 * is not retriable, or that exhausts its retries, is reported as a
 * `StatementsApiException`, as is a connection failure (status 0).
 *
 * Callers depend on `StatementsApiClientInterface`, never on `Http` directly.
 */
final class StatementsApiClient implements StatementsApiClientInterface
{
    use InteractsWithDatabaseLog;

    /** Default per-request timeout in seconds (not yet a config field). */
    private const int DEFAULT_TIMEOUT_SECONDS = 30;

    /** Transient status codes that warrant a retry (server / rate-limit). */
    private const array RETRYABLE_STATUSES = [408, 429, 500, 502, 503, 504];

    /** Maximum characters of a raw error body captured for diagnostics. */
    private const int ERROR_BODY_MAX_LENGTH = 500;

    /**
     * Logger Name
     */
    protected string $loggerName = 'ABSA API - StatementsApiClient';

    public function __construct(private readonly StatementsApiClientConfig $config) {}

    public function getHealth(GetHealthRequestDTO $request): HealthResponseDTO
    {
        return $this->send(
            '/health',
            [],
            $request->query(),
            $request->headers(),
            HealthResponseDTO::class,
        );
    }

    public function getBalances(GetBalancesRequestDTO $request): BalancesReadResponseDTO
    {
        return $this->send(
            '/balances',
            [],
            $request->query(),
            $request->headers(),
            BalancesReadResponseDTO::class,
        );
    }

    public function getAccountBalances(GetAccountBalancesRequestDTO $request): BalancesReadResponseDTO
    {
        return $this->send(
            '/accounts/{accountId}/balances',
            $request->path(),
            $request->query(),
            $request->headers(),
            BalancesReadResponseDTO::class,
        );
    }

    public function getStatements(GetStatementsRequestDTO $request): StatementReadResponseDTO
    {
        return $this->send(
            '/accounts/{accountId}/statements',
            $request->path(),
            $request->query(),
            $request->headers(),
            StatementReadResponseDTO::class,
        );
    }

    public function getStatement(GetStatementRequestDTO $request): StatementReadResponseDTO
    {
        return $this->send(
            '/accounts/{accountId}/statements/{statementId}',
            $request->path(),
            $request->query(),
            $request->headers(),
            StatementReadResponseDTO::class,
        );
    }

    public function getStatementTransactions(GetStatementTransactionsRequestDTO $request): TransactionReadResponseDTO
    {
        return $this->send(
            '/accounts/{accountId}/statements/{statementId}/transactions',
            $request->path(),
            $request->query(),
            $request->headers(),
            TransactionReadResponseDTO::class,
        );
    }

    public function getAllStatements(GetAllStatementsRequestDTO $request): StatementReadResponseDTO
    {
        return $this->send(
            '/statements',
            [],
            $request->query(),
            $request->headers(),
            StatementReadResponseDTO::class,
        );
    }

    public function getIntraDayStatement(GetIntraDayStatementRequestDTO $request): TransactionReadResponseDTO
    {
        return $this->send(
            '/accounts/{accountId}/intraday-statement',
            $request->path(),
            $request->query(),
            $request->headers(),
            TransactionReadResponseDTO::class,
        );
    }

    /**
     * Execute a single GET and map the response into `$responseClass`.
     *
     * @template T of FromArray
     *
     * @param  array<string, string>  $path  ordered path-param name => value
     * @param  array<string, mixed>  $query  query-string key => value
     * @param  array<string, string>  $headers  ABSA cross-cutting headers
     * @param  class-string<T>  $responseClass
     * @return T
     */
    private function send(
        string $template,
        array $path,
        array $query,
        array $headers,
        string $responseClass,
    ): FromArray {
        // Log Request
        $this->logDb->debug('Sending Request...',
            [
                'template' => $template,
                'path' => $path,
                'query' => $query,
                'baseUrl' => $this->config->baseUrl,
                'timeout' => self::DEFAULT_TIMEOUT_SECONDS,
                'options' => $this->config->sslOptions(),
                'headers' => $headers,
                'responseClass' => $responseClass,
                'retryAttempts' => $this->config->retryAttempts,
                'retryDelayMs' => $this->config->retryDelayMs,
            ]
        );

        $request = Http::baseUrl($this->config->baseUrl)
            ->timeout(self::DEFAULT_TIMEOUT_SECONDS)
            ->withOptions($this->config->sslOptions())
            ->withHeaders($this->withAuthorization($headers));

        if ($this->config->retryAttempts > 1) {
            $request = $request->retry(
                $this->config->retryAttempts,
                $this->config->retryDelayMs,
                fn (\Throwable $e): bool => $this->shouldRetry($e),
                throw: false,
            );
        }

        $url = $this->requestUrl($template, $path);

        try {
            $response = $request->get($this->interpolatePath($template, $path), $query);
        } catch (ConnectionException $e) {
            throw new StatementsApiException(
                status: 0,
                error: null,
                method: 'GET',
                url: $url,
                previous: $e,
            );
        }

        if (! $response->successful()) {
            $rawBody = $response->body();

            throw new StatementsApiException(
                status: $response->status(),
                error: $this->decodeError($response),
                method: 'GET',
                url: $url,
                body: $rawBody === '' ? null : mb_substr($rawBody, 0, self::ERROR_BODY_MAX_LENGTH),
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        // Log Response
        $this->logDb->debug('Retrieved Response...',
            [
                'responseBody' => $body,
            ]
        );

        return $responseClass::fromArray($body);
    }

    /**
     * Prepend the resolved OAuth2 bearer token. The token is read from the
     * runtime config slot `absa.statements.api_key` at call time — it is
     * populated by `StatementService::resolveToken()` ahead of every outbound
     * call — so the immutable transport config never holds a credential.
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function withAuthorization(array $headers): array
    {
        $token = config('absa.statements.api_key');

        if (! is_string($token) || $token === '') {
            return $headers;
        }

        return array_merge($headers, ['Authorization' => 'Bearer '.$token]);
    }

    /**
     * Substitute each `{name}` placeholder with its URL-encoded value, using
     * the ordered path-param array so the template and the values stay aligned.
     *
     * @param  array<string, string>  $path
     */
    private function interpolatePath(string $template, array $path): string
    {
        foreach ($path as $name => $value) {
            $template = str_replace('{'.$name.'}', rawurlencode((string) $value), $template);
        }

        return $template;
    }

    /**
     * Resolve the absolute outbound URL (base + interpolated path) so error
     * diagnostics can report exactly which endpoint failed.
     *
     * @param  array<string, string>  $path
     */
    private function requestUrl(string $template, array $path): string
    {
        return rtrim($this->config->baseUrl, '/').'/'.ltrim($this->interpolatePath($template, $path), '/');
    }

    /**
     * Decode a non-2xx body into an `ErrorResponseDTO` when it is JSON.
     */
    private function decodeError(Response $response): ?ErrorResponseDTO
    {
        $body = $response->json();

        return is_array($body) ? ErrorResponseDTO::fromArray($body) : null;
    }

    /**
     * Decide whether a failed attempt should be retried.
     *
     * Retries are reserved for transient failures: connection errors and the
     * server-side / rate-limit responses in {@see self::RETRYABLE_STATUSES}.
     * A 4xx response is deterministic and is returned to the caller at once.
     */
    private function shouldRetry(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        return in_array($this->getStatusCodeFromException($e), self::RETRYABLE_STATUSES, true);
    }

    /**
     * Extract the HTTP status from a response-attached exception, if any.
     *
     * Laravel's `RequestException` (thrown for a non-2xx response) carries the
     * `Response` on a public `$response` property; a bare `ConnectionException`
     * has no response and therefore no status.
     */
    private function getStatusCodeFromException(\Throwable $e): ?int
    {
        if ($e instanceof RequestException && $e->response instanceof Response) {
            return $e->response->status();
        }

        return null;
    }
}

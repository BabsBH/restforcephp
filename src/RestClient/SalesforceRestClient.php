<?php
namespace EventFarm\Restforce\RestClient;

use EventFarm\Restforce\Oauth\AccessToken;
use EventFarm\Restforce\Oauth\SalesforceProviderInterface;
use EventFarm\Restforce\Oauth\RetryAuthorizationTokenFailedException;
use EventFarm\Restforce\TokenRefreshInterface;
use Psr\Http\Message\ResponseInterface;

class SalesforceRestClient
{
    const HALF_SECOND = 500000;

    /**
     * @var RestClientInterface
     */
    private $restClient;
    /**
     * @var SalesforceProviderInterface
     */
    private $salesforceProvider;
    /**
     * @var AccessToken
     */
    private $accessToken;
    /**
     * @var string
     */
    private $resourceOwnerUrl;
    /**
     * @var TokenRefreshInterface|null
     */
    private $tokenRefreshObject;
    /**
     * @var int
     */
    private $maxRetryRequests;
    /**
     * @var string
     */
    private $apiVersion;

    /**
     * SalesforceRestClient constructor.
     * @param RestClientInterface $restClient
     * @param SalesforceProviderInterface $salesforceProvider
     * @param AccessToken $accessToken
     * @param string $resourceOwnerUrl
     * @param TokenRefreshInterface|null $tokenRefreshObject
     * @param string $apiVersion
     * @param int $maxRetryRequests
     */
    public function __construct(
        RestClientInterface $restClient,
        SalesforceProviderInterface $salesforceProvider,
        AccessToken $accessToken,
        string $resourceOwnerUrl,
        $tokenRefreshObject,
        string $apiVersion,
        int $maxRetryRequests
    ) {
        $this->restClient = $restClient;
        $this->salesforceProvider = $salesforceProvider;
        $this->accessToken = $accessToken;
        $this->resourceOwnerUrl = $resourceOwnerUrl;
        $this->maxRetryRequests = $maxRetryRequests;
        $this->apiVersion = $apiVersion;
        $this->tokenRefreshObject = $tokenRefreshObject;
    }

    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        return $this->retryRequest(
            $method,
            $this->constructUrl($uri),
            $this->mergeOptions($options)
        );
    }

    public function getResourceOwnerUrl(): string
    {
        return $this->resourceOwnerUrl;
    }

    private function getAccessToken(): string
    {
        return $this->accessToken->getToken();
    }

    private function isResponseAuthorized(ResponseInterface $response): bool
    {
        return !($response->getStatusCode() === 401);
    }

    private function refreshAccessToken()
    {
        $refreshToken = $this->accessToken->getRefreshToken();

        $accessToken = $this->salesforceProvider->getAccessToken('refresh_token', [
            'refresh_token' => $refreshToken,
        ]);

        if (!empty($this->tokenRefreshObject)) {
            $this->tokenRefreshObject->tokenRefreshCallback($accessToken);
        }

        $this->accessToken = $accessToken;
    }

    private function mergeOptions(array $options): array
    {
        $defaultOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ],
        ];
        $options = array_merge_recursive($defaultOptions, $options);
        return $options;
    }

    private function retryRequest(string $method, string $uri, array $options): ResponseInterface
    {
        $attempt = 0;
        do {
            $response = $this->restClient->request($method, $uri, $options);
            $isAuthorized = $this->isResponseAuthorized($response);

            if (!$isAuthorized) {
                // Back off the token refresh retry to combat rapid
                // requests to salesforce not allowing the token to refresh.
                $this->delayRetry($attempt);
                $this->refreshAccessToken();
            }

            $attempt++;
        } while (!$isAuthorized && $attempt < $this->maxRetryRequests);

        if (!$isAuthorized) {
            throw new RetryAuthorizationTokenFailedException(
                'Max retry limit of ' . $this->maxRetryRequests . 'has been reached. oAuth Token Failed.'
            );
        }

        return $response;
    }

    private function constructUrl(string $endpoint): string
    {
        $beginsWithHttp = (substr($endpoint, 0, 7) === "http://") || (substr($endpoint, 0, 8) === "https://");

        if ($beginsWithHttp) {
            return $endpoint;
        }

        $baseUrl = $this->accessToken->getInstanceUrl() . '/services/data/' . $this->apiVersion . '/';
        return $baseUrl . $endpoint;
    }

    private function delayRetry(int $attempt)
    {
        usleep(self::HALF_SECOND + log($attempt));
    }
}

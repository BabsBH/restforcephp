<?php
namespace EventFarm\Restforce\Tests\RestClient;

use EventFarm\Restforce\Oauth\AccessToken;
use EventFarm\Restforce\Oauth\RetryAuthorizationTokenFailedException;
use EventFarm\Restforce\Oauth\StevenMaguireSalesforceProvider;
use EventFarm\Restforce\RestClient\GuzzleRestClient;
use EventFarm\Restforce\RestClient\SalesforceRestClient;
use EventFarm\Restforce\TokenRefreshInterface;
use Mockery;
use Psr\Http\Message\ResponseInterface;

class SalesforceRestClientTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionIsThrownWhenClientRetriesMoreThanMaxRetry()
    {
        $restClient = Mockery::mock(GuzzleRestClient::class);
        $provider = Mockery::mock(StevenMaguireSalesforceProvider::class);
        $accessToken = Mockery::mock(AccessToken::class);
        $tokenRefreshCallback = Mockery::mock(TokenRefreshInterface::class);

        $tokenRefreshCallback->shouldReceive('tokenRefreshCallback');

        $provider->shouldReceive('getAccessToken')
            ->andReturn($accessToken);

        $accessToken->shouldReceive('getToken')
            ->andReturn('MOCKACCESSTOKEN');
        $accessToken->shouldReceive('getInstanceUrl')
            ->andReturn('salesforce.com');
        $accessToken->shouldReceive('getRefreshToken')
            ->andReturn('TOKENSDKLJLKJWEF');

        $failedResponse = Mockery::mock(ResponseInterface::class);
        $failedResponse->shouldReceive('getStatusCode')
            ->andReturn(401);

        $restClient->shouldReceive('request')
            ->andReturn($failedResponse)
            ->times(3);

        $maxRetry = 3;
        $apiVersion = 'v37.0';
        $resourceOwnerUrl = "myResourceOwnerUrl";

        $salesforceProvider = new SalesforceRestClient(
            $restClient,
            $provider,
            $accessToken,
            $resourceOwnerUrl,
            $tokenRefreshCallback,
            $apiVersion,
            $maxRetry
        );

        $this->expectException(RetryAuthorizationTokenFailedException::class);
        $salesforceProvider->request('GET', '/example/getExample', []);
    }

    public function testFailThenRetryAndSucceedBeforeMaxRetryLimit()
    {
        $restClient = Mockery::mock(GuzzleRestClient::class);
        $provider = Mockery::mock(StevenMaguireSalesforceProvider::class);
        $accessToken = Mockery::mock(AccessToken::class);
        $tokenRefreshCallback = Mockery::mock(TokenRefreshInterface::class);

        $tokenRefreshCallback->shouldReceive('tokenRefreshCallback');

        $provider->shouldReceive('getAccessToken')
            ->andReturn($accessToken);

        $accessToken->shouldReceive('getToken')
            ->andReturn('MOCKACCESSTOKEN');
        $accessToken->shouldReceive('getInstanceUrl')
            ->andReturn('salesforce.com');

        $accessToken->shouldReceive('getRefreshToken')
            ->andReturn('TOKENSDKLJLKJWEF');

        $failedResponse = Mockery::mock(ResponseInterface::class);
        $failedResponse->shouldReceive('getStatusCode')
                       ->andReturn(401);

        $successResponse = Mockery::mock(ResponseInterface::class);
        $successResponse->shouldReceive('getStatusCode')
                        ->andReturn(200);

        $restClient->shouldReceive('request')
                   ->andReturn($failedResponse)
                   ->times(2);

        $restClient->shouldReceive('request')
                   ->andReturn($successResponse)
                   ->once();

        $maxRetry = 3;
        $apiVersion = 'v37.0';
        $resourceOwnerUrl = 'myResourceOwnerUrl';

        $salesforceProvider = new SalesforceRestClient(
            $restClient,
            $provider,
            $accessToken,
            $resourceOwnerUrl,
            $tokenRefreshCallback,
            $apiVersion,
            $maxRetry
        );

        $response = $salesforceProvider->request('GET', '/example/getExample');

        $this->assertSame(200, $response->getStatusCode());
    }
}

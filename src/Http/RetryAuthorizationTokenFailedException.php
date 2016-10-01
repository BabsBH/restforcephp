<?php
namespace Jmondi\Restforce\Http;

/**
 * An exception for when the max retry limit
 * is reached when trying to refresh the api
 * token when making a salesforce request.
 */
class RetryAuthorizationTokenFailedException extends \Exception
{

}

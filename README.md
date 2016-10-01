# Restforce PHP

This is meant to emulate what the [ejhomes/restforce gem](https://github.com/ejholmes/restforce) is doing.

## Example Client Implementation

```php
<?php
namespace App;

use Stevenmaguire\OAuth2\Client\Token\AccessToken;
use Jmondi\Restforce\Token\TokenRefreshCallbackInterface;

class DemoClient implements TokenRefreshCallbackInterface
{

    const SALESFORCE_CLIENT_ID = 'your salesforce client id';
    const SALESFORCE_CLIENT_SECRET = 'your salesforce client secret';
    const SALESFORCE_CALLBACK = 'callback URL to catch $_GET['code'] to generate AccessToken';
    const ACCESS_TOKEN = 'access token string, different from AccessToken object';
    const REFRESH_TOKEN = 'refresh token sring';
    const INSTANCE_URL = 'salesforce instance url';
    const RESOURCE_OWNER_ID = 'url to salesforce authd user info (from AccessToken)';

    public function tokenRefreshCallback(AccessToken $token):void
    {
        // CALLBACK FUNCTION TO STORE THE
        // NEWLY REFRESHED ACCESS TOKEN
    }
    
    public function getClient()
    {
        $accessToken = $this-getAccessToken();
        
        $client =
            new SalesforceProviderRestClient(
                new GuzzleRestClient(
                    new \GuzzleHttp\Client([
                        'base_uri' => $baseUri,
                        'http_errors' => false
                    ])
                ),
                new SalesforceProvider([
                    'clientId' => self::SALESFORCE_CLIENT_ID,
                    'clientSecret' => self::SALESFORCE_CLIENT_SECRET,
                    'redirectUri' => self::SALESFORCE_CALLBACK,
                ]),
                $accessToken,
                $this
            );
        
        $restforce = new RestforceClient(
            $client,
            self::ACCESS_TOKEN,
            self::REFRESH_TOKEN,
            self::INSTANCE_URL,
            self::SALESFORCE_CLIENT_ID,
            self::SALESFORCE_CLIENT_SECRET,
            self::SALESFORCE_CALLBACK,
            self::RESOURCE_OWNER_ID,
            $this
        );
    }
    
    private function getAccessToken():AccessToken
    {
        $accessToken = getAccessTokenFromCache()
        return $accessToken;
    }
}
```

## Usage

#### Limits
[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/resources_limits.htm?search_text=limits) Returns a list of daily API limits for the salesforce api. Refer to the docs for the full list of options.
`public function limits():stdClass`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->limits();
// { ... }
```


#### UserInfo

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/resources_limits.htm?search_text=limits) Get info about the logged-in user.

`public function limits():stdClass`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->userInfo();
// { ... }
```

#### Query

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_query.htm) Use the Query resource to execute a SOQL query that returns all the results in a single response.

`public function query(string $query):stdClass`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->query('SELECT Id, Name FROM Account);
// { ... }
```

#### QueryAll

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_queryall.htm) Include SOQL that includes deleted items.

`public function queryAll(string $query):stdClass`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->queryAll('SELECT Id, Name FROM Account);
// { ... }
```

#### Explain
[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_query_explain.htm) Get feedback on how Salesforce will execute your query, report, or list view.

`public function explain(string $query):stdClass`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->explain('SELECT Id, Name FROM Account);
// { ... }
```

#### Find

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_get_field_values.htm?search_text=limits) Find resource `$id` of `$type`, optionally specify the fields you want to retrieve in the fields parameter and use the GET method of the resource.

`public function find(string $type, string $id, array $fields = []):stdClass`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->find('Account', '001410000056Kf0AAE');
// { ... }
```

#### Describe

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/resources_sobject_describe.htm?search_text=describe) Completely describes the individual metadata at all levels for the specified object.

`public function describe(string $type):stdClass`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->describe('Account');
// { ... }
```


#### Create

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_sobject_create.htm) Create new records of `$type`. The response body will contain the ID of the created record if the call is successful.

`public function create(string $type, array $data):stdClass`

``` 
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->create('Account', [
    'Name' => 'Foo Bar'
]);
// '001i000001ysdBGAAY'` 
```

#### Update

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_update_fields.htm?search_text=describe) You use the SObject Rows resource to update records. The response will be the a bool of `$success`.

`public function update(string $type, string $id, array $data):bool`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->update('Account', '001i000001ysdBGAAY' [
    'Name' => 'Foo Bar Two'
]);
// true|false
```

#### Destroy

[Docs](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_delete_record.htm?search_text=describe) Delete record of `$type` and `$id`. The response will be the a bool of `$success`.

`public function destroy(string $type, string $id):bool`

```
$demoSalesforceClient = new DemoSalesforceClient();
$restforce = $demoSalesforceClient->getClient();
$restforce->destroy('Account', '001i000001ysdBGAAY');
// true|false
```

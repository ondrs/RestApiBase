REST API Base
=============

Base components for JSON REST API for Nette applications

namespace: `Clevis\RestApi`


###ApiRoute
Route for REST requests

usage:
```
// for POST only
$router[] = new RestRoute('/api/login', 'User:login', RestRoute::METHOD_POST);

// for SSL secured request
$router[] = new RestRoute('/api/login', 'User:login', RestRoute::METHOD_POST | RestRoute::SECURE);

// for all methods
$route = new RestRoute('/api/login', 'User:', RestRoute::RESTFUL);
$router[] = $route;
// optional - set HTTP method to presenter action translation table. (these are defults)
$route->setRestDictionary(array(
	'GET' => 'get',
	'POST' => 'append',
	'PUT' => 'create',
	'PATCH' => 'update',
	'DELETE' => 'delete',
))
```


###ApiPresenter
Simplified presenter for JSON requsts and responses

Presenter lifecycle:

1) calls `startup()`
 - you can configure presenter internals here

2) SSL validation
 - it is recomended to always use SSL for API
 - set `$checkSsl` to `FALSE` for testing without SSL (by default is on)

3) API version validation based on `X-Api-Version` header
 - set `$minApiVersion` and `$minApiVersion` to configure

4) data decoding and schema validation
 - by default presenter decodes and validates POST data for all POST, PUT and PATCH requests
 - GET, DELETE etc. are not allowed to have any data. if they have, the data are ignored
 - presenter automacally loads a JSON validation schema from file `../schemas/{presenter}/{action}.json`
 - defaults schema location can be changed by overloading method `formatSchemaFiles()`
 - schema validation can be turned off or validation path changed by annotation `@schema` over the action

5) user authentication based on `X-Api-Key` header
 - set `$checkAccess` to `FALSE` to turn autenication off (by default is on)
 - authenticator must be set via `setAuthenticator()`
 - authenticator also logs the user in and creates the `$apiKey` - see `IApiAuthenticator` for more

6) calls `actionXyz()`
 - remember ApiPresenter does not use methods named `renderXyz` as Nette presenters do (it does not render anything)

7) filtering of response data
 - all `DateTime` values are converted to string
 - all `NULL` values are completely removed to save bandwidth

8) calls $apiLogger::logRequest() if the logger has been set via `setApiLogger()`
 - see `IApiLogger` for more

9) returns the response

####returning results:
You can either:
 - write data to the `$payload` property
 - or use method sendSuccessResponse($data, $responseCode)
 - or sendErrorReponse($responseCode, $message) for errors


###ApiResponse
Sends response data to client



###*IApiAuthenticator*
Interface for API authentication service


###*IApiUser*
Interface of user entity returned by `IApiAuthenticator`


###*IApiLogger*
Interface for logging service

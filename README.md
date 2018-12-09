# Larave-Api-JWT
### To write api please follow the link below
### Run the command
```php
    composer require "tymon/jwt-auth":"^1.0.0-rc.2"
    
```
### Get Logged In User Id
```php
public $user;
public function __construct()
{
    $this->user = \JWTAuth::parseToken()->authenticate();
}
or
$user = \JWTAuth::parseToken()->toUser();
```
### Get Token Storage

Got to config/jwt.php and find the storage path
### Follow the both links
[https://jwt-auth.readthedocs.io/en/develop/laravel-installation](https://jwt-auth.readthedocs.io/en/develop/laravel-installation/)

and

[https://blog.pusher.com/laravel-jwt](https://blog.pusher.com/laravel-jwt)
### Routes
```php
Route::group(['middleware' => 'jwt.verify', 'namespace' => 'Api'], function() {
    Route::get('user', 'AuthController@getAuthenticatedUser');
    Route::get('closed', 'PostController@closed');
});

```
### Resource api
just use
```php
php artisan make:resource studentapiresource

```
### Register and Login
```php
use JWTFactory;
use Tymon\JWTAuth\Facades\JWTAuth;

public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255|unique:users',
        'name' => 'required',
        'password'=> 'required'
    ]);
    if ($validator->fails()) 
    {
        return response()->json($validator->errors());
    }
    User::create([
        'name' => $request->get('name'),
        'email' => $request->get('email'),
        'password' => bcrypt($request->get('password')),
    ]);
    $user = User::first();
    Profile::Create(['user_id'=>$user->id]);
    if($token = JWTAuth::fromUser($user))
    {
        return Response::json(['status_code'=> 200, 'data'=> 'Account created successfully'],200);
    }
    return Response::json(['status_code'=> 203, 'data'=> 'unbale to create account.'],203);

}
```
login
```php
public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255',
        'password'=> 'required'
    ]);
    if ($validator->fails()) 
    {
        return response()->json($validator->errors());
    }
    $credentials = $request->only('email', 'password');
    try 
    {
        if (! $token = JWTAuth::attempt($credentials)) 
        {
            return response()->json(['status_code'=>422, 'user'=> null,'error' => 'invalid_credentials','token'=>null], 422);
        }
    } 
    catch (JWTException $e) 
    {
        return response()->json(['status_code'=>500, 'error' => 'could_not_create_token'], 500);
    }
    $user = JWTAuth::toUser($token);
    return response()->json(['status_code'=>200, 'user'=>$user,'token'=>$token]);
}
```
# Facebook API
### Create an facebook developer account just follow this link

[https://developers.facebook.com/](https://developers.facebook.com/)


### Facebook comment just folllow this link
[http://blog.naimehossain.com/how-to-add-facebook/](http://blog.naimehossain.com/how-to-add-facebook/)

### Copy this code and past in header page on your App
```js
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.2&appId=Your id goes here &autoLogAppEvents=1';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
</script>
```
### Inside your single post past this code
```html
<div class="fb-comments" data-href="https://developers.facebook.com/docs/plugins/comments#configurator" data-numposts="5"></div>
```

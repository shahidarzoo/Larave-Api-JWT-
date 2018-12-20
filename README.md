# Larave-Api-JWT
### Laravel Shopping Cart
Follow the link to make shopping cart in laravel

[http://webmobtuts.com/backend-development/creating-a-shopping-cart-with-laravel/](http://webmobtuts.com/backend-development/creating-a-shopping-cart-with-laravel/)

### Multiple Images upload in laravel
[https://appdividend.com/2018/02/05/laravel-multiple-images-upload-tutorial/](https://appdividend.com/2018/02/05/laravel-multiple-images-upload-tutorial/)
### Crop image In laravel
To crop image in laravel follow below link
[https://itsolutionstuff.com/post/laravel-5-ajax-crop-image-before-upload-using-using-croppie-pluginexample.html](https://itsolutionstuff.com/post/laravel-5-ajax-crop-image-before-upload-using-using-croppie-pluginexample.html)
### To write api please follow the link below
# API Start Here
### Api Run the command
```php
    composer require "tymon/jwt-auth":"^1.0.0-rc.2"
    
```
### Get Logged In User Id
```php
public $user;
public function __construct()
{
    $this->user = \JWTAuth::parseToken()->authenticate();
    or
    $this->user = \JWTAuth::parseToken()->toUser();
}
public function test()
{
    print_r($this->user->id);exit;
}
```
### Get Token Storage

Got to config/jwt.php and find the storage path
### Follow the both links
[https://jwt-auth.readthedocs.io/en/develop/laravel-installation](https://jwt-auth.readthedocs.io/en/develop/laravel-installation/)

and

[https://blog.pusher.com/laravel-jwt](https://blog.pusher.com/laravel-jwt)
### Routes
Open Api.php File form above clone area

### Resource api
just use
```php
php artisan make:resource studentapiresource

```
### Login With Google and facebook in Laravel
Copy the code and past inside config/service.php 
```php
'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT')
    ],
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT')
    ],
```
### Fallow these links for google and facebook
#### For Google keys
[https://console.developers.google.com/apis/credentials?project=csvlive&folder&organizationId](https://console.developers.google.com/apis/credentials?project=csvlive&folder&organizationId)

### For Facebook keys

[https://appdividend.com/2017/07/12/laravel-facebook-login/](https://appdividend.com/2017/07/12/laravel-facebook-login/)

[https://developers.facebook.com/](https://developers.facebook.com/)

inside .env file
```php
Google 
GOOGLE_CLIENT_ID=your client id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=secret key
GOOGLE_REDIRECT="http://csvlive.org/callback"

Facebook
FACEBOOK_CLIENT_ID=424324320208
FACEBOOK_CLIENT_SECRET=7d5ceb16c2cwrewrewe81989ac70245
FACEBOOK_REDIRECT="http://csvlive.org/facebook_callback"
```
### Route setup
```php
Google
Route::get('/redirect', 'Auth\LoginController@redirectToProvider');
Route::get('/callback', 'Auth\LoginController@handleProviderCallback');

Facebook

Route::get('/facebook_redirect', 'Auth\LoginController@redirectToFacebookProvider');
Route::get('/facebook_callback', 'Auth\LoginController@handleFacebookProviderCallback');
```
### LoginController inside Auth\LoginController
```php
public function redirectToProvider()
{
   return Socialite::driver('google')->redirect();
}

public function handleProviderCallback(Request $request)
{
    try 
    {
         $user = Socialite::driver('google')->user();
    } 
    catch (\Exception $e) 
    {
        return redirect('/login');
    }
    // check if they're an existing user
     $existingUser = User::where('email', $user->email)->first();
     if($existingUser)
     {
         // log them in
          auth()->login($existingUser, true);
     } 
     else 
     {
            $newUser = new User();
            $newUser->name            = $user->name;
            $newUser->email           = $user->email;
            $newUser->password = bcrypt(str_random(15));
            $newUser->google_id       = $user->id;
            $newUser->save();
            auth()->login($newUser, true);
     }
            return redirect()->to('/home');
    }
    
    // Facebook
public function redirectToFacebookProvider()
{
    return Socialite::driver('facebook')->redirect();
}
public function handleFacebookProviderCallback(Request $request)
{
        try 
        {
            $user = Socialite::driver('facebook')->user();
        } 
        catch (\Exception $e) 
        {
            return redirect('/login');
        }
        
        // check if they're an existing user
        $existingUser = User::where('email', $user->getEmail())->first();
        if($existingUser){
            // log them in
            auth()->login($existingUser, true);
        } else {
            /*$full_name = explode(" ", trim($user->getName()));
            $new_user->first_name = $full_name[0];
            $new_user->last_name = $full_name[1];*/
            $newUser = new User();
            $newUser->name            = $user->name;
            $newUser->email           = $user->email;
            $newUser->password = bcrypt(str_random(15));
            $newUser->google_id       = $user->id;
            $newUser->save();
            auth()->login($newUser, true);
        }
        return redirect()->to('/home');
    }


```

# Facebook Comment API
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

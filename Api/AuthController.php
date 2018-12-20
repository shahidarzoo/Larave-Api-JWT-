<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;
use App\UserInformation;
use Response;
use Auth;
use Socialite;

class AuthController extends Controller
{
     /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    /*public $user;
    public function __construct()
    {
        $this->user = \JWTAuth::parseToken()->toUser();
    }*/

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255',
        'password'=> 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors(), 'status_code' => 500], 500);
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
        return response()->json(['status_code'=>200, 'token'=>$token],200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors(), 'status_code' => 500], 500);
        }

        $user = User::create([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);

        $user_info = new UserInformation();
        $user_info->user_id = $user->id;
        $user_info->country_id = $request->country;
        $user_info->city = $request->city;
        $user_info->address = $request->address;
        $user_info->phone_number = $request->country_code.$request->mobile;
        $user_info->save();

        if($token = JWTAuth::fromUser($user))
        {
            return Response::json(['status_code'=> 200, 'data'=> 'Account created successfully'],200);
        }
        return Response::json(['status_code'=> 203, 'data'=> 'unbale to create account.'],203);
    }

    public function getAuthenticatedUser()
    {
        try 
        {

            if (! $user = JWTAuth::parseToken()->authenticate()) 
            {
                    return response()->json(['user_not_found'], 404);
            }

        } 
        catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) 
        {

            return response()->json(['token_expired'], $e->getStatusCode());

        } 
        catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) 
        {

                return response()->json(['token_invalid'], $e->getStatusCode());

        } 
        catch (Tymon\JWTAuth\Exceptions\JWTException $e) 
        {

            return response()->json(['token_absent'], $e->getStatusCode());
        }

        return response()->json(compact('user'));
    }

        /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request) 
    {
        try 
        {
            /*$current_token  = $request->bearerToken();
            $current_token1  = JWTAuth::getToken();
            return response()->json(['token' => $current_token1]);*/
            config([ 
                'jwt.blacklist_enabled' => true 
            ]); 
            auth()->logout(); 
            JWTAuth::invalidate(JWTAuth::parseToken());
            \Cookie::forget(JWTAuth::parseToken());
            return response()->json(['message' => 'Successfully logged out']);
            
        } 
        catch (Exception $e) 
        {
            return response()->json(['message' => 'There is something wrong try again later']);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {

        $current_token  = JWTAuth::getToken();
        $token          = JWTAuth::refresh($current_token);
        return response()->json([
            "status" => "success",
            "code" => 200,
            'data' => compact('token'),
            'messages' => ['Token refreshed!'],
        ]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function invalid()
    {
        $current_token  = JWTAuth::getToken();
        $token_invalid = JWTAuth::invalidate($current_token);
        if($token_invalid)
        {
            return response()->json([
                'message' => 'Current token invalid and can not use again',
            ], 200);
        } 
    }
    public function loginWithGoogleAndFacebook(Request $request)
    {
        try 
        {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
                'name' => 'required|string|max:255',
            ]);
            if ($validator->fails()) 
            {
                return response()->json(['error' => $validator->errors(), 'status_code' => 500], 500);
            }
            $login_google = User::where('email' ,$request->email)->first();
            if(count($login_google) == 0)
            {
                try 
                {
                    $name = explode(" ", trim($request->name));
                    $user = User::create([
                        'first_name' => $name[0],
                        'last_name' => $name[1] ? $name[1] : '',
                        'email' => $request->email,
                        'password' => Hash::make($request->password),
                    ]);
                    $user_info = new UserInformation();
                    $user_info->user_id = $user->id;
                    $user_info->country_id = $request->country;
                    $user_info->city = $request->city;
                    $user_info->address = $request->address;
                    $user_info->phone_number = $request->country_code.$request->mobile;
                    $user_info->save();
                    if($token = JWTAuth::fromUser($user))
                    {
                        try 
                        {
                            $user = User::where('email', $request->email)->first();
                            if (!$userToken=JWTAuth::fromUser($user)) 
                            {
                                return response()->json(['status_code' => 401,'error' => 'invalid_credentials'], 401);
                            }

                            return response()->json(['status_code'=>200, 'token'=>$userToken], 200);
                        } 
                        catch (JWTException $e) 
                        {
                            return response()->json(['status_code'=>500, 'error' => 'could_not_create_token'], 500);
                        }
                    }
                    return Response::json(['status_code'=> 203, 'data'=> 'unbale to create account.'],203);
               } 
               catch (Exception $e) 
               {
                   return Response::json(['status_code'=> 500, 'error'=> 'There is something wrong'],500);
               }
            }
            else
            {
                
                try 
                {
                    $user = User::where('email', $request->email)->first();
                    if (!$userToken=JWTAuth::fromUser($user)) 
                    {
                        return response()->json(['status_code' => 401,'error' => 'invalid_credentials'], 401);
                    }

                    return response()->json(['status_code'=>200, 'token'=>$userToken], 200);
                } 
                catch (JWTException $e) 
                {
                    return response()->json(['status_code'=>500, 'error' => 'could_not_create_token'], 500);
                }
                
            }
        }
        catch (Exception $e) 
        {
            return Response::json(['status_code' => 500 , 'error' => 'There is something wrong'], 500);    
        }
            
    }
    
    
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
   
public function login(Request $request){

        $login = Validator::make($request->all(), [ 
            'email' => 'required|email:rfc,dns,email',
            'password' => 'required|string|min:8',
        ]);

        if ($login->fails()) {
            return response()->json(['message'=> $validator->errors(), 'status' => "error"], 422);
        } 
        

        if (!Auth::attempt($request->only('email', 'password'))){ 
            return response()->json(['message'=> 'user not found', 'status' => "error"],401);
        }


        $userInfo = collect([
            'userName' => Auth::user()->name,
            'email' => Auth::user()->email,
            'token'=> Auth::user()->createToken('authToken')->accessToken
        ]);

        return response()->json(['userInfo'=>$userInfo],200);
    }

     public function register(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message'=> $validator->errors(), 'status' => "error"], 422);
        } 

        $user = User::create(['name' => $request->name, 'email' => $request->email, 'password' => bcrypt($request->password)]); 
        $userInfo = collect(['userName' => $user->name, 'email' => $user->email, 'token'=> $user->createToken('auth_token')->accessToken]);
        
        return response()->json(['userInfo'=>$userInfo],201); 
    }


    public function logout(){   

        if(Auth::check()){
            Auth::user()->token()->revoke();
            return response()->json(['message'=> 'Success logout', 'status' => "Success"],200);
         }

         return response()->json(['message'=> 'found error in logout', 'status' => "error"],401);
        
    }

}

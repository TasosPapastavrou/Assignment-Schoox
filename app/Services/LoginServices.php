<?php

namespace App\Services;

use App\Repositories\LoginRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginServices implements LoginRepositoryInterface
{
    
    private $statuses = [
            0 => ['status' => "error", "message"=> "", "data" => []],
            1 => ['status' => "success", "message"=> "", "data" => []],
    ];


    public function login(Request $request){

        $response = [];
        $httpReqStatus = '';

        $login = Validator::make($request->all(), [ 
            'email' => 'required|email:rfc,dns,email',
            'password' => 'required|string|min:8',
        ]);

        if ($login->fails()) {
            $httpReqStatus = 422; 
            $response = $this->statuses[0];
            $response["message"] = $validator->errors();
            return ['response' => $response, "http_status" => $httpReqStatus]; 
        } 
        

        if (!Auth::attempt($request->only('email', 'password'))){ 
            $httpReqStatus = 401; 
            $response = $this->statuses[0];
            $response["message"] = 'The provided credentials are incorrect.';
            return ['response' => $response, "http_status" => $httpReqStatus];  
        }


        $userInfo = collect([
            'userName' => Auth::user()->name,
            'email' => Auth::user()->email,
            'token'=> Auth::user()->createToken('authToken')->accessToken
        ]);

        $httpReqStatus = 200;
        $response = $this->statuses[1];
        $response["data"] = ['userInfo'=>$userInfo];

        return ['response' => $response, "http_status" => $httpReqStatus];
    }

     public function register(Request $request){

        $response = [];
        $httpReqStatus = '';

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            $httpReqStatus = 422; 
            $response = $this->statuses[0];
            $response["message"] = $validator->errors();
            return ['response' => $response, "http_status" => $httpReqStatus];
        } 

        $user = User::create(['name' => $request->name, 'email' => $request->email, 'password' => bcrypt($request->password)]); 
        $userInfo = collect(['userName' => $user->name, 'email' => $user->email, 'token'=> $user->createToken('auth_token')->accessToken]);

        $httpReqStatus = 201;
        $response = $this->statuses[1];
        $response["data"] = ['userInfo'=>$userInfo];
        
        return ['response' => $response, "http_status" => $httpReqStatus];
    }


    public function logout(){ 
        
        $response = [];
        $httpReqStatus = '';

        if(Auth::check()){
            Auth::user()->token()->revoke(); 
            $httpReqStatus = 200;
            $response = $this->statuses[1];
            $response["message"] = "Logged out successfully.";
        }else{
            $httpReqStatus = 401; 
            $response = $this->statuses[0];
            $response["message"] = "Invalid token or user is not authenticated.";
        }

        return ['response' => $response, "http_status" => $httpReqStatus];  
    }







}
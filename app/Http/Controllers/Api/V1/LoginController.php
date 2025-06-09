<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\LoginRepositoryInterface;

class LoginController extends Controller
{

   protected LoginRepositoryInterface $loginRepository;

    public function __construct(LoginRepositoryInterface $loginRepository)
    {
        $this->loginRepository = $loginRepository;
    }

    public function login(Request $request){
        $result = $this->loginRepository->login($request);
        return response()->json($result['response'], $result['http_status']);
    }

     public function register(Request $request){
        $result = $this->loginRepository->register($request);
        return response()->json($result['response'], $result['http_status']);
    }


    public function logout(){   
        $result = $this->loginRepository->logout();
        return response()->json($result['response'], $result['http_status']);        
    }

}

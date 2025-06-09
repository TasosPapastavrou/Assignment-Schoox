<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;

interface LoginRepositoryInterface
{
    public function logout(); 
    public function login(Request $request); 
    public function register(Request $request); 
}
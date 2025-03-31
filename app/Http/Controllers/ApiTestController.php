<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\JsonResponse;

class ApiTestController extends Controller
{
    private ApiService $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index(): JsonResponse
    {
        throw new \Exception("BOOM");
        return response()->json([
            'message' => $this->apiService->getMessage(),
            /*'message' => 'Bienvenido a la API de prueba',*/
            'status' => 'success'
        ]);
    }
}

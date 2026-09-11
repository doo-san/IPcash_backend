<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Beneficiary\AddBeneficiaryRequest;
use App\Http\Resources\BeneficiaryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Implémente le tag `beneficiaries` de openapi.yaml.
class BeneficiaryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return BeneficiaryResource::collection($request->user()->beneficiaries()->latest()->get());
    }

    public function store(AddBeneficiaryRequest $request): JsonResponse
    {
        $beneficiary = $request->user()->beneficiaries()->create([
            'name' => $request->validated('name'),
            'phone_number' => $request->validated('phoneNumber'),
        ]);

        return response()->json((new BeneficiaryResource($beneficiary))->resolve(), 201);
    }
}

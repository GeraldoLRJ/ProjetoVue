<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyStoreRequest;
use App\Http\Requests\CompanyUpdateRequest;
use App\Models\Company;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'role:master']);
    }

    public function index()
    {
        return Company::orderBy('name')->paginate(20);
    }

    public function store(CompanyStoreRequest $request)
    {
        $company = Company::create($request->validated());
        return response()->json($company, 201);
    }

    public function show(Company $company)
    {
        return $company;
    }

    public function update(CompanyUpdateRequest $request, Company $company)
    {
        $company->update($request->validated());
        return $company;
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

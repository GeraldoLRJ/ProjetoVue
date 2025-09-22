<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['slug' => 'master'],
            ['name' => 'Master Company']
        );

        User::firstOrCreate(
            ['email' => 'master@local.test'],
            [
                'name' => 'Master User',
                'password' => Hash::make('master123'),
                'tenant_id' => $company->id,
                'role' => User::ROLE_MASTER,
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $mainAccounts = [
            [
                'account_code'   => '1',
                'account_name'   => 'الأصول',
                'account_type'   => 'asset',
                'normal_account' => 'main',
                'account_nature' => 'debit',
            ],
            [
                'account_code'   => '2',
                'account_name'   => 'الالتزامات',
                'account_type'   => 'liability',
                'normal_account' => 'main',
                'account_nature' => 'credit',
            ],
            [
                'account_code'   => '3',
                'account_name'   => 'حقوق الملكية',
                'account_type'   => 'equity',
                'normal_account' => 'main',
                'account_nature' => 'credit',
            ],
            [
                'account_code'   => '4',
                'account_name'   => 'الإيرادات',
                'account_type'   => 'revenue',
                'normal_account' => 'main',
                'account_nature' => 'credit',
            ],
            [
                'account_code'   => '5',
                'account_name'   => 'المصروفات',
                'account_type'   => 'expense',
                'normal_account' => 'main',
                'account_nature' => 'debit',
            ],
        ];

        foreach ($mainAccounts as $data) {
            Account::firstOrCreate(
                ['account_code' => $data['account_code']],
                array_merge($data, ['uuid' => (string) Str::uuid()])
            );
        }
    }
}
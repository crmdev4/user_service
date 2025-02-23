<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'account' => 'fms_driver',
                'is_single_device' => 1,
                'is_banned' => 0,
            ],
            [
                'account' => 'fms_company',
                'is_single_device' => 1,
                'is_banned' => 0,
            ],
           
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(['account' => $account['account']], $account);
        }
    }
}

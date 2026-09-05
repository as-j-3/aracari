<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            /*
            |--------------------------------------------------------------------------
            | 1000 - الأصول
            |--------------------------------------------------------------------------
            */
            [
                'code' => '1000',
                'name' => 'الأصول',
                'type' => 'asset',
                'level' => 1,
                'parent' => null,
            ],

            [
                'code' => '1100',
                'name' => 'الأصول المتداولة',
                'type' => 'asset',
                'level' => 2,
                'parent' => '1000',
            ],

            [
                'code' => '1110',
                'name' => 'الصندوق',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1100',
            ],

            [
                'code' => '1120',
                'name' => 'البنك',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1100',
            ],

            [
                'code' => '1130',
                'name' => 'محافظ التحصيل الإلكترونية',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1100',
            ],

            [
                'code' => '1140',
                'name' => 'حسابات تسوية بوابات الدفع',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1100',
            ],

            [
                'code' => '1150',
                'name' => 'حوالات بنكية معلقة',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1100',
            ],

            [
                'code' => '1160',
                'name' => 'المدينون',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1100',
            ],

            /*
            |--------------------------------------------------------------------------
            | 1200 - الأصول الثابتة
            |--------------------------------------------------------------------------
            */
            [
                'code' => '1200',
                'name' => 'الأصول الثابتة',
                'type' => 'asset',
                'level' => 2,
                'parent' => '1000',
            ],

            [
                'code' => '1210',
                'name' => 'أجهزة ومعدات',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1200',
            ],

            [
                'code' => '1220',
                'name' => 'أثاث',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1200',
            ],

            [
                'code' => '1290',
                'name' => 'مجمع إهلاك الأصول الثابتة',
                'type' => 'asset',
                'level' => 3,
                'parent' => '1200',
            ],

            /*
            |--------------------------------------------------------------------------
            | 2000 - الالتزامات
            |--------------------------------------------------------------------------
            */
            [
                'code' => '2000',
                'name' => 'الالتزامات',
                'type' => 'liability',
                'level' => 1,
                'parent' => null,
            ],

            [
                'code' => '2100',
                'name' => 'الدائنون',
                'type' => 'liability',
                'level' => 2,
                'parent' => '2000',
            ],

            [
                'code' => '2200',
                'name' => 'مستحقات شركات النقل',
                'type' => 'liability',
                'level' => 2,
                'parent' => '2000',
            ],

            [
                'code' => '2300',
                'name' => 'الضريبة المستحقة على عمولة أراكاري',
                'type' => 'liability',
                'level' => 2,
                'parent' => '2000',
            ],

            [
                'code' => '2310',
                'name' => 'ض.ق.م على عمولة أراكاري - مصر (14%)',
                'type' => 'liability',
                'level' => 3,
                'parent' => '2300',
            ],

            [
                'code' => '2320',
                'name' => 'ض.ق.م على عمولة أراكاري - السعودية (15%)',
                'type' => 'liability',
                'level' => 3,
                'parent' => '2300',
            ],

            [
                'code' => '2330',
                'name' => 'ض.ق.م على عمولة أراكاري - الإمارات (5%)',
                'type' => 'liability',
                'level' => 3,
                'parent' => '2300',
            ],

            [
                'code' => '2340',
                'name' => 'الضريبة السياحية على عمولة أراكاري - اليمن (3%)',
                'type' => 'liability',
                'level' => 3,
                'parent' => '2300',
            ],

            [
                'code' => '2500',
                'name' => 'أرصدة محافظ العملاء',
                'type' => 'liability',
                'level' => 2,
                'parent' => '2000',
            ],

            [
                'code' => '2600',
                'name' => 'مبالغ استرداد مستحقة',
                'type' => 'liability',
                'level' => 2,
                'parent' => '2000',
            ],

            [
                'code' => '2700',
                'name' => 'إيرادات مقدمة',
                'type' => 'liability',
                'level' => 2,
                'parent' => '2000',
            ],

            [
                'code' => '2800',
                'name' => 'رواتب ومزايا مستحقة',
                'type' => 'liability',
                'level' => 2,
                'parent' => '2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | 4000 - الإيرادات
            |--------------------------------------------------------------------------
            */
            [
                'code' => '4000',
                'name' => 'الإيرادات',
                'type' => 'revenue',
                'level' => 1,
                'parent' => null,
            ],

            [
                'code' => '4100',
                'name' => 'إيراد إحصائي: إجمالي قيمة الحجوزات',
                'type' => 'revenue',
                'level' => 2,
                'parent' => '4000',
            ],

            [
                'code' => '4200',
                'name' => 'إيراد العمولات',
                'type' => 'revenue',
                'level' => 2,
                'parent' => '4000',
            ],

            [
                'code' => '4250',
                'name' => 'خصومات كوبونات أراكاري',
                'type' => 'revenue',
                'level' => 2,
                'parent' => '4000',
            ],

            [
                'code' => '4300',
                'name' => 'إيراد الإعلانات',
                'type' => 'revenue',
                'level' => 2,
                'parent' => '4000',
            ],

            [
                'code' => '4400',
                'name' => 'إيراد غرامات الإلغاء',
                'type' => 'revenue',
                'level' => 2,
                'parent' => '4000',
            ],

            /*
            |--------------------------------------------------------------------------
            | 5000 - المصروفات
            |--------------------------------------------------------------------------
            */
            [
                'code' => '5000',
                'name' => 'المصروفات',
                'type' => 'expense',
                'level' => 1,
                'parent' => null,
            ],

            [
                'code' => '5100',
                'name' => 'رسوم بوابات الدفع',
                'type' => 'expense',
                'level' => 2,
                'parent' => '5000',
            ],

            [
                'code' => '5200',
                'name' => 'مصروف الضريبة المستحقة على عمولة أراكاري',
                'type' => 'expense',
                'level' => 2,
                'parent' => '5000',
            ],

            [
                'code' => '5300',
                'name' => 'تكلفة الاستضافة والخدمات التقنية',
                'type' => 'expense',
                'level' => 2,
                'parent' => '5000',
            ],

            [
                'code' => '5400',
                'name' => 'رواتب وأجور',
                'type' => 'expense',
                'level' => 2,
                'parent' => '5000',
            ],

            [
                'code' => '5500',
                'name' => 'مصروفات تسويق وإعلان',
                'type' => 'expense',
                'level' => 2,
                'parent' => '5000',
            ],

            [
                'code' => '5600',
                'name' => 'مصروفات إدارية عامة',
                'type' => 'expense',
                'level' => 2,
                'parent' => '5000',
            ],

            [
                'code' => '5700',
                'name' => 'مصروف إهلاك الأصول الثابتة',
                'type' => 'expense',
                'level' => 2,
                'parent' => '5000',
            ],
        ];

        $createdAccounts = [];

        foreach ($accounts as $account) {
            $existingAccount = Account::where(
                'account_code',
                $account['code']
            )->first();

            $createdAccounts[$account['code']] = Account::updateOrCreate(
                [
                    'account_code' => $account['code'],
                ],
                [
                    'uuid' => $existingAccount?->uuid ?? (string) Str::uuid(),
                    'account_name' => $account['name'],
                    'account_type' => $account['type'],
                    'level' => $account['level'],
                    'parent_id' => null,
                    'created_by_user' => null,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ربط الحسابات بالحسابات الأب
        |--------------------------------------------------------------------------
        */
        foreach ($accounts as $account) {
            if ($account['parent']) {
                $createdAccounts[$account['code']]->update([
                    'parent_id' => $createdAccounts[$account['parent']]->id,
                ]);
            }
        }
    }
}
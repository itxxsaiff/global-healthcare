<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // Pakistan (PKR) — client document
            ['pk_free', 'Starter', 'Free listing. We only earn when you do.', 'pk', 'PKR', 0, 'none', 2000, false, false],
            ['pk_pro_1m', 'Pro Monthly', 'Zero commission, direct patient dealing.', 'pk', 'PKR', 500000, 'month', 0, true, true],
            // 6-month and yearly prices are not fixed in the client document; editable by admin.
            ['pk_pro_6m', 'Pro 6 Months', 'Save 10% on six months.', 'pk', 'PKR', 2700000, 'six_months', 0, true, true],
            ['pk_pro_12m', 'Pro Yearly', 'Best value. Save over 15%.', 'pk', 'PKR', 5000000, 'year', 0, true, true],

            // International (USD) — client document
            ['intl_1m', 'Directory Monthly', 'Zero commission, direct client dealing.', 'intl', 'USD', 3000, 'month', 0, true, true],
            ['intl_6m', 'Directory 6 Months', '$25/month, billed every six months.', 'intl', 'USD', 15000, 'six_months', 0, true, true],
            ['intl_12m', 'Directory Yearly', 'Best value. Two months free.', 'intl', 'USD', 28000, 'year', 0, true, true],
        ];

        foreach ($plans as $i => [$code, $name, $tagline, $market, $currency, $price, $interval, $bps, $linkedin, $direct]) {
            Plan::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'tagline' => $tagline,
                'market' => $market,
                'currency' => $currency,
                'price_minor' => $price,
                'interval' => $interval,
                'commission_bps' => $bps,
                'shows_linkedin' => $linkedin,
                'direct_dealing' => $direct,
                'is_active' => true,
                'sort' => $i,
            ]);
        }
    }
}

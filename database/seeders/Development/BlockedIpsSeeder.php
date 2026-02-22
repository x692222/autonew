<?php

namespace Database\Seeders\Development;

use App\Models\Security\BlockedIp;
use Illuminate\Database\Seeder;

class BlockedIpsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();

        for ($i = 0; $i < 100; $i++) {
            $guard = $faker->randomElement(['backoffice', 'dealer']);
            $ip = $faker->unique()->ipv4();

            BlockedIp::query()->create([
                'ip_address' => $ip,
                'guard_name' => $guard,
                'failed_attempts' => $faker->numberBetween(20, 200),
                'blocked_at' => $faker->dateTimeBetween('-90 days', 'now'),
                'country' => null,
            ]);
        }
    }
}

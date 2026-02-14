<?php

namespace Database\Seeders;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Dealer\DealerUser;
use App\Models\Location\LocationSuburb; // <-- adjust namespace if yours differs
use App\Support\Services\DealerLeadDefaultsProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DealerDatasetSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have suburbs for branch suburb_id FK
        $suburbIds = LocationSuburb::query()->pluck('id')->all();

        if (empty($suburbIds)) {
            throw new \RuntimeException('No location_suburbs found. Seed suburbs first before DealerDatasetSeeder.');
        }

        $faker = fake();

        // Use one hashed password for performance
        $defaultPassword = Hash::make('Password@123');

        $dealerLeadDefaultsProvisioner = new DealerLeadDefaultsProvisioner();

        DB::transaction(function () use ($faker, $suburbIds, $defaultPassword, $dealerLeadDefaultsProvisioner) {

            for ($i = 0; $i < mt_rand(10,30); $i++) {

                // ----------------------------
                // DEALER
                // ----------------------------
                $dealerName = $faker->company();
                $dealerSlug = Str::slug($dealerName) . '-' . Str::lower(Str::random(6));

                /** @var Dealer $dealer */
                $dealer = Dealer::query()->create([
                    // adjust these fields if your Dealer table differs
                    'is_active'   => $faker->boolean(90), // 90% active
                    'name'        => $dealerName,
                    'slug'        => $dealerSlug,
                ]);

                $dealerLeadDefaultsProvisioner->provision($dealer);

                // ----------------------------
                // DEALER USERS (1–5)
                // ----------------------------
                $userCount = $faker->numberBetween(1, 5);

                $users = [];
                for ($u = 0; $u < $userCount; $u++) {
                    $first = $faker->firstName();
                    $last  = $faker->lastName();
                    $email = $faker->unique()->safeEmail();

                    $users[] = [
                        'id'                 => (string) Str::uuid(),
                        'dealer_id'          => (string) $dealer->id,
                        'is_active'          => $faker->boolean(90),
                        'firstname'          => $first,
                        'lastname'           => $last,
                        'email'              => $email,
                        'email_verified_at'  => now(),
                        'password'           => $defaultPassword,
                        'remember_token'     => Str::random(10),
                        'created_at'         => now(),
                        'updated_at'         => now(),
                        'deleted_at'         => null,
                    ];
                }

                // Bulk insert for speed
                DealerUser::query()->insert($users);

                // ----------------------------
                // BRANCHES (1–3)
                // ----------------------------
                $branchCount = $faker->numberBetween(1, 4);

                $branchIds = [];

                for ($b = 0; $b < $branchCount; $b++) {
                    $branchName = $dealerName . ' - ' . $faker->city();
                    $branchSlug = Str::slug($branchName) . '-' . Str::lower(Str::random(6));

                    /** @var DealerBranch $branch */
                    $branch = DealerBranch::query()->create([
                        'dealer_id'        => (string) $dealer->id,
                        'suburb_id'        => (string) $faker->randomElement($suburbIds),

                        // adjust these fields if your Branch table differs
                        'name'             => $branchName,
                        'slug'             => $branchSlug,
                        'contact_numbers' => collect(
                            range(1, $faker->numberBetween(1, 4))
                        )->map(fn () => $faker->phoneNumber())->implode(', '),
                        'display_address'  => $faker->address(),
                        'latitude'         => $faker->optional(0.7)->latitude(-35, -22),
                        'longitude'        => $faker->optional(0.7)->longitude(16, 33),
                    ]);

                    $branchIds[] = $branch->id;
                }

                // ----------------------------
                // SALE PEOPLE: about half of branches
                // each selected branch gets 1–5 sale people
                // ----------------------------
                foreach ($branchIds as $branchId) {
                    if (!$faker->boolean(50)) {
                        continue; // skip ~50%
                    }

                    $saleCount = $faker->numberBetween(1, 5);

                    $salePeople = [];
                    for ($s = 0; $s < $saleCount; $s++) {
                        $salePeople[] = [
                            'id'           => (string) Str::uuid(),
                            'branch_id'    => (string) $branchId,
                            'firstname'    => $faker->firstName(),
                            'lastname'     => $faker->lastName(),
                            'contact_no'   => $faker->phoneNumber(),
                            'email'        => $faker->optional()->safeEmail(),
                            'created_at'   => now(),
                            'updated_at'   => now(),
                            'deleted_at'   => null,
                        ];
                    }

                    DealerSalePerson::query()->insert($salePeople);
                }
            }
        });
    }
}

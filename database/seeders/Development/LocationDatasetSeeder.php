<?php

namespace Database\Seeders\Development;

use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();

        DB::transaction(function () use ($faker) {
            $countryNames = ['Namibia', 'South Africa'];

            foreach ($countryNames as $countryName) {
                $country = LocationCountry::query()->create([
                    'name' => $countryName,
                ]);

                $stateCount = $faker->numberBetween(1, 7);

                for ($s = 1; $s <= $stateCount; $s++) {
                    $state = LocationState::query()->create([
                        'country_id' => (string) $country->id,
                        'name' => $faker->unique()->state() . " {$s}",
                    ]);

                    $faker->unique(true);

                    $cityCount = $faker->numberBetween(1, 10);

                    for ($c = 1; $c <= $cityCount; $c++) {

                        $city = LocationCity::query()->create([
                            'state_id' => (string) $state->id,
                            'name' => $faker->unique()->city() . " {$c}",
                        ]);

                        $faker->unique(true);

                        $suburbCount = $faker->numberBetween(1, 15);

                        for ($u = 1; $u <= $suburbCount; $u++) {
                            LocationSuburb::query()->create([
                                'city_id' => (string) $city->id,
                                'name' => $faker->streetName() . " {$u}",
                            ]);
                        }
                    }
                }
            }
        });
    }
}

<?php

namespace App\Actions\Backoffice\GuardBackoffice\System\Locations;
use App\Support\Locations\LocationTypeResolver;
use Illuminate\Database\Eloquent\Model;

class UpdateLocationAction
{
    public function execute(string $type, Model $location, array $data): bool
    {
        return $location->update($this->payload($type, $data));
    }

    private function payload(string $type, array $data): array
    {
        return match ($type) {
            LocationTypeResolver::COUNTRY => [
                'name' => $data['name'],
            ],
            LocationTypeResolver::STATE => [
                'name' => $data['name'],
                'country_id' => $data['country_id'],
            ],
            LocationTypeResolver::CITY => [
                'name' => $data['name'],
                'state_id' => $data['state_id'],
            ],
            LocationTypeResolver::SUBURB => [
                'name' => $data['name'],
                'city_id' => $data['city_id'],
            ],
            default => [],
        };
    }
}

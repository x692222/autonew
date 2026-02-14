<?php

use App\Models\Stock\Stock;
use App\Models\Stock\StockMake;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // vehicle types

        $names = [
            'Abarth',
            'Alfa Romeo',
            'Aston Martin',
            'Audi',
            'BMW',
            'Baic',
            'Cadillac',
            'Chery',
            'Chevrolet',
            'Chrysler',
            'Citroen',
            'Daihatsu',
            'Datsun',
            'Dodge',
            'FAW',
            'Ferrari',
            'Fiat',
            'Ford',
            'Foton',
            'GWM',
            'Geely',
            'Haval',
            'Honda',
            'Hummer',
            'Hyundai',
            'Ineos',
            'Infiniti',
            'Isuzu',
            'Jaecoo',
            'Jaguar',
            'Jeep',
            'Jetour',
            'Jiayuan',
            'KIA',
            'Land Rover',
            'Lexus',
            'Leyland',
            'Lotus',
            'MG',
            'Mahindra',
            'Maserati',
            'Mazda',
            'Mercedes-Benz',
            'Mini',
            'Mitsubishi',
            'Nissan',
            'Omoda',
            'Opel',
            'Peugeot',
            'Porsche',
            'Proton',
            'Renault',
            'Seat',
            'Secma',
            'Smart',
            'Ssangyong',
            'Subaru',
            'Suzuki',
            'Tata',
            'Toyota',
            'Volkswagen',
            'Volvo',

            'Ashok Leyland',
            'Chevrolet',
            'Colt',
            'Daihatsu',
            'Fiat',
            'Ford',
            'Foton',
            'GWM',
            'Hyundai',
            'Isuzu',
            'JAC',
            'JMC',
            'Jeep',
            'Jiayuan',
            'KIA',
            'LDV',
            'Land Rover',
            'Mahindra',
            'Mazda',
            'Mercedes-Benz',
            'Mitsubishi',
            'Nissan',
            'Opel',
            'Peugeot',
            'Ssangyong',
            'Suzuki',
            'Tata',
            'Toyota',
            'Volkswagen',
        ];

        $names = array_unique($names);
        sort($names);

        $rows = array_map(fn($name) => [
            'name'       => $name,
            'stock_type' => Stock::STOCK_TYPE_VEHICLE,
        ], $names);

        foreach ($rows as $row) {
            StockMake::create($row);
        }

        // motorbike types

        $names = [
            'Aprilia',
            'BMW',
            'Bennelli',
            'Big Boy',
            'Buell',
            'ClubCar',
            'Conti',
            'Dinli',
            'Drake',
            'Ducati',
            'GoGo',
            'Gomoto',
            'Harley Davidson',
            'Hawk',
            'Hero',
            'Honda',
            'Husqvarna',
            'Hyosung',
            'Indian',
            'Invacare',
            'KTM',
            'Kawasaki',
            'Kayo',
            'Kymco',
            'Lifan',
            'Linhai',
            'Moto Pro',
            'Mv Agusta',
            'Polaris',
            'Puzey',
            'SWM',
            'SYM',
            'Suzuki',
            'TVS',
            'Triumph',
            'Vespa',
            'Victory',
            'X-Moto',
            'Yamaha',
            'Zhejiang Cf Moto',
            'Zontes',
        ];

        $names = array_unique($names);
        sort($names);

        $rows = array_map(fn($name) => [
            'name'       => $name,
            'stock_type' => Stock::STOCK_TYPE_MOTORBIKE,
        ], $names);

        foreach ($rows as $row) {
            StockMake::create($row);
        }

        // leisure types

        $names = [
            'Ace',
            'Ace Cat 500',
            'Ace Craft',
            'Adventure',
            'Afrispoor',
            'Angler',
            'Armadillo',
            'Austral Marine',
            'Bayliner',
            'Bermac',
            'Bright Idea',
            'Bush Lapa',
            'BushLapa',
            'Buzzard Industries',
            'Campmaster',
            'Cartel Projects',
            'Challenger',
            'Clifton',
            'Club Car',
            'ClubCar',
            'Conqueror',
            'Countess',
            'Crownline',
            'Custom',
            'Dethleffs',
            'Diamond',
            'Echo',
            'El Shaddai',
            'Escape',
            'Expression',
            'Falcon',
            'GT Camper',
            'Gypsey',
            'Home-Build',
            'Hysucat',
            'Infanta',
            'Invader',
            'Jaguar',
            'Jetstream',
            'Jimny',
            'Jurgens',
            'Karet',
            'Kawasaki',
            'Lebusha',
            'Mafuta',
            'Mallards',
            'Mecca',
            'Mobi Lodge',
            'Multiloader',
            'Quantum',
            'Raven',
            'Regal',
            'Rubberduck',
            'Scarab',
            'Scorpion',
            'Sensation',
            'Sherpa',
            'Skipper',
            'Sprite',
            'Sugar Sands',
            'Sun Seeker',
            'Torsion',
            'Trailored',
            'Trim Craft',
            'Tuff Cat',
            'Tuff Cats',
            'Unipod',
            'Vagabond',
            'Venter',
            'Wildebeest',
            'X Factor',
            'Yamaha',
            'Z Craft',

            'Sea-Doo',
            'Yamaha',
            'Kawasaki',
            'Taiga',
            'Narke',
            'Krash Industries',
            'Belassi',
            'Honda',
            'Polaris',
            'Arctic Cat',
            'Wet Jet',
            'Hydrospace',
            'HSR-Benelli',
        ];

        $names = array_unique($names);
        sort($names);

        $rows = array_map(fn($name) => [
            'name'       => $name,
            'stock_type' => Stock::STOCK_TYPE_LEISURE,
        ], $names);

        foreach ($rows as $row) {
            StockMake::create($row);
        }

        // commercial types

        $names = [
            'Afrit',
            'FAW',
            'Ford',
            'Foton',
            'Fuso',
            'Hino',
            'Hyundai',
            'Isuzu',
            'Iveco',
            'JCB',
            'M a n',
            'Manitou',
            'Massey Ferguson',
            'Mercedes-Benz',
            'Mitsubishi',
            'Peugeot',
            'Tata',
            'Scania',
            'Sinotruck',
            'Suzuki',
            'Toyota',
            'Ud Trucks',
            'Volkswagen',
            'Volvo',
        ];

        $names = array_unique($names);
        sort($names);

        $rows = array_map(fn($name) => [
            'name'       => $name,
            'stock_type' => Stock::STOCK_TYPE_COMMERCIAL,
        ], $names);

        foreach ($rows as $row) {
            StockMake::create($row);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

<?php

use App\Models\Stock\Stock;
use App\Models\Stock\StockFeatureTag;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // vehicle

        $features = [
            'Air Conditioning',
            'Climate Control',
            'Central Locking',
            'Cruise Control',
            'Electric Mirrors',
            'Electric Windows',
            'Immobilizer',
            'Leather Interior',
            'Cloth Seats',
            'Power Steering',
            'Tilt / Telescopic Steering Wheel',
            'Airbags',
            'Alarm',
            'Anti-Lock Braking System (ABS)',
            'Fog Lights',
            'Front-view Camera',
            'Rear-view Camera',
            '360° Camera System',
            'Navigation System (GPS)',
            'AM/FM Radio',
            'Aux Input',
            'Bluetooth Connectivity',
            'CD Player',
            'USB Port',
            'Alloy Wheels',
            'TV Entertainment',
            'Key Start',
            'Push Start',
            'Spare Wheel',
            'Sunroof',
            'Heated Seats',
            'Hill Descent Control',
            'Lane Assist',
            'Winker / Indicator Mirrors',
            'Adaptive Cruise Control',
            'Automatic Headlights',
            'Automatic Wipers',
            'Blind Spot Monitoring',
            'Brake Assist',
            'Child Safety Locks',
            'Daytime Running Lights (DRL)',
            'Digital Instrument Cluster',
            'Driver Fatigue Warning',
            'Electronic Stability Control (ESC)',
            'Emergency Brake Assist',
            'Hands-Free Calling',
            'Heads-Up Display (HUD)',
            'Heated Steering Wheel',
            'Hill Start Assist',
            'Keyless Entry',
            'LED Headlights',
            'Memory Seats',
            'Multi-Function Steering Wheel',
            'Parking Sensors',
            'Rear Cross-Traffic Alert',
            'Remote Central Locking',
            'Roof Rails',
            'Speed Limiter',
            'Start-Stop System',
            'Touchscreen Infotainment System',
            'Traction Control',
            'Tyre Pressure Monitoring System (TPMS)',
            'Voice Control',
            'Wireless Charging',
            'Apple CarPlay',
            'Android Auto',
        ];

        $features = array_unique($features);
        sort($features);

        $rows = array_map(fn($name) => [
            'is_approved' => true,
            'name'        => $name,
            'stock_type'  => Stock::STOCK_TYPE_VEHICLE,
        ], $features);

        foreach ($rows as $row) {
            StockFeatureTag::create($row);
        }

        // commercial

        $features = [
            'Air Conditioning',
            'Climate Control',
            'Central Locking',
            'Cruise Control',
            'Immobilizer',
            'Power Steering',
            'Airbags',
            'Alarm',
            'Anti-Lock Braking System (ABS)',
            'Fog Lights',
            'Front-view Camera',
            'Rear-view Camera',
            '360° Camera System',
            'Navigation System (GPS)',
            'AM/FM Radio',
            'Bluetooth Connectivity',
            'CD Player',
            'USB Port',
            'Key Start',
            'Push Start',
            'Spare Wheel',
            'Hill Descent Control',
            'Lane Assist',
            'Winker / Indicator Mirrors',
            'Adaptive Cruise Control',
            'Brake Assist',
            'Driver Fatigue Warning',
            'Electronic Stability Control (ESC)',
            'Emergency Brake Assist',
            'Hands-Free Calling',
            'Heads-Up Display (HUD)',
            'Hill Start Assist',
            'LED Headlights',
            'Memory Seats',
            'Parking Sensors',
            'Remote Central Locking',
            'Speed Limiter',
            'Touchscreen Infotainment System',
            'Traction Control',
            'Tyre Pressure Monitoring System (TPMS)',
            'Wireless Charging',
            'Apple CarPlay',
            'Android Auto',
        ];

        $features = array_unique($features);
        sort($features);

        $rows = array_map(fn($name) => [
            'is_approved' => true,
            'name'        => $name,
            'stock_type'  => Stock::STOCK_TYPE_COMMERCIAL,
        ], $features);

        foreach ($rows as $row) {
            StockFeatureTag::create($row);
        }

        // motorbike

        $features = [
            'Anti-Lock Braking System (ABS)',
            'Traction Control System',
            'Cruise Control',
            'Electric Start',
            'Kick Start',
            'Fuel Gauge',
            'Tachometer',
            'Digital Instrument Cluster',
            'Analog + Digital Dash',
            'Bluetooth Connectivity',
            'USB Charging Port',
            'Phone Connectivity',
            'LED Headlights',
            'Heated Grips',
            'Adjustable Windscreen',
            'Hand Guards',
            'Crash Bars / Engine Guards',
            'Center Stand',
            'Side Stand',
            'Adjustable Suspension',
            'Rear Mono-Shock',
            'Dual Front Disc Brakes',
            'Rear Disc Brake',
            'Tubeless Tires',
            'Alloy Wheels',
            'Slip-On Exhaust',
            'Under-Seat Storage',
            'Saddle Bags / Panniers',
            'Top Box',
            'Passenger Foot Pegs',
            'Passenger Grab Rail',
            'Fuel Injection System',
            'Automatic Engine Cut-off',
            'Immobilizer',
            'Alarm System',
            'Keyless Ignition',
            'Push Start Button',
            'Ride-By-Wire Throttle',
            'Cornering ABS',
            'Wheelie Control',
            'Selectable Ride Modes',
            'Rain Mode',
            'Sport Mode',
            'Touring Mode',
            'Digital Clock',
            'Trip Meter',
            'Odometer',
            'Range to Empty Display',
            'Service Reminder',
            'Gear Shift Indicator',
            'Side Stand Engine Cut-off',
            'Adjustable Brake & Clutch Levers',
            'Underbody Protection (Skid Plate)',
            'Tyre Pressure Monitoring System (TPMS)',
            'GPS Tracking Integration',
        ];

        $features = array_unique($features);
        sort($features);

        $rows = array_map(fn($name) => [
            'is_approved' => true,
            'name'        => $name,
            'stock_type'  => Stock::STOCK_TYPE_MOTORBIKE,
        ], $features);

        foreach ($rows as $row) {
            StockFeatureTag::create($row);
        }

        // leisure

        $features = [
            'Braked Trailer',
            'Unbraked Trailer',
            'Spare Wheel',
            'Spare Wheel Carrier',
            'Alloy Wheels',
            'Steel Wheels',
            'Heavy-Duty Chassis',
            'Galvanised Chassis',
            'Aluminium Body',
            'Fibreglass Body',
            'Off-Road Capable',
            'High Ground Clearance',
            'Stabiliser Legs',
            'Jockey Wheel',
            'Heavy-Duty Jockey Wheel',
            '12V Power System',
            '220V Power System',
            'External Power Inlet',
            'Pop-Up Roof',
            'Hard Roof',
            'Canvas Tent',
            'Fold-Out Kitchen',
            'Slide-Out Kitchen',
            'Fridge Slide',
            'Sink',
            'Prep Table',
            'Cutlery Drawer',
            'Pantry Storage',
            'Cupboards',
            'Hanging Wardrobe',
            'Fixed Bed',
            'Fold-Down Bed',
            'Dining Table',
            'Hand Basin',
            'Jerry Can Holder',
            'Gas Bottle Holder',
            'Winch Mount',
            'Underbody Protection',
            'Wheel Clamp',
            'Security Locks',
            'Alarm System',
            'First Aid Kit Holder',
            'Jetski Trailer Compatible',
            'Boat Trailer Compatible',
            'Roller System',
            'Bunks',
            'Winch',
            'Winch Post',
            'Bow Stop',
            'Saltwater Rated',
            'Submersible Bearings',
            'Flush Kit',
            'Tie-Down Straps',
        ];

        $features = array_unique($features);
        sort($features);

        $rows = array_map(fn($name) => [
            'is_approved' => true,
            'name'        => $name,
            'stock_type'  => Stock::STOCK_TYPE_LEISURE,
        ], $features);

        foreach ($rows as $row) {
            StockFeatureTag::create($row);
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

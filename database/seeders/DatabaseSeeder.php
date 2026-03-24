<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CountrySeeder::class,
            IdentificationDocumentTypeSeeder::class,
            BedTypeSeeder::class,
            FeeTypeSeeder::class,
            ChargeBasisSeeder::class,
            FeeTypeChargeBasisSeeder::class,
            BathRoomTypeSeeder::class,
            PlatformSeeder::class,
            HolidayDefinitionSeeder::class,
            SeasonBlockSeeder::class,
            PricingCategorySeeder::class,
            PricingRuleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}

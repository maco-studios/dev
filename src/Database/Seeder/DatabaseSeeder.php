<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Seeder_DatabaseSeeder extends Dev_Database_Seeder_SeederAbstract
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Add your seeders here
        $this->call('ProductSeeder');
        $this->call('CustomerSeeder');
        $this->call('CategorySeeder');

        $this->call('OrderSeeder');
    }
}

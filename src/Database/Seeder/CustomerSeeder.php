<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Seeder_CustomerSeeder extends Dev_Database_Seeder_SeederAbstract
{

    protected $amount = 15;


    protected $addressesPerCustomer = 1;


    protected $countryId = 'GB';

    public function run()
    {
        $factory = new Dev_Database_Factory_CustomerFactory();

        $batch = $factory->count($this->amount);

        for ($i = 0; $i < $this->addressesPerCustomer; $i++) {
            $batch->withAddress(
                array('country_id' => $this->countryId),
                $i === 0
            );
        }

        $batch->create();
    }
}

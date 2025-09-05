<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Seeder_OrderSeeder extends Dev_Database_Seeder_SeederAbstract
{

    protected $ordersToCreate = 10;


    protected $itemsPerOrderMinimum = 1;
    protected $itemsPerOrderMaximum = 3;


    protected $paymentMethodCode = 'checkmo';
    protected $shippingMethodCode = 'flatrate_flatrate';


    protected $productPoolSize = 12;


    protected $createAsGuest = false;

    public function run()
    {

        $productPool = (new Dev_Database_Factory_ProductFactory())
            ->count($this->productPoolSize)
            ->create();


        $orderFactory = (new Dev_Database_Factory_OrderFactory())
            ->withPayment($this->paymentMethodCode)
            ->withShipping($this->shippingMethodCode)
            ->useProductPool($productPool)
            ->itemsPerOrder($this->itemsPerOrderMinimum, $this->itemsPerOrderMaximum);

        if ($this->createAsGuest) {
            $orderFactory->guest();
        }


        $orderFactory->count($this->ordersToCreate)->create();
    }
}

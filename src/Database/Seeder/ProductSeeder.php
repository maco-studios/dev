<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Seeder_ProductSeeder extends Dev_Database_Seeder_SeederAbstract
{

    protected $amount = 20;


    protected $categoryId = null;

    public function run()
    {
        $factory = new Dev_Database_Factory_ProductFactory();

        $batch = $factory
            ->count($this->amount)
            ->state(function (Mage_Catalog_Model_Product $p) {
                $p->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                    ->setTaxClassId(0);
            });

        if ($this->categoryId) {
            $batch->withCategory((int) $this->categoryId);
        }

        $batch->create();
    }
}

<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Seeder_CategorySeeder extends Dev_Database_Seeder_SeederAbstract
{

    protected $amount = 8;


    protected $parentId = null;

    public function run()
    {
        $factory = new Dev_Database_Factory_CategoryFactory();

        $batch = $factory->count($this->amount)
            ->state(function (Mage_Catalog_Model_Category $c) {
                $c->setIsActive(1)
                    ->setIncludeInMenu(1)
                    ->setIsAnchor(1)
                    ->setDisplayMode(Mage_Catalog_Model_Category::DM_PRODUCT);
            });

        if ($this->parentId !== null) {
            $batch->withParent((int) $this->parentId);
        } else {
            $batch->underRoot();
        }

        $batch->create();
    }
}

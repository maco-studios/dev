<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Factory_CategoryFactory extends Dev_Database_Factory_FactoryAbstract
{
    protected $modelAlias = 'catalog/category';

    protected function definition()
    {
        $faker = self::faker();
        $store = $this->frontendStore();
        $rootId = (int) $store->getRootCategoryId();

        return array(
            'store_id' => (int) $store->getId(),
            'parent_id' => $rootId,
            'name' => ucfirst($faker->unique()->words(2, true)),
            'is_active' => 1,
            'include_in_menu' => 1,
            'is_anchor' => 1,
            'display_mode' => Mage_Catalog_Model_Category::DM_PRODUCT,
            'url_key' => $faker->unique()->slug . '-' . uniqid(),
            'position' => $faker->numberBetween(1, 100),
        );
    }

    public function withParent($parentId)
    {
        $parentId = (int) $parentId;
        return $this->state(function (Mage_Catalog_Model_Category $c) use ($parentId) {
            $c->setParentId($parentId);
        });
    }

    public function underRoot()
    {
        $rootId = (int) $this->frontendStore()->getRootCategoryId();
        return $this->withParent($rootId);
    }

    /** garante path do pai para evitar explode(null) no resource */
    public function create(array $overrides = array())
    {
        /** @var Mage_Catalog_Model_Category $cat */
        $cat = $this->make($overrides);

        $parentId = (int) $cat->getParentId();
        if (!$parentId) {
            $parentId = (int) $this->frontendStore()->getRootCategoryId();
            $cat->setParentId($parentId);
        }

        $parent = Mage::getModel('catalog/category')->load($parentId);
        $cat->setPath($parent->getPath());

        $cat->save();
        return $cat;
    }

    protected function frontendStore()
    {
        foreach (Mage::app()->getStores() as $s) {
            if (!$s->isAdmin())
                return $s;
        }

        return Mage::app()->getStore(1);
    }

    public function count($n)
    {
        return new Dev_Database_Factory_FactoryBatch($this, (int) $n);
    }
}

<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Factory_ProductFactory extends Dev_Database_Factory_FactoryAbstract
{
    protected $modelAlias = 'catalog/product';

    protected function definition()
    {
        $faker = self::faker();
        $product = Mage::getModel('catalog/product');
        $attributeId = (int) $product->getDefaultAttributeSetId();
        $websiteId = (int) Mage::app()->getStore()->getWebsiteId();

        return array(
            'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
            'attribute_set_id' => $attributeId,
            'sku' => strtoupper('SKU-' . Mage::helper('core')->uniqHash()),
            'name' => $faker->words(3, true),
            'description' => $faker->sentence(12),
            'short_description' => $faker->sentence(6),
            'price' => $faker->randomFloat(2, 10, 1000),
            'weight' => $faker->randomFloat(2, 0.1, 10),
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            'tax_class_id' => 0,
            'website_ids' => array($websiteId),
            'stock_data' => array(
                'qty' => $faker->numberBetween(5, 50),
                'is_in_stock' => 1,
                'manage_stock' => 1,
                'use_config_manage_stock' => 0,
                'backorders' => 0,
                'use_config_backorders' => 0,
                'min_qty' => 0,
                'use_config_min_qty' => 0,
            ),
        );
    }

    public function create(array $overrides = array())
    {
        /** @var Mage_Catalog_Model_Product $productModel */
        $productModel = parent::create($overrides);
        $this->ensureStockItem($productModel);
        return $productModel;
    }

    protected function ensureStockItem(Mage_Catalog_Model_Product $productModel)
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productModel);

        if (!$stockItem->getId()) {
            $stockItem->setProductId((int) $productModel->getId())
                ->setStockId(1);
        }

        $qty = (float) $stockItem->getQty();
        if ($qty <= 0.0)
            $qty = 10.0;

        $stockItem->addData(array(
            'qty' => $qty,
            'is_in_stock' => 1,
            'manage_stock' => 1,
            'use_config_manage_stock' => 0,
            'backorders' => 0,
            'use_config_backorders' => 0,
            'min_qty' => 0,
            'use_config_min_qty' => 0,
        ))->save();

        // CRÍTICO: anexar o StockItem ao objeto do produto
        $productModel->setStockItem($stockItem);

        Mage::getModel('cataloginventory/stock_status')->updateStatus((int) $productModel->getId());
    }

    public function simple()
    {
        return $this->state(function (Mage_Catalog_Model_Product $p) {
            $p->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
        });
    }

    public function withCategory($categoryId)
    {
        $categoryId = (int) $categoryId;
        return $this->state(function (Mage_Catalog_Model_Product $p) use ($categoryId) {
            $ids = (array) $p->getCategoryIds();
            $ids[] = $categoryId;
            $p->setCategoryIds(array_values(array_unique(array_map('intval', $ids))));
        });
    }

    public function count($n)
    {
        return new Dev_Database_Factory_FactoryBatch($this, (int) $n);
    }
}

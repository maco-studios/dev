<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Factory_OrderFactory extends Dev_Database_Factory_FactoryAbstract
{
    protected $modelAlias = 'sales/order';


    protected $customerModel = null;


    protected $createAsGuest = false;


    protected $guestIdentityData = array();


    protected $billingAddressOverrides = array();


    protected $shippingAddressOverrides = array();


    protected $paymentMethodCode = 'checkmo';


    protected $shippingMethodCode = 'flatrate_flatrate';


    protected $productPool = array();


    protected $requestedItems = array();


    protected $itemsPerOrderRange = array(1, 1);


    protected function definition()
    {
        $currentStore = Mage::app()->getStore();
        return array(
            'store_id' => (int) $currentStore->getId(),
            'order_currency_code' => $currentStore->getCurrentCurrencyCode(),
        );
    }


    public function withCustomer(Mage_Customer_Model_Customer $customerModel)
    {
        $this->customerModel = $customerModel;
        $this->createAsGuest = false;
        return $this;
    }

    public function guest(array $guestData = array())
    {
        $faker = self::faker();
        $this->customerModel = null;
        $this->createAsGuest = true;
        $this->guestIdentityData = array_merge(array(
            'email' => strtolower($faker->unique()->userName . '+' . time() . '@example.test'),
            'firstname' => $faker->firstName,
            'lastname' => $faker->lastName,
        ), $guestData);
        return $this;
    }

    public function billTo(array $overrides)
    {
        $this->billingAddressOverrides = $overrides;
        return $this;
    }

    public function shipTo(array $overrides)
    {
        $this->shippingAddressOverrides = $overrides;
        return $this;
    }

    public function withPayment($methodCode)
    {
        $this->paymentMethodCode = (string) $methodCode;
        return $this;
    }

    public function withShipping($methodCode)
    {
        $this->shippingMethodCode = (string) $methodCode;
        return $this;
    }

    public function withProduct($productDescriptor, $quantity = 1)
    {
        $this->requestedItems[] = array('product' => $productDescriptor, 'qty' => (int) $quantity);
        return $this;
    }

    public function useProductPool(array $products)
    {
        $this->productPool = array();
        foreach ($products as $candidate) {
            $this->productPool[] = $this->resolveProduct($candidate);
        }
        return $this;
    }

    public function itemsPerOrder($minimum, $maximum = null)
    {
        $minimum = (int) $minimum;
        $maximum = $maximum === null ? $minimum : (int) $maximum;
        if ($minimum < 1)
            $minimum = 1;
        if ($maximum < $minimum)
            $maximum = $minimum;
        $this->itemsPerOrderRange = array($minimum, $maximum);
        return $this;
    }

    public function count($count)
    {
        return new Dev_Database_Factory_FactoryBatch($this, (int) $count);
    }


    public function create(array $overrides = array())
    {
        $store = Mage::app()->getStore();

        $this->ensureCustomerContextUsingCustomerFactory();

        list($billingData, $shippingData) = $this->prepareGuestAddressesIfNeeded();


        $productsWithQuantities = $this->buildItemsUsingFactoriesAndEnsureSalable();

        $configSnapshot = $this->enablePaymentAndShippingTemporarily();

        try {

            $quote = Mage::getModel('sales/quote')->setStore($store);

            if ($this->createAsGuest) {
                $quote->setCustomerIsGuest(true)
                    ->setCustomerEmail($this->guestIdentityData['email'])
                    ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
            } else {
                $quote->assignCustomer($this->customerModel);
            }

            foreach ($productsWithQuantities as $row) {

                $productModel = $row['product'];
                $quantity = (int) $row['qty'];
                $this->safeAddProductToQuote($quote, $productModel, $quantity);
            }
            $billingAddressModel = $this->getCustomerBillingAddressOrCreate($this->customerModel);
            $shippingAddressModel = $this->getCustomerShippingAddressOrCreate($this->customerModel);

            $quote->getBillingAddress()->importCustomerAddress($billingAddressModel);
            $quote->getShippingAddress()->importCustomerAddress($shippingAddressModel);

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
            $shippingAddress->setShippingMethod($this->shippingMethodCode);

            $quote->getPayment()->importData(array('method' => $this->paymentMethodCode));

            $quote->collectTotals()->save();


            $serviceQuote = Mage::getModel('sales/service_quote', $quote);
            $serviceQuote->submitAll();


            $orderModel = $serviceQuote->getOrder();

            return $orderModel;
        } finally {
            $this->restoreConfigSnapshot($configSnapshot);
        }
    }


    protected function ensureCustomerContextUsingCustomerFactory()
    {
        if (!$this->createAsGuest && !$this->customerModel) {
            $customerFactory = new Dev_Database_Factory_CustomerFactory();
            $this->customerModel = $customerFactory
                ->withAddress(array('country_id' => 'GB'), true)
                ->create();
        }

        if (!$this->createAsGuest) {
            $customer = $this->customerModel;

            $hasBilling = (bool) $customer->getDefaultBillingAddress();
            $hasShipping = (bool) $customer->getDefaultShippingAddress();

            if (!$hasBilling || !$hasShipping) {
                $customerFactory = new Dev_Database_Factory_CustomerFactory();
                if (!$hasBilling) {
                    $customerFactory->createAddressFor($customer, array('country_id' => 'GB'), true);
                }
                if (!$hasShipping) {
                    $customerFactory->createAddressFor($customer, array('country_id' => 'GB'), true);
                }
                $customer->load($customer->getId());
            }
        }
    }

    protected function prepareGuestAddressesIfNeeded()
    {
        if (!$this->createAsGuest) {
            return array(array(), array());
        }

        $faker = self::faker();

        $base = array(
            'firstname' => $this->guestIdentityData['firstname'],
            'lastname' => $this->guestIdentityData['lastname'],
            'email' => $this->guestIdentityData['email'],
            'street' => array($faker->streetAddress),
            'city' => $faker->city,
            'postcode' => preg_replace('/\D+/', '', $faker->postcode),
            'telephone' => preg_replace('/\D+/', '', $faker->phoneNumber),
            'country_id' => 'GB',
        );

        $billingData = array_merge($base, $this->billingAddressOverrides);
        $shippingData = array_merge($base, $this->shippingAddressOverrides);

        return array($billingData, $shippingData);
    }


    protected function buildItemsUsingFactoriesAndEnsureSalable()
    {
        $items = $this->requestedItems;

        if (empty($items)) {
            $minimum = $this->itemsPerOrderRange[0];
            $maximum = $this->itemsPerOrderRange[1];
            $itemsCount = ($minimum === $maximum) ? $minimum : mt_rand($minimum, $maximum);

            if (empty($this->productPool)) {

                $created = (new Dev_Database_Factory_ProductFactory())
                    ->count($itemsCount)
                    ->create();

                $items = array();
                foreach ($created as $createdProduct) {
                    if ($this->isProductStrictlySalable($createdProduct, 1)) {
                        $items[] = array('product' => $createdProduct, 'qty' => 1);
                    }
                }
            } else {
                $items = array();
                for ($i = 0; $i < $itemsCount; $i++) {
                    $candidate = $this->productPool[array_rand($this->productPool)];
                    if ($this->isProductStrictlySalable($candidate, 1)) {
                        $items[] = array('product' => $candidate, 'qty' => 1);
                    } else {

                        $fresh = (new Dev_Database_Factory_ProductFactory())->create();
                        if ($this->isProductStrictlySalable($fresh, 1)) {
                            $items[] = array('product' => $fresh, 'qty' => 1);
                        }
                    }
                }
            }
        } else {

            $resolved = array();
            foreach ($items as $row) {
                $productModel = $this->resolveProduct($row['product']);
                $quantity = max(1, (int) $row['qty']);
                if ($this->isProductStrictlySalable($productModel, $quantity)) {
                    $resolved[] = array('product' => $productModel, 'qty' => $quantity);
                }
            }
            $items = $resolved;
        }

        if (empty($items)) {
            $fallback = (new Dev_Database_Factory_ProductFactory())->create();
            $items = array(array('product' => $fallback, 'qty' => 1));
        }

        return $items;
    }

    protected function isProductStrictlySalable(Mage_Catalog_Model_Product $productModel, $requestedQuantity)
    {
        if ((int) $productModel->getStatus() !== Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            return false;
        }


        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productModel);
        if (!$stockItem->getId()) {
            return false;
        }

        $isInStock = (bool) $stockItem->getIsInStock();
        $availableQuantity = (float) $stockItem->getQty();
        $backordersPolicy = (int) $stockItem->getBackorders();

        if (!$isInStock) {
            return false;
        }

        if ($backordersPolicy === 0 && $availableQuantity < (float) $requestedQuantity) {
            return false;
        }

        return true;
    }

    protected function resolveProduct($descriptor)
    {
        if ($descriptor instanceof Mage_Catalog_Model_Product) {
            return $descriptor;
        }
        if (is_int($descriptor) || ctype_digit((string) $descriptor)) {
            $model = Mage::getModel('catalog/product')->load((int) $descriptor);
            if ($model && $model->getId())
                return $model;
        }
        if (is_string($descriptor)) {
            $model = Mage::getModel('catalog/product')->loadByAttribute('sku', $descriptor);
            if ($model && $model->getId())
                return $model;
        }

        return (new Dev_Database_Factory_ProductFactory())->create();
    }

    protected function safeAddProductToQuote(Mage_Sales_Model_Quote $quote, Mage_Catalog_Model_Product $productModel, $quantity)
    {
        $this->attachExistingStockItem($productModel);

        $buyRequest = new Varien_Object(array('qty' => (int) $quantity));
        $quote->addProductAdvanced(
            $productModel,
            $buyRequest,
            Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL
        );
    }

    protected function attachExistingStockItem(Mage_Catalog_Model_Product $productModel)
    {

        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productModel);
        if ($stockItem && $stockItem->getId()) {
            $productModel->setStockItem($stockItem);
        }
    }



    protected function enablePaymentAndShippingTemporarily()
    {
        $carrierCode = $this->extractCarrierCode($this->shippingMethodCode);

        $originalPaymentActive = Mage::getStoreConfig("payment/{$this->paymentMethodCode}/active");
        $originalCarrierActive = Mage::getStoreConfig("carriers/{$carrierCode}/active");

        if (!$originalPaymentActive) {
            Mage::getConfig()->saveConfig("payment/{$this->paymentMethodCode}/active", 1);
        }
        if (!$originalCarrierActive) {
            Mage::getConfig()->saveConfig("carriers/{$carrierCode}/active", 1);
        }

        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
        Mage::app()->getCacheInstance()->cleanType('config');

        return array(
            'paymentPath' => "payment/{$this->paymentMethodCode}/active",
            'carrierPath' => "carriers/{$carrierCode}/active",
            'paymentValue' => (int) $originalPaymentActive,
            'carrierValue' => (int) $originalCarrierActive,
        );
    }

    protected function restoreConfigSnapshot(array $snapshot)
    {
        Mage::getConfig()->saveConfig($snapshot['paymentPath'], $snapshot['paymentValue']);
        Mage::getConfig()->saveConfig($snapshot['carrierPath'], $snapshot['carrierValue']);
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
        Mage::app()->getCacheInstance()->cleanType('config');
    }

    protected function extractCarrierCode($shippingMethodCode)
    {
        list($carrierCode, ) = array_pad(explode('_', $shippingMethodCode, 2), 2, null);
        return $carrierCode ?: $shippingMethodCode;
    }

    protected function getCustomerBillingAddressOrCreate(Mage_Customer_Model_Customer $customer)
    {
        $address = $customer->getDefaultBillingAddress();
        if ($address && $address->getId()) {
            return $address;
        }


        $existing = $customer->getAddressesCollection()->getFirstItem();
        if ($existing && $existing->getId()) {

            $customer->setDefaultBilling($existing->getId())->save();
            return $existing;
        }


        $customerFactory = new Dev_Database_Factory_CustomerFactory();
        $created = $customerFactory->createAddressFor($customer, array('country_id' => 'GB'), true);

        $customer->setDefaultBilling($created->getId())->setDefaultShipping($created->getId())
            ->save();
        $customer->load($customer->getId());
        $defaultBilling = Mage::getModel('customer/address')->load($customer->getData('default_billing'));
        return $defaultBilling;
    }

    protected function getCustomerShippingAddressOrCreate(Mage_Customer_Model_Customer $customer)
    {
        $address = $customer->getDefaultShippingAddress();
        if ($address && $address->getId()) {
            return $address;
        }


        $billing = $customer->getDefaultBillingAddress();
        if ($billing && $billing->getId()) {
            $customer->setDefaultShipping($billing->getId())->save();
            return $customer->getDefaultShippingAddress();
        }


        $existing = $customer->getAddressesCollection()->getFirstItem();
        if ($existing && $existing->getId()) {
            $customer->setDefaultShipping($existing->getId())->save();
            return $customer->getDefaultShippingAddress();
        }


        $customerFactory = new Dev_Database_Factory_CustomerFactory();
        $created = $customerFactory->createAddressFor($customer, array('country_id' => 'GB'), true);
        $customer->setDefaultBilling($created->getId())->setDefaultShipping($created->getId())->save();
        $customer->load($customer->getId());

        $defaultShipping = Mage::getModel('customer/address')->load($customer->getData('default_shipping'));

        return $defaultShipping;

    }



}

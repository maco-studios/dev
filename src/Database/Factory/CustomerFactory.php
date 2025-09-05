<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Factory_CustomerFactory extends Dev_Database_Factory_FactoryAbstract
{
    protected $modelAlias = 'customer/customer';

    protected function definition()
    {
        $faker = self::faker();
        $store = Mage::app()->getStore();
        $websiteId = (int) $store->getWebsiteId();
        $groupId = (int) Mage::getStoreConfig('customer/create_account/default_group', $store);
        $password = $faker->password(10, 14);

        return array(
            'website_id' => $websiteId,
            'store_id' => (int) $store->getId(),
            'group_id' => $groupId ?: 1,
            'email' => strtolower($faker->unique()->userName . '+' . time() . '@example.test'),
            'firstname' => $faker->firstName,
            'lastname' => $faker->lastName,

            '_factory_password' => $password,
        );
    }


    public function create(array $overrides = array())
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->make($overrides);

        if ($pw = $customer->getData('_factory_password')) {
            $customer->unsetData('_factory_password');
            $customer->setPassword($pw);
        }

        $customer = $customer->save();


        $pending = (array) $customer->getData('_factory_with_addresses');
        if ($pending) {
            foreach ($pending as $cfg) {
                $this->createAddressFor($customer, $cfg['overrides'], (bool) $cfg['asDefault']);
            }
            $customer->unsetData('_factory_with_addresses');
        }

        return $customer;
    }


    public function withAddress(array $overrides = array(), $asDefault = true)
    {
        return $this->state(function (Mage_Customer_Model_Customer $c) use ($overrides, $asDefault) {
            $queued = (array) $c->getData('_factory_with_addresses');
            $queued[] = array('overrides' => $overrides, 'asDefault' => (bool) $asDefault);
            $c->setData('_factory_with_addresses', $queued);
        });
    }


    public function createAddressFor(Mage_Customer_Model_Customer $customer, array $overrides = array(), $asDefault = true)
    {
        $faker = self::faker();

        $data = array_merge(array(
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'street' => array($faker->streetAddress),
            'city' => $faker->city,
            'postcode' => preg_replace('/\D+/', '', $faker->postcode),
            'telephone' => preg_replace('/\D+/', '', $faker->phoneNumber),
            'country_id' => 'GB',
        ), $overrides);

        /** @var Mage_Customer_Model_Address $address */
        $address = Mage::getModel('customer/address');
        $address->setData($data)
            ->setCustomerId((int) $customer->getId());

        if ($asDefault) {
            $address->setIsDefaultBilling(1)
                ->setIsDefaultShipping(1);
        }

        $address->save();
        return $address;
    }
}

<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

abstract class Dev_Database_Seeder_SeederAbstract
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Call another seeder class.
     *
     * @param string $seeder
     * @return void
     */
    public function call($seeder)
    {
        $seederClass = 'Dev_Database_Seeder_' . $seeder;

        if (!class_exists($seederClass)) {
            throw new Exception("Seeder class {$seederClass} not found");
        }

        $seederInstance = new $seederClass();
        $seederInstance->run();
    }

    /**
     * Get a factory instance.
     *
     * @param string $factory Factory name (without "Factory" suffix)
     * @return Dev_Database_Factory_FactoryAbstract
     */
    protected function factory($factory)
    {
        $factoryClass = 'Dev_Database_Factory_' . $factory . 'Factory';

        if (!class_exists($factoryClass)) {
            throw new Exception("Factory class {$factoryClass} not found");
        }

        return new $factoryClass();
    }
}

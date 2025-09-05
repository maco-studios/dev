<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

abstract class Dev_Database_Factory_FactoryAbstract
{
    protected static $faker = null;
    protected $states = [];

    protected $modelAlias;


    abstract protected function definition();


    public function make(array $overrides = [])
    {
        $data = array_merge($this->definition(), $overrides);
        $model = Mage::getModel($this->modelAlias);
        $model->addData($data);
        $this->applyStates($model);
        return $model;
    }


    public function create(array $overrides = [])
    {
        $model = $this->make($overrides);
        $model->save();
        return $model;
    }


    public function state(callable $callback)
    {
        $this->states[] = $callback;
        return $this;
    }

    public static function faker()
    {
        if (self::$faker === null) {
            self::$faker = Faker\Factory::create();
        }
        return self::$faker;
    }


    protected function applyStates($model)
    {
        foreach ($this->states as $cb) {
            $cb($model);
        }
        $this->states = [];
    }

    public function count(int $n)
    {
        return new Dev_Database_Factory_FactoryBatch($this, $n);
    }
}

<?php

/**
 * Copyright (c) 2025 Marcos "Marcão" Aurelio
 *
 * @see https://github.com/maco-studios/console
 */

class Dev_Database_Factory_FactoryBatch
{
    protected $factory;
    protected $n;

    public function __construct(Dev_Database_Factory_FactoryAbstract $factory, $n)
    {
        $this->factory = $factory;
        $this->n = (int) $n;
    }


    public function state(callable $callback)
    {
        $this->factory->state($callback);
        return $this;
    }


    public function __call($name, $args)
    {
        if (method_exists($this->factory, $name)) {
            call_user_func_array([$this->factory, $name], $args);
            return $this;
        }
        throw new BadMethodCallException("Method {$name} does not exist on FactoryBatch or underlying factory.");
    }

    public function make(array $overrides = [])
    {
        $out = [];
        for ($i = 0; $i < $this->n; $i++) {
            $f = clone $this->factory;
            $out[] = $f->make($overrides);
        }
        return $out;
    }

    public function create(array $overrides = [])
    {
        $out = [];
        for ($i = 0; $i < $this->n; $i++) {
            $f = clone $this->factory;
            $out[] = $f->create($overrides);
        }
        return $out;
    }
}

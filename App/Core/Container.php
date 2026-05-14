<?php

namespace App\Core;

class Container
{
    private static ?self $instance = null;

    private function __construct(
        private array $bindings = [], 
        private array $singletons = []
    ) {}

    public static function getContainer(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getInstancia(string $key): mixed
    {
        if (!isset($this->bindings[$key])) {
            throw new \Exception("Binding not found: {$key}");
        }

        if (array_key_exists($key, $this->singletons)) {
            if ($this->singletons[$key] === null) {
                $this->singletons[$key] = $this->bindings[$key]($this);
            }
            return $this->singletons[$key];
        }

        return $this->bindings[$key]($this);
    }

    public function bind(string $key, callable $resolver, bool $singleton = false): void
    {
       $this->bindings[$key] = $resolver;
        if ($singleton) {
            $this->singletons[$key] = null;
        }
    }
    private static function teste() {

    }
}
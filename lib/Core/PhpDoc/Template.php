<?php

namespace Phpactor\WorseReflection\Core\PhpDoc;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

class Template
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var ReflectionType|null
     */
    private $constaint;

    public function __construct(string $name, ?ReflectionType $constaint = null)
    {
        $this->name = $name;
        $this->constaint = $constaint;
    }

    public function constaint(): ?ReflectionType
    {
        return $this->constaint;
    }

    public function name(): string
    {
        return $this->name;
    }
}

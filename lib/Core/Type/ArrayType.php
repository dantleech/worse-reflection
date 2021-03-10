<?php

namespace Phpactor\WorseReflection\Core\Type;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

class ArrayType implements ReflectionType
{
    /**
     * @var ReflectionType
     */
    private $key;
    /**
     * @var ReflectionType
     */
    private $value;

    public function __construct(?ReflectionType $key = null, ?ReflectionType $value = null)
    {
        $this->key = $key ?: new UndefinedType();
        $this->value = $value ?: new UndefinedType();
    }
}

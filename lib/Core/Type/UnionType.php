<?php

namespace Phpactor\WorseReflection\Core\Type;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

class UnionType implements ReflectionType
{
    /**
     * @var array
     */
    private $types;

    public function __construct(array $types)
    {
        $this->types = $types;
    }
}

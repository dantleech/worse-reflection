<?php

namespace Phpactor\WorseReflection\Core\Type;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

class Template
{
    /**
     * @var array<string,ReflectionType>
     */
    private $map;

    public function __construct(array $map = [])
    {
        $this->map = $map;
    }
}

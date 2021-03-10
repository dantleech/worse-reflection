<?php

namespace Phpactor\WorseReflection\Core\Type;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

class GenericType implements ReflectionType
{
    /**
     * @var ClassType
     */
    private $type;
    /**
     * @var ReflectionType[]
     */
    private $parameters;

    public function __construct(ClassType $type, array $parameters)
    {
        $this->type = $type;
        $this->parameters = array_values($parameters);
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function type(): ClassType
    {
        return $this->type;
    }
}

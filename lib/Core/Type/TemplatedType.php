<?php

namespace Phpactor\WorseReflection\Core\Type;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

class TemplatedType implements ReflectionType
{
    /**
     * @var ReflectionType|null
     */
    private $constraint;

    /**
     * @var string
     */
    private $placeholder;

    public function __construct(string $placeholder, ?ReflectionType $constraint = null)
    {
        $this->constraint = $constraint;
        $this->placeholder = $placeholder;
    }
}

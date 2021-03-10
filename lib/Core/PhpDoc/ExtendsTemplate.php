<?php

namespace Phpactor\WorseReflection\Core\PhpDoc;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

final class ExtendsTemplate
{
    /**
     * @var ReflectionType
     */
    private $type;

    public function __construct(ReflectionType $type)
    {
        $this->type = $type;
    }

    public function type(): ReflectionType
    {
        return $this->type;
    }
}

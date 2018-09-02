<?php

namespace Phpactor\WorseReflection\Core\Virtual\Collection;

use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionParameterCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;

class VirtualReflectionParameterCollection extends AbstractReflectionCollection implements ReflectionParameterCollection
{
    protected function collectionType(): string
    {
        return ReflectionParameterCollection::class;
    }

    public function fromReflectionParameters(array $reflectionParameters)
    {
        $parameters = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameters[$reflectionParameter->name()] = $reflectionParameter;
        }

        return new static($parameters);
    }

    public static function empty()
    {
        return new static([]);
    }
}

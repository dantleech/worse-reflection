<?php

namespace Phpactor\WorseReflection\Core\Virtual\Collection;

use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionPropertyCollection;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionProperty;

/**
 * @extends VirtualReflectionMemberCollection<VirtualReflectionProperty>
 */
class VirtualReflectionPropertyCollection extends VirtualReflectionMemberCollection implements ReflectionPropertyCollection
{
    public static function fromReflectionProperties(array $reflectionProperties): self
    {
        $properties = [];
        foreach ($reflectionProperties as $reflectionProperty) {
            $properties[$reflectionProperty->name()] = $reflectionProperty;
        }
        return new self($properties);
    }

    protected function collectionType(): string
    {
        return ReflectionPropertyCollection::class;
    }
}

<?php

namespace Phpactor\WorseReflection\Bridge\Phpactor\MemberProvider;

use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMemberCollection;
use Phpactor\WorseReflection\Core\ServiceLocator;
use Phpactor\WorseReflection\Core\Virtual\Collection\VirtualReflectionMemberCollection;
use Phpactor\WorseReflection\Core\Virtual\ReflectionMemberProvider;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMember;

class MixinMemberProvider implements ReflectionMemberProvider
{
    public function provideMembers(ServiceLocator $locator, ReflectionClassLike $class): ReflectionMemberCollection
    {
        $collection = VirtualReflectionMemberCollection::fromMembers([]);
        foreach ($class->docblock()->mixins() as $name) {
            $fqn = $class->scope()->resolveLocalName($name);
            $mixinClass = $locator->reflector()->reflectClass($fqn);

            $collection = $collection->merge($mixinClass->members());
        }

        return $collection;
    }
}

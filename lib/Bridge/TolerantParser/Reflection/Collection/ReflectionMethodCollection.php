<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection;

use Phpactor\WorseReflection\Core\ServiceLocator;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionMethod;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\Statement\InterfaceDeclaration;
use Phpactor\WorseReflection\Core\ClassName;
use Microsoft\PhpParser\Node\Statement\TraitDeclaration;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionTrait;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMethodCollection as CoreReflectionMethodCollection;

/**
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionMethod get()
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionMethod first()
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionMethod last()
 */
class ReflectionMethodCollection extends AbstractReflectionCollection implements CoreReflectionMethodCollection
{
    public static function fromClassDeclaration(ServiceLocator $serviceLocator, ClassDeclaration $class, ReflectionClass $reflectionClass)
    {
        /** @var MethodDeclaration[] $methods */
        $methods = array_filter($class->classMembers->classMemberDeclarations, function ($member) {
            return $member instanceof MethodDeclaration;
        });

        $items = [];
        foreach ($methods as $method) {
            $items[$method->getName()] = new ReflectionMethod($serviceLocator, $reflectionClass, $method);
        }

        return new static($serviceLocator, $items);
    }

    public static function fromInterfaceDeclaration(ServiceLocator $serviceLocator, InterfaceDeclaration $interface, ReflectionInterface $reflectionInterface): CoreReflectionMethodCollection
    {
        /** @var MethodDeclaration[] $methods */
        $methods = array_filter($interface->interfaceMembers->interfaceMemberDeclarations, function ($member) {
            return $member instanceof MethodDeclaration;
        });

        $items = [];
        foreach ($methods as $method) {
            $items[$method->getName()] = new ReflectionMethod($serviceLocator, $reflectionInterface, $method);
        }

        return new static($serviceLocator, $items);
    }

    public static function fromTraitDeclaration(ServiceLocator $serviceLocator, TraitDeclaration $trait, ReflectionTrait $reflectionTrait): CoreReflectionMethodCollection
    {
        /** @var MethodDeclaration[] $methods */
        $methods = array_filter($trait->traitMembers->traitMemberDeclarations, function ($member) {
            return $member instanceof MethodDeclaration;
        });

        $items = [];
        foreach ($methods as $method) {
            $items[$method->getName()] = new ReflectionMethod($serviceLocator, $reflectionTrait, $method);
        }

        return new static($serviceLocator, $items);
    }

    public static function fromReflectionMethods(ServiceLocator $serviceLocator, array $methods): CoreReflectionMethodCollection
    {
        return new static($serviceLocator, $methods);
    }

    public function byVisibilities(array $visibilities): CoreReflectionMethodCollection
    {
        $items = [];
        foreach ($this->items as $key => $item) {
            foreach ($visibilities as $visibility) {
                if ($item->visibility() != $visibility) {
                    continue;
                }

                $items[$key] = $item;
            }
        }

        return new static($this->serviceLocator, $items);
    }

    public function belongingTo(ClassName $class): CoreReflectionMethodCollection
    {
        return new self($this->serviceLocator, array_filter($this->items, function (ReflectionMethod $item) use ($class) {
            return $item->declaringClass()->name() == $class;
        }));
    }

    public function abstract()
    {
        return new self($this->serviceLocator, array_filter($this->items, function (ReflectionMethod $item) {
            return $item->isAbstract();
        }));
    }

    public function atOffset(int $offset): ReflectionMethodCollection
    {
        return new self($this->serviceLocator, array_filter($this->items, function (ReflectionMethod $item) use ($offset) {
            return $item->position()->start() <= $offset && $item->position()->end() >= $offset;
        }));
    }
}

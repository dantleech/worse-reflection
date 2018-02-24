<?php

namespace Phpactor\WorseReflection\Core\Reflector;

use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Core\Reflection\ReflectionTrait;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionOffset;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethodCall;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionClassCollection;

class CompositeReflector implements Reflector
{
    /**
     * @var ClassReflector
     */
    private $classReflector;

    /**
     * @var SourceCodeReflector
     */
    private $sourceCodeReflector;

    public function __construct(
        ClassReflector $classReflector,
        SourceCodeReflector $sourceCodeReflector
    )
    {
        $this->classReflector = $classReflector;
        $this->sourceCodeReflector = $sourceCodeReflector;
    }

    /**
     * {@inheritDoc}
     */
    public function reflectClass($className): ReflectionClass
    {
        return $this->classReflector->reflectClass($className);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectInterface($className): ReflectionInterface
    {
        return $this->classReflector->reflectInterface($className);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectTrait($className): ReflectionTrait
    {
        return $this->classReflector->reflectTrait($className);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectClassLike($className): ReflectionClassLike
    {
        return $this->classReflector->reflectClassLike($className);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectClassesIn($sourceCode): ReflectionClassCollection
    {
        return $this->sourceCodeReflector->reflectClassesIn($sourceCode);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectOffset($sourceCode, $offset): ReflectionOffset
    {
        return $this->sourceCodeReflector->reflectOffset($sourceCode, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectMethodCall($sourceCode, $offset): ReflectionMethodCall
    {
        return $this->sourceCodeReflector->reflectMethodCall($sourceCode, $offset);
    }
}

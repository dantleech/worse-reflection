<?php

namespace Phpactor\WorseReflection\Core\PhpDoc;

use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionNode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionType;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;
use Phpactor\WorseReflection\Core\Type\ClassType;
use Phpactor\WorseReflection\Core\Type\GenericType;
use Phpactor\WorseReflection\Core\Type\UndefinedType;
use RuntimeException;

class GenericTypeResolver
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    public function __construct(ClassReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function resolve(ReflectionNode $reflectionNode): ReflectionType
    {
        if ($reflectionNode instanceof ReflectionMethod) {
            return $this->resolveMethod($reflectionNode);
        }
        throw new RuntimeException(sprintf(
            'Unable to resolve type of reflection node "%s"',
            get_class($reflectionNode)
        ));
    }

    private function resolveMethod(ReflectionMethod $reflectionMethod): ReflectionType
    {
        $type = $reflectionMethod->phpdoc()->returnType();
        if (!$type instanceof ClassType) {
            return $type;
        }

        $decaringTemplates = $reflectionMethod->declaringClass()->phpdoc()->templates();
        $localName = $reflectionMethod->scope()->resolveLocalName($type->name())->__toString();

        if (!$decaringTemplates->has($localName)) {
            return $type;
        }

        $extendsType = (function (?ExtendsTemplate $extends, Template $template) use ($decaringTemplates): ?ReflectionType {
            if (!$extends) {
                return null;
            }
            $type = $extends->type();
            if (!$type instanceof GenericType) {
                return null;
            }

            $parameters = $type->parameters();
            $parameter = $parameters[$decaringTemplates->offset($template)] ?? null;

            if (!$parameter) {
                return null;
            }

            return $parameter;
        })($reflectionMethod->class()->phpdoc()->extends(), $decaringTemplates->get($localName));

        if ($extendsType) {
            return $extendsType;
        }

        return new UndefinedType();
    }
}

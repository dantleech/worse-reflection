<?php

namespace Phpactor\WorseReflection\Bridge\Phpactor;

use Phpactor\Docblock\Ast\Docblock as PhpactorDocblock;
use Phpactor\Docblock\Ast\ParameterList;
use Phpactor\Docblock\Ast\Tag\DeprecatedTag;
use Phpactor\Docblock\Ast\Tag\MethodTag;
use Phpactor\Docblock\Ast\Tag\ParamTag;
use Phpactor\Docblock\Ast\Tag\ParameterTag;
use Phpactor\Docblock\Ast\Tag\PropertyTag;
use Phpactor\Docblock\Ast\Tag\ReturnTag;
use Phpactor\Docblock\Ast\Tag\VarTag;
use Phpactor\Docblock\Ast\TypeNode;
use Phpactor\Docblock\Ast\Type\ClassNode;
use Phpactor\Docblock\Ast\Type\GenericNode;
use Phpactor\Docblock\Ast\Type\ListNode;
use Phpactor\Docblock\Ast\Type\NullableNode;
use Phpactor\Docblock\Ast\Type\ScalarNode;
use Phpactor\Docblock\Ast\Type\ThisNode;
use Phpactor\Docblock\Ast\Type\UnionNode;
use Phpactor\WorseReflection\Core\DefaultValue;
use Phpactor\WorseReflection\Core\Deprecation;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock as CoreDocblock;
use Phpactor\WorseReflection\Core\DocBlock\DocBlockVar;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\NodeText;
use Phpactor\WorseReflection\Core\Position;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMethodCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionPropertyCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\DocBlock\DocBlockVars;
use Phpactor\WorseReflection\Core\Virtual\Collection\VirtualReflectionMethodCollection;
use Phpactor\WorseReflection\Core\Virtual\Collection\VirtualReflectionParameterCollection;
use Phpactor\WorseReflection\Core\Virtual\Collection\VirtualReflectionPropertyCollection;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionMethod;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionParameter;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionProperty;
use Phpactor\WorseReflection\Core\Visibility;

class Docblock implements CoreDocblock
{
    /**
     * @var string
     */
    private $raw;

    /**
     * @var PhpactorDocblock
     */
    private $node;

    public function __construct(
        string $raw,
        PhpactorDocblock $node
    ) {
        $this->raw = $raw;
        $this->node = $node;
    }

    public function parameterTypes(string $paramName): Types
    {
        foreach ($this->node->tags(ParamTag::class) as $child) {
            assert($child instanceof ParamTag);

            if (!$child->type) {
                continue;
            }

            if (!$child->variable) {
                continue;
            }

            if ($child->variable->toString() !== '$' . $paramName) {
                continue;
            }

            return $this->typesFrom($child->type);
        }

        return Types::empty();
    }

    public function propertyTypes(string $propertyName): Types
    {
        foreach ($this->node->tags(PropertyTag::class) as $child) {
            assert($child instanceof PropertyTag);

            if (!$child->type) {
                continue;
            }

            if (!$child->name) {
                continue;
            }

            if (ltrim($child->name->toString(), '$') !== $propertyName) {
                continue;
            }

            return $this->typesFrom($child->type);
        }

        return Types::empty();
    }

    public function vars(): DocBlockVars
    {
        $types = [];
        foreach ($this->node->tags(VarTag::class) as $child) {
            assert($child instanceof VarTag);
            $variable = $child->variable;
            $types[] = new DocBlockVar(
                $variable ? $variable->toString() : '',
                $this->typesFrom($child->type)
            );
        }
        return new DocBlockVars($types);
    }

    public function inherits(): bool
    {
        return false;
    }

    public function methodTypes(string $methodName): Types
    {
        foreach ($this->node->tags(MethodTag::class) as $child) {
            assert($child instanceof MethodTag);

            if (!$child->type) {
                continue;
            }

            if (!$child->name) {
                continue;
            }

            if ($child->name->toString() !== $methodName) {
                continue;
            }

            return $this->typesFrom($child->type);
        }

        return Types::empty();
    }

    public function formatted(): string
    {
        return preg_replace('{\n +}', "\n", $this->node->prose());
    }

    public function returnTypes(): Types
    {
        $types = [];
        foreach ($this->node->tags(ReturnTag::class) as $child) {
            if (!$child->type) {
                continue;
            }
            return $this->typesFrom($child->type);
        }

        return Types::empty();
    }


    public function raw(): string
    {
        return $this->raw;
    }

    public function isDefined(): bool
    {
        return !empty(trim($this->raw));
    }

    public function properties(ReflectionClassLike $declaringClass): ReflectionPropertyCollection
    {
        $properties = [];
        foreach ($this->node->tags(PropertyTag::class) as $property) {
            assert($property instanceof PropertyTag);
            $name = $property->name;
            if (!$name) {
                continue;
            }
            $type = $property->type;
            $properties[] = new VirtualReflectionProperty(
                Position::fromStartAndEnd($property->start(), $property->start()),
                $declaringClass,
                $declaringClass,
                ltrim($name->toString(), '$'),
                new Frame(''),
                $this,
                $declaringClass->scope(),
                Visibility::public(),
                $this->typesFrom($type),
                $type ? Type::fromString($type->toString()) : Type::undefined(),
                new Deprecation($property->hasChild(DeprecatedTag::class))
            );
        }

        return VirtualReflectionPropertyCollection::fromMembers($properties);
    }

    public function methods(ReflectionClassLike $declaringClass): ReflectionMethodCollection
    {
        $methods = [];
        foreach ($this->node->tags(MethodTag::class) as $method) {
            assert($method instanceof MethodTag);
            $name = $method->name;

            if (!$name) {
                continue;
            }

            $originalMethod = $declaringClass->methods()->has($name->toString()) ?
                $declaringClass->methods()->get($name->toString()) : null;

            $parameters = VirtualReflectionParameterCollection::empty();
            $text = $method->text;
            $reflectionMethod = new VirtualReflectionMethod(
                Position::fromStartAndEnd($method->start(), $method->start()),
                $declaringClass,
                $declaringClass,
                $method->name->toString(),
                new Frame(''),
                $this,
                $declaringClass->scope(),
                Visibility::public(),
                Types::fromTypes(array_map(function (Type $type) use ($declaringClass) {
                    $name = $type->className();
                    if (null === $name) {
                        return $type;
                    }
                    if ($name->wasFullyQualified()) {
                        return $type;
                    }
                    return $type->withClassName($declaringClass->scope()->resolveFullyQualifiedName($name));
                }, iterator_to_array($this->typesFrom($method->type), false))),
                $originalMethod ? $originalMethod->type() : Type::undefined(),
                $parameters,
                NodeText::fromString($text ? $text->toString() : ''),
                false,
                $method->static ? true : false,
                new Deprecation($this->node->hasTag(DeprecatedTag::class))
            );
            $this->methodParameter($parameters, $reflectionMethod, $method->parameters);
            $methods[] = $reflectionMethod;
        }

        return VirtualReflectionMethodCollection::fromMembers($methods);
    }

    public function deprecation(): Deprecation
    {
        foreach ($this->node->tags(DeprecatedTag::class) as $child) {
            assert($child instanceof DeprecatedTag);
            return new Deprecation(true, trim($child->text->toString()));
        }
        return new Deprecation(false);
    }

    private function typesFrom(?TypeNode $type = null): Types
    {
        if (null === $type) {
            return Types::empty();
        }
        $nullable = false;

        if ($type instanceof NullableNode) {
            $nullable = true;
            $type = $type->type;
        }

        if ($type instanceof ThisNode) {
            return Types::fromTypes([Type::fromString('$this')]);
        }

        if (
            $type instanceof ClassNode ||
            $type instanceof ScalarNode
        ) {
            $type = Type::fromString($type->toString());
            if ($nullable) {
                $type = $type->asNullable();
            }

            return Types::fromTypes([$type]);
        }

        if ($type instanceof UnionNode) {
            return Types::fromTypes(array_map(function (TypeNode $typeNode) {
                return $this->typesFrom($typeNode)->best();
            }, iterator_to_array($type->types->types())));
        }

        if ($type instanceof ListNode) {
            $type = $this->typesFrom($type->type);
            return Types::fromTypes([
                Type::array()->withArrayType($type->best())
            ]);
        }

        // fake generic support
        if ($type instanceof GenericNode) {
            $classType = $this->typesFrom($type->type)->best();
            $iterableType = $type->parameters()->firstDescendant(ClassNode::class);
            if ($iterableType instanceof ClassNode) {
                $classType = $classType->withArrayType($this->typesFrom($iterableType)->best());
            }
            return Types::fromTypes([
                $classType
            ]);
        }

        return Types::empty();
    }

    private function methodParameter(
        VirtualReflectionParameterCollection $collection,
        VirtualReflectionMethod $method,
        ?ParameterList $parameterList = null
    ): void {
        if (!$parameterList) {
            return;
        }

        $params = [];
        foreach ($parameterList->parameters() as $parameterNode) {
            assert($parameterNode instanceof ParameterTag);
            if (!$parameterNode->name) {
                continue;
            }

            $types = $this->typesFrom($parameterNode->type);
            $collection->add(new VirtualReflectionParameter(
                ltrim($parameterNode->name->toString(), '$'),
                $method,
                $types,
                $types->best(),
                DefaultValue::fromValue(null),
                false,
                $method->scope(),
                $method->position()
            ));
        }
    }
}

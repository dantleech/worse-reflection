<?php

namespace Phpactor\WorseReflection\Bridge\Phpactor;

use Phpactor\Docblock\Ast\Docblock as PhpactorDocblock;
use Phpactor\Docblock\Ast\Tag\DeprecatedTag;
use Phpactor\Docblock\Ast\Tag\MethodTag;
use Phpactor\Docblock\Ast\Tag\ParamTag;
use Phpactor\Docblock\Ast\Tag\PropertyTag;
use Phpactor\Docblock\Ast\Tag\ReturnTag;
use Phpactor\Docblock\Ast\Tag\VarTag;
use Phpactor\Docblock\Ast\Token;
use Phpactor\Docblock\Ast\TypeList;
use Phpactor\Docblock\Ast\TypeNode;
use Phpactor\Docblock\Ast\Type\ClassNode;
use Phpactor\Docblock\Ast\Type\GenericNode;
use Phpactor\Docblock\Ast\Type\ListNode;
use Phpactor\Docblock\Ast\Type\NullableNode;
use Phpactor\Docblock\Ast\Type\ScalarNode;
use Phpactor\Docblock\Ast\Type\UnionNode;
use Phpactor\WorseReflection\Core\Deprecation;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock as CoreDocblock;
use Phpactor\WorseReflection\Core\DocBlock\DocBlockVar;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Position;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMethodCollection;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionPropertyCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\DocBlock\DocBlockVars;
use Phpactor\WorseReflection\Core\Virtual\Collection\VirtualReflectionMethodCollection;
use Phpactor\WorseReflection\Core\Virtual\Collection\VirtualReflectionPropertyCollection;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionMethod;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionProperty;
use Phpactor\WorseReflection\Core\Visibility;
use function Phpactor\WorseReflection\Core\DocBlock\DocBlockVar;

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
    )
    {
        $this->raw = $raw;
        $this->node = $node;
    }

    public function parameterTypes(string $paramName): Types
    {
        $types = [];
        foreach ($this->node->tags(ParamTag::class) as $child) {
            if (!$child->type) {
                continue;
            }
            $types[] = Type::fromString($child->type->toString());
        }
        return Types::fromTypes($types);
    }

    public function propertyTypes(string $methodName): Types
    {
        $types = [];
        foreach ($this->node->tags(PropertyTag::class) as $child) {
            if (!$child->type) {
                continue;
            }
            $types[] = Type::fromString($child->type->toString());
        }
        return Types::fromTypes($types);
    }

    public function vars(): DocBlockVars
    {
        $types = [];
        foreach ($this->node->tags(VarTag::class) as $child) {
            assert($child instanceof VarTag);
            $variable = $child->variable;
            if (!$variable) {
                continue;
            }
            $types[] = new DocBlockVar(
                $variable->toString(),
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
        $types = [];
        foreach ($this->node->tags(MethodTag::class) as $child) {
            if (!$child->type) {
                continue;
            }
            $types[] = Type::fromString($child->type->toString());
        }
        return Types::fromTypes($types);
    }

    public function formatted(): string
    {
        return $this->node->prose();
    }

    public function returnTypes(): Types
    {
        $types = [];
        foreach ($this->node->tags(ReturnTag::class) as $child) {
            if (!$child->type) {
                continue;
            }
            $types[] = Type::fromString($child->type->toString());
        }
        return Types::fromTypes($types);
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
            $properties[] = new VirtualReflectionProperty(
                Position::fromStartAndEnd($property->start(), $property->start()),
                $declaringClass,
                $declaringClass,
                $property->name->toString(),
                new Frame(''),
                null,
                $declaringClass->scope(),
                Visibility::public(),
                Types::fromTypes([Type::fromString($property->type->toString())]),
                Type::fromString($property->type->toString()),
                new Deprecation($property->hasChild(DeprecationTag::class))
            );
        }

        return VirtualReflectionPropertyCollection::fromMembers([]);
    }

    public function methods(ReflectionClassLike $declaringClass): ReflectionMethodCollection
    {
        $methods = [];
        foreach ($this->node->tags(MethodTag::class) as $method) {
            assert($method instanceof MethodTag);
            $methods[] = new VirtualReflectionMethod(
                Position::fromStartAndEnd($method->start(), $method->start()),
                $declaringClass,
                $declaringClass,
                $method->name->toString(),
                new Frame(''),
                $this,
                $declaringClass->scope(),
                Visibility::public(),
                Types::fromTypes([Type::fromString($method->type->toString())]),
                Type::fromString($method->type->toString()),
                new Deprecation($this->node->hasTag(DeprecationTag::class))
            );
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

    private function typesFrom(TypeNode $type): Types
    {
        $nullable = false;
        if ($type instanceof NullableNode) {
            $nullable = true;
            $type = $type->type;
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
}

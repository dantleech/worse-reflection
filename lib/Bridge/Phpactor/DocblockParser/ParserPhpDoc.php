<?php

namespace Phpactor\WorseReflection\Bridge\Phpactor\DocblockParser;

use Phpactor\DocblockParser\Ast\Docblock;
use Phpactor\DocblockParser\Ast\Node;
use Phpactor\DocblockParser\Ast\Tag\ExtendsTag;
use Phpactor\DocblockParser\Ast\Tag\ReturnTag;
use Phpactor\DocblockParser\Ast\Tag\TemplateTag;
use Phpactor\DocblockParser\Ast\Type\ArrayNode;
use Phpactor\DocblockParser\Ast\Type\ClassNode;
use Phpactor\DocblockParser\Ast\Type\GenericNode;
use Phpactor\DocblockParser\Ast\Type\ScalarNode;
use Phpactor\DocblockParser\Ast\Type\UnionNode;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionScope;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\PhpDoc\ExtendsTemplate;
use Phpactor\WorseReflection\Core\PhpDoc\PhpDoc;
use Phpactor\WorseReflection\Core\PhpDoc\Template;
use Phpactor\WorseReflection\Core\PhpDoc\Templates;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionNode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionType;
use Phpactor\WorseReflection\Core\Type\ArrayType;
use Phpactor\WorseReflection\Core\Type\ClassType;
use Phpactor\WorseReflection\Core\Type\FloatType;
use Phpactor\WorseReflection\Core\Type\GenericType;
use Phpactor\WorseReflection\Core\Type\IntegerType;
use Phpactor\WorseReflection\Core\Type\MixedType;
use Phpactor\WorseReflection\Core\Type\StringType;
use Phpactor\WorseReflection\Core\Type\UndefinedType;
use Phpactor\WorseReflection\Core\Type\UnionType;
use RuntimeException;

final class ParserPhpDoc implements PhpDoc
{
    /**
     * @var ReflectionScope
     */
    private $scope;

    /**
     * @var Docblock
     */
    private $docblock;

    public function __construct(ReflectionScope $scope, Docblock $docblock)
    {
        $this->scope = $scope;
        $this->docblock = $docblock;
    }

    public function returnType(): ReflectionType
    {
        foreach ($this->docblock->tags(ReturnTag::class) as $tag) {
            return $this->resolveType($tag->type());
        }

        return new UndefinedType();
    }

    public function templates(): Templates
    {
        $templates = [];
        foreach ($this->docblock->tags(TemplateTag::class) as $templateTag) {
            assert($templateTag instanceof TemplateTag);
            $constraint = $templateTag->type ? $this->resolveType($templateTag->type) : null;
            $templates[$templateTag->placeholder->value] = new Template(
                $templateTag->placeholder->value,
                $constraint
            );
        }

        return new Templates($templates);
    }

    public function extends(): ?ExtendsTemplate
    {
        foreach ($this->docblock->tags(ExtendsTag::class) as $extendsTag) {
            assert($extendsTag instanceof ExtendsTag);
            $type = $extendsTag->type ? $this->resolveType($extendsTag->type) : null;
            return new ExtendsTemplate($type);
        }

        return null;
    }

    private function resolveType(Node $node): ReflectionType
    {
        if ($node instanceof ScalarNode) {
            if ($node->name()->value === 'string') {
                return new StringType();
            }
            if ($node->name()->value === 'int') {
                return new IntegerType();
            }
            if ($node->name()->value === 'float') {
                return new FloatType();
            }
            if ($node->name()->value === 'mixed') {
                return new MixedType();
            }
        }

        if ($node instanceof ArrayNode) {
            return new ArrayType();
        }

        if ($node instanceof GenericNode) {
            return $this->resolveGenericType($node);
        }

        if ($node instanceof UnionNode) {
            return $this->resolveUnionType($node);
        }

        if ($node instanceof ClassNode) {
            return $this->resolveClassNode($node);
        }

        throw new RuntimeException(sprintf(
            'Could not evaluate node of type "%s"',
            get_class($node)
        ));
    }

    private function resolveGenericType(GenericNode $node): ReflectionType
    {
        $subject = $node->type();
        if ($subject instanceof ArrayNode) {
            return new ArrayType(...array_map(function (Node $node) {
                return $this->resolveType($node);
            }, iterator_to_array($node->parameters()->types(), true)));
        }

        if ($subject instanceof ClassNode) {
            return new GenericType($this->resolveType($subject), array_map(function (Node $node) {
                return $this->resolveType($node);
            }, iterator_to_array($node->parameters()->types(), true)));
        }

        throw new RuntimeException(sprintf(
            'Could not resolve generic node "%s"',
            get_class($node)
        ));
    }

    private function resolveUnionType(UnionNode $node): ReflectionType
    {
        return new UnionType(array_values(array_map(function (Node $node) {
            return $this->resolveType($node);
        }, iterator_to_array($node->types->types(), true))));
    }

    private function resolveClassNode(ClassNode $node): ReflectionType
    {
        $name = ClassName::fromString($node->name()->value);
        if ($name->wasFullyQualified()) {
            return new ClassType($name);
        }
        return new ClassType($this->scope->resolveFullyQualifiedName($name)->className());
    }
}

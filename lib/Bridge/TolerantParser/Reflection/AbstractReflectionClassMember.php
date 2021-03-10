<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection;

use Phpactor\DocblockParser\Ast\Docblock;
use Phpactor\DocblockParser\Ast\Tag\ReturnTag;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Deprecation;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock as CoreDocBlock;
use Phpactor\WorseReflection\Core\PhpDoc\PhpDoc;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMember;
use Phpactor\WorseReflection\Core\Reflection\ReflectionType;
use Phpactor\WorseReflection\Core\ServiceLocator;
use Microsoft\PhpParser\ClassLike;

use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Microsoft\PhpParser\NamespacedNameInterface;
use Microsoft\PhpParser\TokenKind;
use Phpactor\WorseReflection\Core\Type\UndefinedType;
use Phpactor\WorseReflection\Core\Util\OriginalMethodResolver;
use Phpactor\WorseReflection\Core\Visibility;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\PropertyDeclaration;
use Microsoft\PhpParser\Node\ClassConstDeclaration;
use InvalidArgumentException;

abstract class AbstractReflectionClassMember extends AbstractReflectedNode
{
    public function declaringClass(): ReflectionClassLike
    {
        $classDeclaration = $this->node()->getFirstAncestor(ClassLike::class);

        assert($classDeclaration instanceof NamespacedNameInterface);

        $class = $classDeclaration->getNamespacedName();

        if (null === $class) {
            throw new InvalidArgumentException(sprintf(
                'Could not locate class-like ancestor node for member "%s"',
                $this->name()
            ));
        }

        return $this->serviceLocator()->reflector()->reflectClassLike(ClassName::fromString($class));
    }

    public function reflectionType(): ReflectionType
    {
        return new UndefinedType();
    }

    public function original(): ReflectionMember
    {
        return (new OriginalMethodResolver())->resolveOriginalMember($this);
    }

    public function frame(): Frame
    {
        return $this->serviceLocator()->frameBuilder()->build($this->node());
    }

    /**
     * @deprecated Use phpdoc() instead
     */
    public function docblock(): CoreDocBlock
    {
        return $this->serviceLocator()->docblockFactory()->create($this->node()->getLeadingCommentAndWhitespaceText());
    }

    public function phpdoc(): PhpDoc
    {
        return $this->serviceLocator()->phpdocFactory()->create($this->scope(), $this->node()->getLeadingCommentAndWhitespaceText());
    }

    public function visibility(): Visibility
    {
        $node = $this->node();
        assert($node instanceof PropertyDeclaration || $node instanceof ClassConstDeclaration || $node instanceof MethodDeclaration);
        foreach ($node->modifiers as $token) {
            if ($token->kind === TokenKind::PrivateKeyword) {
                return Visibility::private();
            }

            if ($token->kind === TokenKind::ProtectedKeyword) {
                return Visibility::protected();
            }
        }

        return Visibility::public();
    }

    public function deprecation(): Deprecation
    {
        return $this->docblock()->deprecation();
    }

    abstract protected function serviceLocator(): ServiceLocator;

    abstract protected function name(): string;
}

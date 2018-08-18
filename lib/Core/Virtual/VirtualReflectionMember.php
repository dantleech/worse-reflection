<?php

namespace Phpactor\WorseReflection\Core\Virtual;

use Phpactor\WorseReflection\Core\DocBlock\DocBlock;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Position;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMember;
use Phpactor\WorseReflection\Core\Reflection\ReflectionScope;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\Visibility;

abstract class VirtualReflectionMember implements ReflectionMember
{
    /**
     * @var Position
     */
    private $position;

    /**
     * @var ReflectionClassLike
     */
    private $declaringClass;

    /**
     * @var ReflectionClassLike
     */
    private $class;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Frame
     */
    private $frame;

    /**
     * @var DocBlock
     */
    private $docblock;

    /**
     * @var ReflectionScope
     */
    private $scope;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Types
     */
    private $inferredTypes;

    /**
     * @var Type
     */
    private $type;

    public function __construct(
        Position $position,
        ReflectionClassLike $declaringClass,
        ReflectionClassLike $class,
        string $name,
        Frame $frame,
        DocBlock $docblock,
        ReflectionScope $scope,
        Visibility $visiblity,
        Types $inferredTypes,
        Type $type
    ) {
        $this->position = $position;
        $this->declaringClass = $declaringClass;
        $this->class = $class;
        $this->name = $name;
        $this->frame = $frame;
        $this->docblock = $docblock;
        $this->scope = $scope;
        $this->visibility = $visiblity;
        $this->inferredTypes = $inferredTypes;
        $this->type = $type;
    }

    public function position(): Position
    {
        return $this->position;
    }

    public function declaringClass(): ReflectionClassLike
    {
        return $this->declaringClass;
    }

    public function class(): ReflectionClassLike
    {
        return $this->class;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function frame(): Frame
    {
        return $this->frame;
    }

    public function docblock(): DocBlock
    {
        return $this->docblock;
    }

    public function scope(): ReflectionScope
    {
        return $this->scope;
    }

    public function visibility(): Visibility
    {
        return $this->visibility;
    }

    public function inferredTypes(): Types
    {
        return $this->inferredTypes;
    }

    public function type(): Type
    {
        return $this->type;
    }
}

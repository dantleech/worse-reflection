<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection;

use Phpactor\WorseReflection\Core\Position;
use Microsoft\PhpParser\Node;
use Phpactor\WorseReflection\Core\Reflection\ReflectionNode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionScope as CoreReflectionScope;

abstract class AbstractReflectedNode implements ReflectionNode
{
    public function position(): Position
    {
        return Position::fromFullStartStartAndEnd(
            $this->node()->getFullStart(),
            $this->node()->getStart(),
            $this->node()->getEndPosition()
        );
    }

    public function scope(): CoreReflectionScope
    {
        return new ReflectionScope($this->node());
    }
    abstract protected function node(): Node;
}

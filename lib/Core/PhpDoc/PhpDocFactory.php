<?php

namespace Phpactor\WorseReflection\Core\PhpDoc;

use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionNode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionScope;

interface PhpDocFactory
{
    public function create(ReflectionScope $scope, string $docblock): PhpDoc;
}

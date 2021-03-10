<?php

namespace Phpactor\WorseReflection\Core\PhpDoc;

use Phpactor\WorseReflection\Core\Reflection\ReflectionType;

interface PhpDoc
{
    public function returnType(): ReflectionType;

    public function templates(): Templates;

    public function extends(): ?ExtendsTemplate;
}

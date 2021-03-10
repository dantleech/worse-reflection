<?php

namespace Phpactor\WorseReflection\Bridge\Phpactor;

use Phpactor\WorseReflection\Core\DocBlock\DocBlockFactory as CoreDocblockPhpactory;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock as CoreDocblock;
use Phpactor\Docblock\DocblockFactory as PhpactorDocblockFactory;

class DocblockFactory implements CoreDocblockPhpactory
{
    /**
     * @var PhpactorDocblockFactory
     */
    private $factory;

    public function __construct(PhpactorDocblockFactory $factory = null)
    {
        $this->factory = $factory ?: new PhpactorDocblockFactory();
    }

    public function create(string $docblock): Docblock
    {
        return new Docblock($docblock, $this->factory->create($docblock));
    }
}

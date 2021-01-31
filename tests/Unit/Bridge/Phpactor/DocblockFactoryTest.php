<?php

namespace Phpactor\WorseReflection\Tests\Unit\Bridge\Phpactor;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Bridge\Phpactor\DocblockFactory as BridgeFactory;
use Phpactor\WorseReflection\Core\Cache\NullCache;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock;

class DocblockFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new BridgeFactory(new NullCache());
        $docblock = $factory->create('/** @var asd */');
        $this->assertInstanceOf(DocBlock::class, $docblock);
    }
}

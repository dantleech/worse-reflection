<?php

namespace Phpactor\WorseReflection\Tests\Unit\Bridge\Phpactor;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Bridge\Phpactor\DocblockFactory;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock;
use Phpactor\WorseReflection\Core\Name;

class DocblockTest extends TestCase
{
    public function testIsDefined(): void
    {
        $docblock = $this->create('');
        $this->assertFalse($docblock->isDefined());

        $docblock = $this->create('    ');
        $this->assertFalse($docblock->isDefined());

        $docblock = $this->create('/** Hello */');
        $this->assertTrue($docblock->isDefined());
    }

    public function testRaw(): void
    {
        $docblock = $this->create('asd');
        $this->assertEquals('asd', $docblock->raw());
    }

    public function testReturnTypes(): void
    {
        $docblock = $this->create('/** @return Foo */');
        $this->assertEquals([ 'Foo' ], iterator_to_array($docblock->returnTypes()));
    }

    public function testMethodTypes(): void
    {
        $docblock = $this->create('/** @method Foo bar() */');
        $this->assertEquals([ 'Foo' ], iterator_to_array($docblock->methodTypes('bar')));
    }

    public function testVarTypes(): void
    {
        $docblock = $this->create('/** @var Foo $foo) */');
        $this->assertEquals('Foo', $docblock->vars()->types()->best()->className()->full());
        $this->assertFalse($docblock->vars()->types()->best()->arrayType()->isDefined());
    }

    public function testArrayTypes(): void
    {
        $docblock = $this->create('/** @var Foo[] $foo) */');
        $this->assertTrue($docblock->vars()->types()->best()->arrayType()->isDefined());
        $this->assertEquals('Foo', $docblock->vars()->types()->best()->arrayType()->className()->full());
    }

    public function testCollectionTypes(): void
    {
        $docblock = $this->create('/** @var Foo<Item> $foo) */');
        $this->assertTrue($docblock->vars()->types()->best()->arrayType()->isDefined());
        $this->assertEquals('Foo', $docblock->vars()->types()->best()->short());
        $this->assertEquals('Item', $docblock->vars()->types()->best()->arrayType()->className()->full());
    }

    public function testInherits(): void
    {
        $docblock = $this->create('/** Hello */');
        $this->assertFalse($docblock->inherits());
        $docblock = $this->create('/** {@inheritDoc} */');
        $this->assertTrue($docblock->inherits());
    }

    public function testMixins(): void
    {
        $docblock = $this->create('/** @mixin Foobar */');
        $mixins = $docblock->mixins();
        self::assertCount(1, $mixins);
        $mixin = reset($mixins);
        self::assertInstanceOf(Name::class, $mixin);
    }

    public function testDeprecated(): void
    {
        $docblock = $this->create('/** Hello */');
        $this->assertFalse($docblock->inherits());
        $docblock = $this->create('/** @deprecated */');
        $this->assertTrue($docblock->deprecation()->isDefined());
    }

    public function testDeprecatedMessage(): void
    {
        $docblock = $this->create('/** @deprecated Use foobar instead */');
        $this->assertEquals('Use foobar instead', $docblock->deprecation()->message());
    }

    private function create($docblock): DocBlock
    {
        $factory = new DocblockFactory();
        return $factory->create($docblock);
    }
}

<?php

namespace Phpactor\WorseReflection\Tests\Unit\Bridge\Phpactor;

use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Bridge\Phpactor\DocblockFactory;
use Phpactor\WorseReflection\Core\Cache\NullCache;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionProperty;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\ReflectorBuilder;

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
        $this->markTestSkipped('Not supported');
        return;
        $docblock = $this->create('/** Hello */');
        $this->assertFalse($docblock->inherits());
        $docblock = $this->create('/** {@inheritDoc} */');
        $this->assertTrue($docblock->inherits());
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

    /**
     * @dataProvider provideMethodTags
     */
    public function testMethodTags(string $docblock, Closure $assertion): void
    {
        $docblock = '/** ' . $docblock . ' */';
        $class = ReflectorBuilder::create()->addSource(
            '<?php class Foobar {}'
        )->build()->reflectClass('Foobar');
        $methods = $this->create($docblock)->methods($class)->first();
        $assertion($methods);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideMethodTags(): Generator
    {
        yield 'minimal' => [
            '@method string myMethod',
            function (ReflectionMethod $method): void {
                $this->assertEquals('Foobar', (string) $method->class()->name());
                $this->assertEquals('myMethod', $method->name());
            }
        ];

        yield 'static method' => [
            '@method static Foobar myMethod',
            function (ReflectionMethod $method): void {
                $this->assertEquals('Foobar', (string) $method->class()->name());
                $this->assertEquals('myMethod', $method->name());
                $this->assertTrue($method->isStatic());
            }
        ];

        yield 'multiple types' => [
            '@method Foobar|string myMethod',
            function (ReflectionMethod $method): void {
                $this->assertEquals('Foobar', (string) $method->class()->name());
                $this->assertEquals('myMethod', $method->name());
                $this->assertEquals(Types::fromTypes([
                    Type::fromString('Foobar'),
                    Type::string(),
                ]), $method->inferredTypes());
            }
        ];

        yield 'parameters' => [
            '@method Foobar barfoo($one, $two)',
            function (ReflectionMethod $method): void {
                $this->assertEquals(2, $method->parameters()->count());
                $this->assertEquals('one', $method->parameters()->first()->name());
                $this->assertEquals('two', $method->parameters()->get('two')->name());
            }
        ];

        yield 'parameters with type and default value' => [
            '@method Foobar barfoo(string|int $one = 1234)',
            function (ReflectionMethod $method): void {
                $this->assertEquals(1, $method->parameters()->count());
                $parameter = $method->parameters()->first();
                $this->assertEquals('one', $parameter->name());
                $this->assertCount(2, $parameter->inferredTypes());
                $this->assertEquals('string', $parameter->inferredTypes()->best());
            }
        ];
    }

    /**
     * @dataProvider providePropertyTags
     */
    public function testPropertyTags(string $docblock, Closure $assertion): void
    {
        $docblock = '/** ' . $docblock . ' */';
        $class = ReflectorBuilder::create()->addSource(
            '<?php class Foobar {}'
        )->build()->reflectClass('Foobar');
        $property = $this->create($docblock)->properties($class)->first();
        $assertion($property);
    }

    /**
     * @return Generator<mixed>
     */
    public function providePropertyTags(): Generator
    {
        yield 'minimal' => [
            '@property string $myProperty',
            function (ReflectionProperty $property): void {
                $this->assertEquals('Foobar', (string) $property->class()->name());
                $this->assertEquals('myProperty', $property->name());
            }
        ];

        yield 'multiple types' => [
            '@property Foobar|string $myProperty',
            function (ReflectionProperty $property): void {
                $this->assertEquals('Foobar', (string) $property->class()->name());
                $this->assertEquals('myProperty', $property->name());
                $this->assertEquals(Types::fromTypes([
                    Type::fromString('Foobar'),
                    Type::string(),
                ]), $property->inferredTypes());
            }
        ];
    }

    private function create($docblock): DocBlock
    {
        $factory = new DocblockFactory(new NullCache());
        return $factory->create($docblock);
    }
}

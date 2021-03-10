<?php

namespace Phpactor\WorseReflection\Tests\Unit\Core\PhpDoc;

use Closure;
use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\PhpDoc\GenericTypeResolver;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionType;
use Phpactor\WorseReflection\Core\Type\IntegerType;
use Phpactor\WorseReflection\Core\Type\StringType;
use Phpactor\WorseReflection\Core\Type\UndefinedType;
use Phpactor\WorseReflection\Tests\Integration\IntegrationTestCase;
use phpDocumentor\Reflection\TypeResolver;
use function ini_get;

class GenericTypeResolverTest extends IntegrationTestCase
{
    /**
     * @dataProvider provideResolve
     */
    public function testResolve(string $source, string $class, Closure $assertion): void
    {
        $reflector = $this->createReflector($source);
        $reflectionClass = $reflector->reflectClass($class);
        $resolver = new GenericTypeResolver($reflector);
        $assertion($reflectionClass, $resolver);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideResolve(): Generator
    {
        yield [
            <<<'EOT'
<?php 

namespace Bar;

/**
 * @template T
 */
class Foo {
    /**
     * @return T
     */
    public function bar() {}
}

/**
 * @extends Foo<string>
 */
class Bar extends Foo
{
}
EOT
        , 'Bar\Bar',
        function (ReflectionClass $class, GenericTypeResolver $resolver) {
            self::assertEquals(
                new StringType(),
                $resolver->resolve($class->methods()->get('bar'))
            );
        }
        ];

        yield [
            <<<'EOT'
<?php 

namespace Bar;

/**
 * @template TKey
 * @template TValue
 */
class Foo {
    /**
     * @return TKey
     */
    public function bar() {}

    /**
     * @return TValue
     */
    public function baz() {}
}

/**
 * @extends Foo<string, int>
 */
class Bar extends Foo
{
}
EOT
        , 'Bar\Bar',
        function (ReflectionClass $class, GenericTypeResolver $resolver) {
            self::assertEquals(
                new StringType(),
                $resolver->resolve($class->methods()->get('bar'))
            );
            self::assertEquals(
                new IntegerType(),
                $resolver->resolve($class->methods()->get('baz'))
            );
        }
        ];

        yield [
            <<<'EOT'
<?php 

namespace Bar;

/**
 * @template TKey
 * @template TValue
 */
class Foo {
    /**
     * @param TKey $a
     * @param TValue $b
     */
    public function __construct($a, $b)
    {
    }
    /**
     * @return TKey
     */
    public function bar() {}

    /**
     * @return TValue
     */
    public function baz() {}
}
EOT
        , 'Bar\Foo',
        function (ReflectionClass $class, GenericTypeResolver $resolver) {
            self::assertEquals(
                new UndefinedType(),
                $resolver->resolve($class->methods()->get('bar'))
            );
        }
        ];
    }
}

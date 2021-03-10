<?php

namespace Phpactor\WorseReflection\Tests\Unit\Bridge\Phpactor\DocblockParser;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\ClassFileConverter\Domain\ClassName as PhpactorClassName;
use Phpactor\DocblockParser\Ast\Tag\ReturnTag;
use Phpactor\DocblockParser\Ast\Type\GenericNode;
use Phpactor\WorseReflection\Bridge\Phpactor\DocblockParser\ParserPhpDocFactory;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\PhpDoc\DocBlockTypeResolver;
use Phpactor\WorseReflection\Core\PhpDoc\ExtendsTemplate;
use Phpactor\WorseReflection\Core\PhpDoc\PhpDoc as PhpactorPhpDoc;
use Phpactor\WorseReflection\Core\PhpDoc\PhpDocFactory;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\PhpDoc\Template;
use Phpactor\WorseReflection\Core\PhpDoc\Templates;
use Phpactor\WorseReflection\Core\Reflection\ReflectionType;
use Phpactor\WorseReflection\Core\Type\ArrayType;
use Phpactor\WorseReflection\Core\Type\ClassType;
use Phpactor\WorseReflection\Core\Type\FloatType;
use Phpactor\WorseReflection\Core\Type\GenericType;
use Phpactor\WorseReflection\Core\Type\IntegerType;
use Phpactor\WorseReflection\Core\Type\MixedType;
use Phpactor\WorseReflection\Core\Type\StringType;
use Phpactor\WorseReflection\Core\Type\TemplatedType;
use Phpactor\WorseReflection\Core\Type\UnionType;
use Phpactor\WorseReflection\Tests\Integration\IntegrationTestCase;

class ParserPhpDocTest extends IntegrationTestCase
{
    /**
     * @dataProvider provideResolveType
     */
    public function testResolveType(string $docblock, ReflectionType $expected): void
    {
        $docblock = $this->parseDocblock($docblock);

        self::assertEquals($expected, $docblock->returnType());
    }

    /**
     * @return Generator<mixed>
     */
    public function provideResolveType(): Generator
    {
        yield [
            '/** @return string */',
            new StringType()
        ];

        yield [
            '/** @return int */',
            new IntegerType()
        ];

        yield [
            '/** @return float */',
            new FloatType()
        ];

        yield [
            '/** @return mixed */',
            new MixedType()
        ];

        yield [
            '/** @return array */',
            new ArrayType()
        ];

        yield [
            '/** @return array|string */',
            new UnionType([new ArrayType(), new StringType()])
        ];

        yield [
            '/** @return array<string> */',
            new ArrayType(new StringType())
        ];

        yield [
            '/** @return array<int, string> */',
            new ArrayType(new IntegerType(), new StringType())
        ];

        yield [
            '/** @return T */',
            new ClassType(ClassName::fromString('Bar\T'))
        ];

        yield [
            '/** @return \IteratorAggregate<Foobar> */',
            new GenericType(new ClassType(
                ClassName::fromString('\IteratorAggregate')
            ), [new ClassType(
                ClassName::fromString('Bar\Foobar')
            )])
        ];
    }

    public function testTemplates(): void
    {
        $docblock = $this->parseDocblock('/** @template T @template Y */');
        self::assertEquals(new Templates([
            'T' => new Template('T'),
            'Y' => new Template('Y'),
        ]), $docblock->templates());
    }

    public function testExtends(): void
    {
        $docblock = $this->parseDocblock('/** @extends Foobar<\Barfoo> */');
        self::assertEquals(new ExtendsTemplate(
            new GenericType(
                new ClassType(ClassName::fromString('Bar\Foobar')),
                [
                    new ClassType(ClassName::fromString('\Barfoo'))
                ]
            ),
        ), $docblock->extends());
    }

    private function parseDocblock(string $docblock): PhpactorPhpDoc
    {
        $reflector = $this->createReflector('<?php namespace Bar; class Foobar{}');
        $scope = $reflector->reflectClass('Bar\Foobar')->scope();
        
        return (new ParserPhpDocFactory())->create($scope, $docblock);
    }

}

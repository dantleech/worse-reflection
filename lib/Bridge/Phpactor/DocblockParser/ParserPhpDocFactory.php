<?php

namespace Phpactor\WorseReflection\Bridge\Phpactor\DocblockParser;

use Phpactor\DocblockParser\Ast\Docblock;
use Phpactor\DocblockParser\Lexer;
use Phpactor\DocblockParser\Parser;
use Phpactor\WorseReflection\Core\PhpDoc\DocBlockTypeResolver;
use Phpactor\WorseReflection\Core\PhpDoc\PhpDoc;
use Phpactor\WorseReflection\Core\PhpDoc\PhpDocFactory;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionNode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionScope;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;
use RuntimeException;

class ParserPhpDocFactory implements PhpDocFactory
{
    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(?Lexer $lexer = null, ?Parser $parser = null)
    {
        $this->lexer = $lexer ?: new Lexer();
        $this->parser = $parser ?: new Parser();
    }

    public function create(ReflectionScope $scope, string $docblock): PhpDoc
    {
        return new ParserPhpDoc($scope, $this->parseDocblock($docblock));
    }

    private function parseDocblock(string $docblock): Docblock
    {
        $node = $this->parser->parse($this->lexer->lex($docblock));
        if (!$node instanceof Docblock) {
            throw new RuntimeException('Nope');
        }
        return $node;
    }
}

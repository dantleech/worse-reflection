<?php

namespace Phpactor\WorseReflection\Bridge\Phpactor;

use Phpactor\WorseReflection\Core\DocBlock\DocBlock as CoreDocblock;
use Phpactor\Docblock\Docblock as PhpactorDocblock;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\DocBlock\DocBlockVars;
use Phpactor\WorseReflection\Core\DocBlock\DocBlockVar;
use Phpactor\Docblock\Tag\DocblockTypes;
use Phpactor\Docblock\DocblockType;
use Phpactor\WorseReflection\Core\Types;

class Docblock implements CoreDocblock
{
    /**
     * @var PhpactorDocblock
     */
    private $docblock;

    /**
     * @var string
     */
    private $raw;

    public function __construct(string $raw, PhpactorDocblock $docblock)
    {
        $this->docblock = $docblock;
        $this->raw = $raw;
    }

    public function isDefined(): bool
    {
        return trim($this->raw) != '';
    }

    public function raw(): string
    {
        return $this->raw;
    }

    public function formatted(): string
    {
        return $this->docblock->prose();
    }

    public function returnTypes(): array
    {
        return $this->typesFromTag('return');
    }

    public function methodTypes(string $methodName): array
    {
        $types = [];

        foreach ($this->docblock->tags()->byName('method') as $tag) {
            if ($tag->methodName() !== $methodName) {
                continue;
            }

            foreach ($tag->types() as $type) {
                $types[] = Type::fromString((string) $type);
            }
        }

        return $types;
    }

    public function vars(): DocBlockVars
    {
        $vars = [];
        foreach ($this->docblock->tags()->byName('var') as $tag) {
            $vars[] = new DocBlockVar($tag->varName() ?: '', $this->typesFromDocblockTypes($tag->types()));
        }

        return new DocBlockVars($vars);
    }

    public function inherits(): bool
    {
        return 0 !== $this->docblock->tags()->byName('inheritDoc')->count();
    }

    private function typesFromTag(string $tag)
    {
        $types = [];

        foreach ($this->docblock->tags()->byName($tag) as $tag) {

            foreach ($tag->types() as $type) {
                $types[] = Type::fromString((string) $type);
            }
        }

        return $types;
    }

    private function typesFromDocblockTypes(DocblockTypes $types)
    {
        $types = array_map(function (DocblockType $type) {
            if ($type->isArray()) {
                return Type::array($type->__toString());
            }

            return Type::fromString($type->__toString());
        }, iterator_to_array($types));

        return Types::fromTypes($types);
    }
}

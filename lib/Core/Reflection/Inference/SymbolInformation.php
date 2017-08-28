<?php

namespace Phpactor\WorseReflection\Core\Reflection\Inference;

use Phpactor\WorseReflection\Core\Type;

final class SymbolInformation
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var Symbol
     */
    private $symbol;

    /**
     * @var Type
     */
    private $classType;

    private function __construct(Symbol $symbol, Type $type, $value = null, Type $classType = null)
    {
        $this->type = $type;
        $this->value = $value;
        $this->symbol = $symbol;
        $this->classType = $classType;
    }

    public static function for(Symbol $symbol): SymbolInformation
    {
        return new self($symbol, Type::unknown());
    }

    /**
     * @deprecated
     */
    public static function fromTypeAndValue(Type $type, $value): SymbolInformation
    {
        return new self(Symbol::unknown(), $type, $value);
    }

    /**
     * @deprecated
     */
    public static function fromType(Type $type)
    {
        return new self(Symbol::unknown(), $type);
    }

    public static function none()
    {
        return new self(Symbol::unknown(), Type::undefined());
    }

    public function withValue($value): SymbolInformation
    {
        return new self($this->symbol, $this->type, $value, $this->classType);
    }

    public function withClassType($classType): SymbolInformation
    {
        return new self($this->symbol, $this->type, $this->value, $classType);
    }

    public function withType(Type $type): SymbolInformation
    {
        return new self($this->symbol, $type, $this->value, $this->classType);
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function value()
    {
        return $this->value;
    }

    public function symbol(): Symbol
    {
        return $this->symbol;
    }

    /**
     * @return Type
     */
    public function classType()
    {
        return $this->classType;
    }
}

<?php

namespace Phpactor\WorseReflection\Tests\Benchmarks;

use Phpactor\WorseReflection\Bridge\Composer\ComposerSourceLocator;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\Core\SourceCodeLocator\ChainSourceLocator;
use Phpactor\WorseReflection\ReflectorFactory;

abstract class BaseBenchCase
{
    public function getReflector(): Reflector
    {
        $composerLocator = new ComposerSourceLocator(include(__DIR__ . '/../../vendor/autoload.php'));

        $stubLocator = new StubSourceLocator(
            ReflectorFactory::create(),
            __DIR__ . '/../../vendor/jetbrains/phpstorm-stubs',
            __DIR__ . '/../Workspace/cache'
        );

        $chainLocator = new ChainSourceLocator([
            $composerLocator, $stubLocator
        ]);

        return ReflectorFactory::create($chainLocator);
    }
}

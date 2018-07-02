<?php

namespace Phpactor\WorseReflection\Tests\Integration\Core\SourceCodeLocator;

use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\Tests\Integration\IntegrationTestCase;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\ReflectorBuilder;

class StubSourceLocatorTest extends IntegrationTestCase
{
    /**
     * @var StubSourceLocator
     */
    private $sourceLocator;

    public function setUp()
    {
        $this->workspace()->reset();

        $locator = new StringSourceLocator(SourceCode::fromString(''));
        $reflector = ReflectorBuilder::create()->addLocator($locator)->build();
        $this->workspace()->mkdir('stubs')->mkdir('cache');

        $this->sourceLocator = new StubSourceLocator(
            $reflector,
            $this->workspace()->path('stubs'),
            $this->workspace()->path('cache')
        );
    }

    public function testCanLocateClasses()
    {
        $this->workspace()->put('stubs/Stub.php', '<?php class StubOne {}');
        $code = $this->sourceLocator->locate(ClassName::fromString('StubOne'));
        $this->assertContains('class StubOne', (string) $code);
    }

    public function testCanLocateFunctions()
    {
        $this->workspace()->put('stubs/Stub.php', '<?php function hello_world() {}');
        $code = $this->sourceLocator->locate(Name::fromString('hello_world'));
        $this->assertContains('function hello_world()', (string) $code);
    }

    public function testDoesNotParseNonPhpFiles()
    {
        $this->workspace()->put('stubs/Stub.xml', '<?php function hello_world() {}');
        $this->workspace()->put('stubs/Stub.php', '<?php function goodbye_world() {}');

        try {
            $code = $this->sourceLocator->locate(Name::fromString('hello_world'));
            $this->fail('Non PHP file parsed');
        } catch (NotFound $notFound) {
            $this->addToAssertionCount(1);
            return;
        }

        $code = $this->sourceLocator->locate(Name::fromString('goodbye_world'));
        $this->assertContains('function goodbye_world()', (string) $code);
    }
}

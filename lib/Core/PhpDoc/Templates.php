<?php

namespace Phpactor\WorseReflection\Core\PhpDoc;

use RuntimeException;
use Phpactor\WorseReflection\Core\PhpDoc\Template;
use Phpactor\WorseReflection\Core\PhpDoc\Templates;
use function array_search;

final class Templates
{
    /**
     * @var Template[]
     */
    private $templates;

    /**
     * @param Template[]
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    public function merge(Templates $templates): self
    {
        return new self(array_merge($this->templates, $templates->templates));
    }

    public function has(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    public function get(string $name): Template
    {
        if (!$this->has($name)) {
            throw new RuntimeException(sprintf(
                'Unknown template "%s"',
                $name
            ));
        }

        return $this->templates[$name];
    }

    public function offset(Template $for)
    {
        $offset = 0;
        foreach ($this->templates as $template) {
            if ($template === $for) {
                return $offset;
            }
            $offset++;
        }

        throw new RuntimeException(sprintf(
            'Template "%s" not found',
            $for->name()
        ));
    }
}

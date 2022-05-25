<?php

namespace Sicet7\Container\Attributes;

use Sicet7\Container\Factories\DefaultDefinitionFactory;
use function DI\factory;
use function DI\get;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Definition
{
    /**
     * @var array
     */
    private array $aliases;

    /**
     * @var callable|array
     */
    private $factory;

    /**
     * @param array $aliases
     * @param callable|array $factory
     */
    public function __construct(
        array $aliases = [],
        callable|array $factory = [DefaultDefinitionFactory::class, 'create']
    ) {
        $this->aliases = $aliases;
        $this->factory = $factory;
    }

    /**
     * @param string $fqcn
     * @return array
     */
    public function getDefinitions(string $fqcn): array
    {
        $output = [
            $fqcn => factory($this->factory),
        ];
        foreach ($this->aliases as $alias) {
            $output[$alias] = get($fqcn);
        }
        return $output;
    }
}
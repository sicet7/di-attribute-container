<?php

namespace Sicet7\Container\Processors;

use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Container\ContainerInterface;
use Sicet7\Container\Attributes\Definition;
use Sicet7\Container\Factories\DefaultDefinitionFactory;
use Sicet7\Container\Interfaces\AttributeProcessorInterface;

final class DefinitionProcessor implements AttributeProcessorInterface
{
    public function __construct()
    {
    }

    /**
     * @param \ReflectionClass $class
     * @return array
     */
    public function getDefinitionsForClass(\ReflectionClass $class): array
    {
        $fqcn = $class->getName();
        $attributes = $class->getAttributes(Definition::class);
        if (empty($attributes)) {
            return [];
        }
        $output = [];
        foreach ($attributes as $attribute) {
            /** @var Definition $instance */
            $instance = $attribute->newInstance();
            $output = array_merge($output, $instance->getDefinitions($fqcn));
        }
        return $output;
    }

    /**
     * @return array
     */
    public function getInferredDefinitions(): array
    {
        return [
            DefaultDefinitionFactory::class => function (ContainerInterface $container) {
                return new DefaultDefinitionFactory(new ResolverChain([
                    0 => new AssociativeArrayResolver(),
                    1 => new TypeHintContainerResolver($container),
                    2 => new DefaultValueResolver(),
                ]));
            },
        ];
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public function containerSetup(ContainerInterface $container): void
    {
        // Do Nothing.
    }
}
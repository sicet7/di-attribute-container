<?php

namespace Sicet7\Container\Interfaces;

use Psr\Container\ContainerInterface;

interface AttributeProcessorInterface
{
    /**
     * A attribute processor's lifecycle should never extend beyond the scope of the build method of the builder.
     */
    public function __construct();

    /**
     * Should return an array containing the definitions for the class provided, if any.
     *
     * @param \ReflectionClass $class
     * @return array
     */
    public function getDefinitionsForClass(\ReflectionClass $class): array;

    /**
     * Should return an array containing definitions inferred by this processor, if any.
     * This method is only called once per processor, right before building the container.
     *
     * @return array
     */
    public function getInferredDefinitions(): array;

    /**
     * Should the processor need to modify the state on any of the objects stored in the container, it may do so here.
     * this method is only called once per processor, right after building the container but before returning it.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function containerSetup(ContainerInterface $container): void;
}
<?php

namespace Sicet7\Container\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class Definition
{
    /**
     * @param string $classFqcn
     * @param array $attributes
     * @return array
     */
    public function getDefinitions(string $classFqcn, array $attributes = []): array
    {

    }
}
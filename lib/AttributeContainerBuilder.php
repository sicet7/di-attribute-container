<?php

namespace Sicet7\Container;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Sicet7\Container\Exceptions\ContainerBuilderException;
use Sicet7\Container\Interfaces\AttributeProcessorInterface;
use Symfony\Component\Finder\Finder;

class AttributeContainerBuilder
{
    /**
     * @var string[][]
     */
    private static array $registrations = [];

    /**
     * @var string[]
     */
    private static array $processors = [];

    /**
     * @var array
     */
    private static array $classes = [];

    /**
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * @var bool
     */
    protected static bool $annotations = false;

    /**
     * @var bool
     */
    protected static bool $autowiring = false;

    /**
     * @param bool $value
     * @return void
     */
    public static function enableAnnotations(bool $value = true): void
    {
        self::$annotations = $value;
    }

    /**
     * @param bool $value
     * @return void
     */
    public static function enableAutowiring(bool $value = true): void
    {
        self::$autowiring = $value;
    }

    /**
     * @param string $processorFqcn
     * @return void
     */
    public static function registerProcessor(string $processorFqcn): void
    {
        if (!in_array($processorFqcn, self::$processors)) {
            self::$processors[] = $processorFqcn;
        }
    }

    /**
     * @param string $namespace
     * @param string $directory
     * @return void
     * @throws ContainerBuilderException
     */
    public static function register(string $namespace, string $directory): void
    {
        if (!file_exists($directory) || !is_dir($directory)) {
            throw new ContainerBuilderException('Failed to find directory: "' . $directory . '".');
        }
        if (!array_key_exists($namespace, self::$registrations)) {
            self::$registrations[$namespace] = [];
        }
        if (!in_array($directory, self::$registrations[$namespace])) {
            self::$registrations[$namespace][] = $directory;
        }
    }

    /**
     * @return void
     */
    protected static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        $classes = [];
        $trimChars = '\\/';
        foreach (self::$registrations as $namespace => $directories) {
            foreach ($directories as $directory) {
                foreach (Finder::create()
                             ->files()
                             ->in($directory)
                             ->name('*.php') as $file) {
                    if ($file->isLink()) {
                        continue;
                    }
                    $className = self::trim($file->getFilenameWithoutExtension(), $trimChars);
                    $fqn = str_replace(
                        '/',
                        '\\',
                        self::trim(substr($file->getPath(), strlen($directory)), $trimChars)
                    );
                    $fqcn = self::trim(
                        self::trim($namespace, $trimChars) . (!empty($fqn) ? '\\' . $fqn : '') . '\\' . $className,
                        $trimChars
                    );
                    if (class_exists($fqcn) && !in_array($fqcn, $classes)) {
                        $classes[] = $fqcn;
                    }
                }
            }
        }
        self::$classes = $classes;
        self::$loaded = true;
    }

    /**
     * @param array $additionalDefinitions
     * @return ContainerInterface
     * @throws \Exception
     */
    public static function build(array $additionalDefinitions = []): ContainerInterface
    {
        self::load();
        $builder = new ContainerBuilder();
        $builder->useAnnotations(self::$annotations);
        $builder->useAutowiring(self::$autowiring);

        /** @var AttributeProcessorInterface[] $processors */
        $processors = [];
        foreach (self::$processors as $processor) {
            if (!class_exists($processor)) {
                continue;
            }
            $processors[] = new $processor;
        }

        foreach (self::$classes as $class) {
            if (!class_exists($class)) {
                continue;
            }
            $reflection = new \ReflectionClass($class);
            if (empty($reflection->getAttributes())) {
                continue;
            }
            foreach ($processors as $processor) {
                $defs = $processor->getDefinitionsForClass($reflection);
                if (!empty($defs)) {
                    $builder->addDefinitions($defs);
                }
            }
        }
        unset($reflection, $defs);

        foreach ($processors as $processor) {
            $defs = $processor->getInferredDefinitions();
            if (!empty($defs)) {
                $builder->addDefinitions($defs);
            }
        }

        unset($defs);

        $builder->addDefinitions($additionalDefinitions);

        $container = $builder->build();

        foreach ($processors as $processor) {
            $processor->containerSetup($container);
        }
        return $container;
    }

    /**
     * @param string $input
     * @param string $additionalChars
     * @param bool|null $left
     * @return string
     */
    private static function trim(string $input, string $additionalChars = '', ?bool $left = null): string
    {
        $chars = " \t\n\r\0\x0B" . $additionalChars;
        if ($left === true) {
            return ltrim($input, $chars);
        } elseif ($left === false) {
            return rtrim($input, $chars);
        } else {
            return trim($input, $chars);
        }
    }
}
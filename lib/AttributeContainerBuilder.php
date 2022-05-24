<?php

namespace Sicet7\Container;

use Psr\Container\ContainerInterface;
use Sicet7\Container\Attributes\Definition;
use Sicet7\Container\Exceptions\ContainerBuilderException;
use Symfony\Component\Finder\Finder;

#[Definition]
class AttributeContainerBuilder
{
    /**
     * @var array
     */
    private static array $registrations = [];

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
     * @param bool $cache
     * @return void
     * @throws ContainerBuilderException
     */
    public static function load(bool $cache = true): void
    {
        if (self::$loaded) {
            return;
        }
        if ($cache && ($cachedData = self::loadFromCache()) !== null) {
            self::$classes = $cachedData;
            self::$loaded = true;
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
                    if (self::hasDefinition($fqcn) && !in_array($fqcn, $classes)) {
                        $classes[] = $fqcn;
                    }
                }
            }
        }
        self::$classes = $classes;
        self::$loaded = true;
        self::writeToCache(self::$classes);
    }

    /**
     * @param array $additionalDefinitions
     * @return ContainerInterface
     * @throws ContainerBuilderException
     */
    public static function build(array $additionalDefinitions = []): ContainerInterface
    {
        if (!self::$loaded) {
            self::load();
        }
        var_dump(self::$classes);
        die;
    }

    /**
     * @param string $fqcn
     * @return bool
     */
    private static function hasDefinition(string $fqcn): bool
    {
        if (!class_exists($fqcn)) {
            return false;
        }
        $reflection = new \ReflectionClass($fqcn);
        $def = self::trim(Definition::class, '\\/');
        foreach ($reflection->getAttributes() as $attribute) {
            $name = self::trim($attribute->getName(), '/\\');
            if (is_subclass_of($name, $def) || $name == $def) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array|null
     * @throws ContainerBuilderException
     */
    private static function loadFromCache(): ?array
    {
        $cacheFile = self::getCacheFilePath();
        if (!file_exists($cacheFile) || ($content = file_get_contents($cacheFile)) === false) {
            return null;
        }
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $data;
    }

    /**
     * @param array $data
     * @return void
     * @throws ContainerBuilderException
     */
    private static function writeToCache(array $data): void
    {
        if (empty($data)) {
            return;
        }
        $cacheFile = self::getCacheFilePath();
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }
        file_put_contents($cacheFile, $data, LOCK_EX);
    }

    /**
     * @return string
     * @throws ContainerBuilderException
     */
    private static function getCacheFilePath(): string
    {
        $cacheDir = getcwd();
        if (defined('ATTRIBUTE_BUILDER_CACHE_DIR')) {
            $cacheDir = ATTRIBUTE_BUILDER_CACHE_DIR;
        }
        if (!file_exists(($cacheDir = self::trim($cacheDir, '/', false))) || !is_dir($cacheDir)) {
            throw new ContainerBuilderException('Failed to find cache directory: "' . $cacheDir . '".');
        }
        return $cacheDir . '/sicet7_attribute_container_builder_cache.json';
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
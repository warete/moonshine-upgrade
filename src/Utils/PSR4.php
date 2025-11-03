<?php

namespace Warete\MoonshineUpgrade\Utils;

use Illuminate\Support\Str;
use ReflectionClass;

class PSR4
{
    public static function lowestCommonDir(array $dirs): ?string
    {
        if (! $dirs) {
            return null;
        }

        $normalized = array_map(function (string $p): string {
            $rp = realpath($p);

            return rtrim($rp ?: $p, DIRECTORY_SEPARATOR);
        }, $dirs);

        $parts = array_map(fn ($p): array => explode(DIRECTORY_SEPARATOR, $p), $normalized);

        $lca = [];
        for ($i = 0; ; $i++) {
            $seg = $parts[0][$i] ?? null;
            if ($seg === null) {
                break;
            }
            foreach ($parts as $row) {
                if (! isset($row[$i]) || $row[$i] !== $seg) {
                    $seg = null;

                    break 2;
                }
            }
            $lca[] = $seg;
        }

        if (! $lca) {
            return null;
        }
        $path = implode(DIRECTORY_SEPARATOR, $lca);

        return $path === '' ? DIRECTORY_SEPARATOR : $path;
    }

    public static function getDirByNamespace(string $namespace): ?string
    {
        $directories = collect(get_declared_classes())
            ->filter(fn ($class) => Str::startsWith($class, $namespace))
            ->map(function ($class): ?string {
                $ref = new ReflectionClass($class);
                $file = $ref->getFileName();

                return $file ? dirname($file) : null;
            })
            ->filter()
            ->map(fn ($p): string => rtrim((realpath($p) ?: (string) $p), DIRECTORY_SEPARATOR))
            ->filter(fn ($p): bool => is_dir($p))
            ->unique()
            ->values()
            ->all();

        return static::lowestCommonDir($directories);
    }
}

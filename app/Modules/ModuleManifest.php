<?php

declare(strict_types=1);

namespace App\Modules;

final readonly class ModuleManifest
{
    public function __construct(
        public string $identifier,
        public string $name,
        public string $version,
        public string $description,
        public string $author,
        public string $provider,
        public string $basePath,
        public bool   $hasMigrations,
        public bool   $hasAssets,
        public bool   $isBuiltIn,
        /** @var string[] */
        public array  $requires,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromJson(string $basePath, array $data): self
    {
        return new self(
            identifier:    $data['identifier'],
            name:          $data['name'],
            version:       $data['version'] ?? '1.0.0',
            description:   $data['description'] ?? '',
            author:        $data['author'] ?? '',
            provider:      $data['provider'],
            basePath:      $basePath,
            hasMigrations: (bool) ($data['migrations'] ?? false),
            hasAssets:     (bool) ($data['assets'] ?? false),
            isBuiltIn:     (bool) ($data['built_in'] ?? false),
            requires:      (array) ($data['requires'] ?? []),
        );
    }

    public function path(string ...$segments): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath, ...$segments]);
    }
}

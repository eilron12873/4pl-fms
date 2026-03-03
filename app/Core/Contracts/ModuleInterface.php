<?php

namespace App\Core\Contracts;

interface ModuleInterface
{
    public function getSlug(): string;

    public function getName(): string;

    public function isEnabled(): bool;

    public function getPath(): string;

    public function getRoutesPath(): ?string;

    public function getApiRoutesPath(): ?string;

    /**
     * @return array<int, string>
     */
    public function getDepends(): array;

    public function getMigrationsPath(): ?string;

    public function getViewsPath(): ?string;

    public function getViewNamespace(): string;

    /**
     * @return array<int, string>
     */
    public function getPermissions(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getNav(): ?array;
}


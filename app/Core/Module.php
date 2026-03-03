<?php

namespace App\Core;

use App\Core\Contracts\ModuleInterface;

class Module implements ModuleInterface
{
    public function __construct(
        protected string $slug,
        protected string $name,
        protected bool $enabled,
        protected string $path,
        protected ?string $version = null,
        protected ?string $description = null,
        protected array $depends = [],
        protected array $permissions = [],
        protected ?array $nav = null,
    ) {
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getRoutesPath(): ?string
    {
        $path = $this->path.DIRECTORY_SEPARATOR.'routes.php';

        return is_file($path) ? $path : null;
    }

    public function getApiRoutesPath(): ?string
    {
        $path = $this->path.DIRECTORY_SEPARATOR.'api.php';

        return is_file($path) ? $path : null;
    }

    public function getDepends(): array
    {
        return $this->depends;
    }

    public function getMigrationsPath(): ?string
    {
        $path = $this->path.DIRECTORY_SEPARATOR.'migrations';

        return is_dir($path) ? $path : null;
    }

    public function getViewsPath(): ?string
    {
        $viewsPath = $this->path.DIRECTORY_SEPARATOR.'UI'.DIRECTORY_SEPARATOR.'Views';

        if (is_dir($viewsPath)) {
            return $viewsPath;
        }

        $uiPath = $this->path.DIRECTORY_SEPARATOR.'UI';

        if (is_dir($uiPath)) {
            return $uiPath;
        }

        return null;
    }

    public function getViewNamespace(): string
    {
        return $this->slug;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getNav(): ?array
    {
        return $this->nav;
    }
}


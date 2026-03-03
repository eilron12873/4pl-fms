<?php

namespace App\Core\Services;

use App\Core\ModuleManager;
use Illuminate\Contracts\Foundation\Application;

class ModuleRegistry
{
    public function __construct(
        protected Application $app,
        protected ModuleManager $moduleManager,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function enabledSlugs(): array
    {
        return array_keys($this->moduleManager->getEnabledModules());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function enabledMetadata(): array
    {
        $data = [];

        foreach ($this->moduleManager->getEnabledModules() as $slug => $module) {
            $data[$slug] = [
                'name' => $module->getName(),
                'path' => $module->getPath(),
                'permissions' => $module->getPermissions(),
                'nav' => $module->getNav(),
            ];
        }

        return $data;
    }

    public function isInstalled(string $slug): bool
    {
        return $this->moduleManager->getModule($slug) !== null;
    }

    public function get(string $slug)
    {
        return $this->moduleManager->getModule($slug);
    }
}


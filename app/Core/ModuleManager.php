<?php

namespace App\Core;

use App\Core\Contracts\ModuleInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModuleManager
{
    /**
     * @var array<string, ModuleInterface>
     */
    protected array $modules = [];

    protected string $modulesPath;

    protected Filesystem $files;

    public function __construct(protected Application $app)
    {
        $this->files = new Filesystem();
        $this->modulesPath = (string) config('modules.path', app_path('Modules'));

        $this->discover();
    }

    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    /**
     * Discover modules from the filesystem and module.json manifests.
     */
    public function discover(): void
    {
        $this->modules = [];

        if (! is_dir($this->modulesPath)) {
            return;
        }

        $directories = collect($this->files->directories($this->modulesPath));

        foreach ($directories as $dir) {
            $manifestPath = $dir.DIRECTORY_SEPARATOR.'module.json';

            if (! is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];

            $name = $manifest['name'] ?? basename($dir);
            $slug = $manifest['slug'] ?? Str::kebab($name);
            $enabled = (bool) ($manifest['enabled'] ?? true);
            $version = $manifest['version'] ?? null;
            $description = $manifest['description'] ?? null;
            $depends = Arr::wrap($manifest['depends'] ?? []);

            $permissions = $this->normalizePermissions(
                $slug,
                Arr::wrap($manifest['permissions'] ?? []),
            );

            $nav = $this->normalizeNav($manifest['nav'] ?? null);

            $module = new Module(
                slug: $slug,
                name: Str::studly($name),
                enabled: $enabled,
                path: $dir,
                version: $version,
                description: $description,
                depends: $depends,
                permissions: $permissions,
                nav: $nav,
            );

            $this->modules[$slug] = $module;
        }

        $this->modules = $this->sortByDependencies($this->modules);
    }

    /**
     * @return array<string, ModuleInterface>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @return array<string, ModuleInterface>
     */
    public function getEnabledModules(): array
    {
        return array_filter(
            $this->modules,
            fn (ModuleInterface $module) => $module->isEnabled(),
        );
    }

    public function getModule(string $slug): ?ModuleInterface
    {
        return $this->modules[$slug] ?? null;
    }

    public function isEnabled(string $slug): bool
    {
        return isset($this->modules[$slug]) && $this->modules[$slug]->isEnabled();
    }

    /**
     * @return array<int, string>
     */
    public function getMigrationPaths(): array
    {
        $paths = [];

        foreach ($this->getEnabledModules() as $module) {
            $path = $module->getMigrationsPath();

            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function boot(): void
    {
        foreach ($this->getEnabledModules() as $module) {
            $this->loadRoutes($module);
            $this->loadApiRoutes($module);
            $this->loadViews($module);
            $this->registerModuleProvider($module);
        }
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    protected function normalizePermissions(string $slug, array $permissions): array
    {
        if (empty($permissions)) {
            return [$slug.'.view'];
        }

        return array_values(array_unique(array_map(
            function (string $permission) use ($slug): string {
                if (str_contains($permission, '.')) {
                    return $permission;
                }

                return $slug.'.'.$permission;
            },
            $permissions,
        )));
    }

    /**
     * @param  array<string, mixed>|null  $nav
     * @return array<string, mixed>|null
     */
    protected function normalizeNav(?array $nav): ?array
    {
        if ($nav === null) {
            return null;
        }

        return [
            'label' => $nav['label'] ?? null,
            'route' => $nav['route'] ?? null,
            'icon' => $nav['icon'] ?? 'fas fa-circle',
            'order' => $nav['order'] ?? 100,
        ];
    }

    /**
     * @param  array<string, ModuleInterface>  $modules
     * @return array<string, ModuleInterface>
     */
    protected function sortByDependencies(array $modules): array
    {
        $sorted = [];
        $visited = [];

        $visit = function (string $slug) use (&$visit, &$sorted, &$visited, $modules): void {
            if (isset($visited[$slug]) || ! isset($modules[$slug])) {
                return;
            }

            $visited[$slug] = true;

            foreach ($modules[$slug]->getDepends() as $dep) {
                $visit($dep);
            }

            $sorted[$slug] = $modules[$slug];
        };

        foreach (array_keys($modules) as $slug) {
            $visit($slug);
        }

        return $sorted;
    }

    protected function loadRoutes(ModuleInterface $module): void
    {
        $routesPath = $module->getRoutesPath();

        if (! $routesPath) {
            return;
        }

        $router = $this->app->make('router');

        $router->middleware('web')
            ->group($routesPath);
    }

    protected function loadApiRoutes(ModuleInterface $module): void
    {
        $apiRoutesPath = $module->getApiRoutesPath();

        if (! $apiRoutesPath) {
            return;
        }

        $router = $this->app->make('router');

        $router->middleware('api')
            ->prefix('api')
            ->group($apiRoutesPath);
    }

    protected function loadViews(ModuleInterface $module): void
    {
        $viewsPath = $module->getViewsPath();

        if (! $viewsPath) {
            return;
        }

        $this->app['view']->addNamespace(
            $module->getViewNamespace(),
            $viewsPath,
        );
    }

    protected function registerModuleProvider(ModuleInterface $module): void
    {
        $name = $module->getName();

        $rootNamespace = 'App\\Modules\\'.$name.'\\';

        $providerClass = $rootNamespace.$name.'ServiceProvider';

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);

            return;
        }

        $fallback = $rootNamespace.'Providers\\ModuleServiceProvider';

        if (class_exists($fallback)) {
            $this->app->register($fallback);
        }
    }
}


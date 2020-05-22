<?php

namespace Naoray\LaravelFactoryPrefill\Commands;

use SplFileInfo;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class PrefillAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'factory:all {models?*}
                        {--P|path= : The location where the models are located}
                        {--R|realpath : Indicate any provided paths are pre-resolved absolute paths}
                        {--N|allow-nullable : Also list nullable columns in your factory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prefills all factories for every model with faker method suggestions.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $directory = $this->resolveModelPath();
        $models = $this->argument('models');
        
        if (!File::exists($directory)) {
            $this->error("No files in [$directory] were found!");

            return 1;
        }

        $this->loadModels($directory, $models)->filter(function ($modelClass) {
            return (new ReflectionClass($modelClass))->isSubclassOf(Model::class)
                && $this->callSilent(PrefillFactory::class, [
                    'model' => $modelClass,
                    '--no-interaction' => true,
                    '--own-namespace' => true,
                    '--allow-nullable' => $this->option('allow-nullable'),
                ]) === 0;
        })->pipe(function ($collection) {
            $factoriesCount = $collection->count();
            $this->info($factoriesCount . ' ' . Str::plural('Factory', $factoriesCount) . ' created');
        });
    }

    protected function loadModels(string $directory, array $models = []): Collection
    {
        if (!empty($models)) {
            return collect($models)->map(function ($name) use ($directory) {
                if (strpos($name, '\\') !== false) {
                    return $name;
                }

                return str_replace(
                    [DIRECTORY_SEPARATOR, basename($this->laravel->path()) . '\\'],
                    ['\\', $this->laravel->getNamespace()],
                    basename($this->laravel->path()) . DIRECTORY_SEPARATOR . $name
                );
            });
        }

        return collect(File::files($directory))->map(function (SplFileInfo $file) {
            preg_match('/namespace\s.*/', $file->getContents(), $matches);
            return str_replace(
                ['namespace ', ';'],
                [''],
                $matches[0]
            ) . "\\{$file->getBasename('.php')}";
        });
    }

    protected function resolveModelPath(): string
    {
        if (!$path = $this->option('path')) {
            return app_path();
        }

        return $this->option('realpath')
            ? $path
            : base_path($path);
    }
}

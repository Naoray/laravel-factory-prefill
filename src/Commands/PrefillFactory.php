<?php

namespace Naoray\LaravelFactoryPrefill\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Naoray\EloquentModelAnalyzer\Column;
use Naoray\EloquentModelAnalyzer\Analyzer;
use Naoray\LaravelFactoryPrefill\TypeGuesser;
use Naoray\EloquentModelAnalyzer\RelationMethod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrefillFactory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'factory:prefill 
                        {model : The name of the model for which a blueprint will be created}
                        {--O|own-namespace : When using this flag the model have to include the full namespace}
                        {--N|allow-nullable : Also list nullable columns in your factory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prefills factory for the given model with a faker method suggestions.';

    /**
     * @var \Naoray\LaravelFactoryPrefill\TypeGuesser
     */
    protected $typeGuesser;

    /**
     * Instance of the model the factory is created for.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelInstance;

    /**
     * Create a new command instance.
     */
    public function __construct(TypeGuesser $guesser)
    {
        parent::__construct();

        $this->typeGuesser = $guesser;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $model = $this->argument('model');

        if (!$modelClass = $this->modelExists($model)) {
            return 1;
        }

        $factoryName = class_basename($modelClass);
        if (!$factoryPath = $this->factoryExists($factoryName)) {
            return 1;
        }

        $this->modelInstance = new $modelClass();

        return Analyzer::columns($this->modelInstance)
            ->mapWithKeys(function (Column $column) {
                return $this->mapTableProperties($column);
            })
            ->merge($this->getPropertiesFromMethods())
            ->filter()
            ->unique()
            ->values()
            ->pipe(function ($properties) use ($factoryPath, $modelClass) {
                $statusCode = $this->writeFactoryFile($factoryPath, $properties, $modelClass);
                if ($statusCode === 0) {
                    $this->info('Factory blueprint created!');
                }

                return $statusCode;
            });
    }

    /**
     * Maps properties.
     *
     * @param Column $column
     * @return array
     */
    protected function mapTableProperties(Column $column): array
    {
        $key = $column->getName();

        if (!$this->shouldBeIncluded($column)) {
            return $this->mapToFactory($key);
        }

        if ($column->isForeignKey()) {
            return $this->mapToFactory(
                $key,
                $this->buildRelationFunction($key)
            );
        }

        if ($key === 'password') {
            return $this->mapToFactory($key, "bcrypt('password')");
        }

        $value = $column->isUnique()
            ? '$faker->unique()->'
            : '$faker->';

        return $this->mapToFactory($key, $value . $this->mapToFaker($column));
    }

    /**
     * Checks if a given column should be included in the factory.
     *
     * @param Column $column
     */
    protected function shouldBeIncluded(Column $column)
    {
        $shouldBeIncluded = ($column->getNotNull() || $this->option('allow-nullable'))
            && !$column->getAutoincrement();

        if (!$this->modelInstance->usesTimestamps()) {
            return $shouldBeIncluded;
        }

        $timestamps = [
            $this->modelInstance->getCreatedAtColumn(),
            $this->modelInstance->getUpdatedAtColumn(),
        ];

        if (method_exists($this->modelInstance, 'getDeletedAtColumn')) {
            $timestamps[] = $this->modelInstance->getDeletedAtColumn();
        }

        return $shouldBeIncluded
            && !in_array($column->getName(), $timestamps);
    }

    protected function mapToFactory($key, $value = null): array
    {
        return [
            $key => is_null($value) ? $value : "'{$key}' => $value",
        ];
    }

    /**
     * Get properties via reflection from methods.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getPropertiesFromMethods()
    {
        return Analyzer::relations($this->modelInstance)
            ->filter(function (RelationMethod $method) {
                return $method->returnType() === BelongsTo::class;
            })
            ->mapWithKeys(function (RelationMethod $method) {
                $property = $method->foreignKey();

                return [$property => "'$property' => " . $this->buildRelationFunction($property, $method)];
            });
    }

    /**
     * Check if the given model exists.
     *
     * @param string $name
     *
     * @return bool|string
     */
    protected function modelExists($name)
    {
        if (class_exists($modelClass = $this->qualifyClass($name))) {
            return $modelClass;
        }

        $this->error($modelClass . ' could not be found!');

        if ($this->confirm("Do you wish me to create {$modelClass} for you?")) {
            $this->call('make:model', ['name' => $modelClass]);
            $this->info("Please repeat the factory:prefill $name command.");
        }

        return false;
    }

    /**
     * Check if factory already exists.
     *
     * @param string $name
     *
     * @return bool|string
     */
    protected function factoryExists($name)
    {
        $factoryPath = database_path("factories/{$name}Factory.php");
        if (!File::exists($factoryPath) || $this->confirm("A factory file for $name already exists, do you wish to overwrite the existing file?")) {
            return $factoryPath;
        }

        $this->info('Canceled blueprint creation!');

        return false;
    }

    /**
     * Map name to faker method.
     *
     * @param Column $column
     *
     * @return string
     */
    protected function mapToFaker(Column $column)
    {
        return $this->typeGuesser->guess(
            $column->getName(),
            $column->getType(),
            $column->getLength()
        );
    }

    /**
     * Build relation function.
     *
     * @param string $column
     *
     * @return string
     */
    public function buildRelationFunction(string $column, $relationMethod = null)
    {
        $relationName = optional($relationMethod)->getName() ?? Str::camel(str_replace('_id', '', $column));
        $foreignCallback = 'factory(App\REPLACE_THIS::class)->lazy()';

        try {
            $relatedModel = get_class($this->modelInstance->$relationName()->getRelated());

            return str_replace('App\REPLACE_THIS', $relatedModel, $foreignCallback);
        } catch (\Exception $e) {
            return $foreignCallback;
        }
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     *
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        $rootNamespace = app()->getNamespace();

        if ($this->option('own-namespace') || Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);

        return $this->qualifyClass(
            trim($rootNamespace, '\\') . '\\' . $name
        );
    }

    /**
     * Writes data to factory file.
     *
     * @param string $path
     * @param array  $data
     *
     * @return bool
     */
    protected function writeFactoryFile($path, $data, $modelClass)
    {
        if (0 === count($data)) {
            $this->error('We could not find any data for your factory. Did you `php artisan migrate` already?');

            return 1;
        }

        $content = view('prefill-factory-helper::factory', [
            'modelClass' => $modelClass,
            'data' => $data,
        ])->render();

        File::put($path, "<?php\n\n" . $content);

        return 0;
    }
}

<?php

namespace Naoray\LaravelFactoryPrefill\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Naoray\EloquentModelAnalyzer\Field;
use Naoray\EloquentModelAnalyzer\Analyzer;
use Naoray\LaravelFactoryPrefill\TypeGuesser;
use Naoray\EloquentModelAnalyzer\RelationMethod;
use Illuminate\Database\Eloquent\Relations\Relation;
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
            return false;
        }

        $factoryName = class_basename($modelClass);
        if (!$factoryPath = $this->factoryExists($factoryName)) {
            return false;
        }

        $this->modelInstance = new $modelClass();

        Analyzer::fields($this->modelInstance)
            ->mapWithKeys(function (Field $field) {
                return $this->mapTableProperties($field);
            })
            ->merge($this->getPropertiesFromMethods())
            ->filter()
            ->unique()
            ->values()
            ->pipe(function ($properties) use ($factoryPath, $modelClass) {
                if ($this->writeFactoryFile($factoryPath, $properties, $modelClass)) {
                    $this->info('Factory blueprint created!');
                }
            });
    }

    /**
     * Maps properties.
     *
     * @param Field $field
     * @return array
     */
    protected function mapTableProperties(Field $field): array
    {
        $key = $field->getName();

        if (!$this->shouldBeIncluded($field)) {
            return $this->mapToFactory($key);
        }

        if ($field->isForeignKey()) {
            return $this->mapToFactory(
                $key,
                $this->buildRelationFunction($key)
            );
        }

        if ($key === 'password') {
            return $this->mapToFactory($key, "bcrypt('password')");
        }

        $value = $field->isUnique()
            ? '$faker->unique()->'
            : '$faker->';

        return $this->mapToFactory($key, $value . $this->mapToFaker($field));
    }

    /**
     * Checks if a given column should be included in the factory.
     *
     * @param Field $field
     */
    protected function shouldBeIncluded(Field $field)
    {
        $shouldBeIncluded = ($field->getNotNull() || $this->option('allow-nullable'))
            && !$field->getAutoincrement();

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
            && !in_array($field->getName(), $timestamps);
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
     * @param Field $data
     *
     * @return string
     */
    protected function mapToFaker(Field $field)
    {
        return $this->typeGuesser->guess(
            $field->getName(),
            $field->getType(),
            $field->getLength()
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

            return false;
        }

        $content = view('prefill-factory-helper::factory', [
            'modelClass' => $modelClass,
            'data' => $data,
        ])->render();

        File::put($path, "<?php\n\n" . $content);

        return true;
    }
}

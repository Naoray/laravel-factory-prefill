<?php

namespace Naoray\LaravelFactoryPrefill\Commands;

use ReflectionClass;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Naoray\LaravelFactoryPrefill\TypeGuesser;
use Illuminate\Database\Eloquent\Relations\Relation;

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

        if (! $modelClass = $this->modelExists($model)) {
            return false;
        }

        $factoryName = class_basename($modelClass);
        if (! $factoryPath = $this->factoryExists($factoryName)) {
            return false;
        }

        $this->modelInstance = new $modelClass();

        // get table name from model
        $tableName = $this->modelInstance->getTable();
        $tableIndexes = DB::getDoctrineSchemaManager()->listTableIndexes($tableName);

        // get column names
        $columnListing = Schema::getColumnListing($tableName);

        // foreach column name get the type
        $columnData = collect($columnListing)->mapWithKeys(function ($column) use ($tableName, $tableIndexes) {
            return $this->getPropertiesFromTable($column, $tableName, $tableIndexes);
        })->merge(
            $this->getPropertiesFromMethods()
        )->filter(function ($data) {
            return (bool) $data;
        })
            ->unique()
            ->values()
            ->all();

        if ($this->writeFactoryFile($factoryPath, $columnData, $modelClass)) {
            $this->info('Factory blueprint created!');
        }
    }

    /**
     * Get properties from table.
     *
     * @param string $column
     * @param string $tableName
     * @param array  $tableIndexes
     *
     * @return array
     */
    protected function getPropertiesFromTable($column, $tableName, $tableIndexes)
    {
        $data = (object) DB::connection()->getDoctrineColumn($tableName, $column)->toArray();

        if (! $this->shouldBeIncluded($data)) {
            return [$data->name => null];
        }

        $isForeignKey = $this->isForeignKey($column, $tableIndexes);
        $isUnique = $this->isUnique($column, $tableIndexes);

        $value = $isForeignKey
            ? $this->buildRelationFunction($data->name)
            : ($isUnique ? '$faker->unique()->' : '$faker->') . $this->mapToFaker($data);

        return [$data->name => "'$data->name' => $value"];
    }

    /**
     * Get properties via reflection from methods.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getPropertiesFromMethods()
    {
        return collect(get_class_methods($this->modelInstance))
            ->mapWithKeys(function ($method, $key) {
                if ($this->isMethodOfCurrentInstance($method)) {
                    $relationMethods = $this->getRelationMethods($method);

                    return $this->extractPropertiesFromMethods($relationMethods, $method, $key);
                }

                return [$key => null];
            });
    }

    /**
     * Check if method is a non-accessor and not a method from the parent class.
     *
     * @param string $method
     *
     * @return bool
     */
    protected function isMethodOfCurrentInstance($method)
    {
        return ! method_exists(Model::class, $method) && ! Str::startsWith($method, 'get');
    }

    /**
     * Get relation method written code.
     *
     * @param string $method
     *
     * @return string
     */
    protected function getRelationMethods($method)
    {
        $reflectionMethod = new \ReflectionMethod($this->modelInstance, $method);
        $file = new \SplFileObject($reflectionMethod->getFileName());
        $file->seek($reflectionMethod->getStartLine() - 1);

        $code = '';
        while ($file->key() < $reflectionMethod->getEndLine()) {
            $code .= $file->current();
            $file->next();
        }

        $code = trim(preg_replace('/\s\s+/', '', $code));

        return Str::before(
            Str::after($code, 'function('),
            '}'
        );
    }

    /**
     * Extract properties from relation methods.
     *
     * @param string $relationMethods
     * @param string $method
     * @param int    $key
     *
     * @return array
     */
    protected function extractPropertiesFromMethods($relationMethods, $method, $key)
    {
        $search = '$this->belongsTo(';

        if (! Str::contains($relationMethods, $search)) {
            return [$key => null];
        }

        $relationObj = $this->modelInstance->$method();

        if (! $relationObj instanceof Relation) {
            return [$key => null];
        }

        $property = method_exists($relationObj, 'getForeignKeyName')
            ? $relationObj->getForeignKeyName()
            : $relationObj->getForeignKey();

        return [$property => "'$property' => " . $this->buildRelationFunction($property, $method)];
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
        if (! File::exists($factoryPath) || $this->confirm("A factory file for $name already exists, do you wish to overwrite the existing file?")) {
            return $factoryPath;
        }

        $this->info('Canceled blueprint creation!');

        return false;
    }

    /**
     * Checks if a given column should be included in the factory.
     *
     * @param stdClass $data
     */
    protected function shouldBeIncluded($data)
    {
        $shouldBeIncluded = ($data->notnull || $this->option('allow-nullable'))
            && ! $data->autoincrement;

        if (! $this->modelInstance->usesTimestamps()) {
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
            && ! in_array($data->name, $timestamps);
    }

    /**
     * Check if column is a foreign key.
     *
     * @param string $name
     * @param array  $tableIndexes
     *
     * @return bool
     */
    protected function isForeignKey($name, $tableIndexes)
    {
        return $this->isOfColumnType($name, 'foreign', $tableIndexes);
    }

    /**
     * Check if column is a unique one.
     *
     * @param string $name
     * @param array  $tableIndexes
     *
     * @return bool
     */
    protected function isUnique($name, $tableIndexes)
    {
        return $this->isOfColumnType($name, 'unique', $tableIndexes);
    }

    /**
     * Map name to faker method.
     *
     * @param stdClass $data
     *
     * @return string
     */
    protected function mapToFaker($data)
    {
        return $this->typeGuesser->guess($data->name, $data->type, $data->length);
    }

    /**
     * Check if a given name is of a given type.
     *
     * @param string $name
     * @param string $type
     * @param array  $tableIndexes
     *
     * @return bool
     */
    protected function isOfColumnType($name, $type, $tableIndexes)
    {
        return (bool) Arr::where(array_keys($tableIndexes), function ($index) use ($name, $type) {
            return Str::contains($index, '_' . $type) && Str::contains($index, '_' . $name . '_');
        });
    }

    /**
     * Build relation function.
     *
     * @param string $column
     *
     * @return string
     */
    public function buildRelationFunction($column, $relationMethod = null)
    {
        $relationName = $relationMethod ?? Str::camel(str_replace('_id', '', $column));
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

        $content = $this->laravel->view->make('prefill-factory-helper::factory', [
            'modelReflection' => new ReflectionClass($modelClass),
            'data' => $data,
        ])->render();

        File::put($path, "<?php\n\n" . $content);

        return true;
    }
}

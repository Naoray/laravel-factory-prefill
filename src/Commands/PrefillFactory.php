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
        // get model
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
        $columnData = collect($columnListing)->map(function ($column) use ($tableName, $tableIndexes) {
            $data = (object) DB::connection()->getDoctrineColumn($tableName, $column)->toArray();

            if (! $this->shouldBeIncluded($data)) {
                return null;
            }

            $isForeignKey = $this->isForeignKey($column, $tableIndexes);
            $isUnique = $this->isUnique($column, $tableIndexes);

            $value = $isForeignKey
                ? $this->buildRelationFunction($data->name)
                : ($isUnique ? '$faker->unique()->' : '$faker->') . $this->mapToFaker($data);

            return "'$data->name' => $value";
        })->filter(function ($data) {
            return (bool) $data;
        })->values()->toArray();

        $this->writeFactoryFile($factoryPath, $columnData, $modelClass);
        $this->info('Factory blueprint created!');
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
            return Str::contains($index, $type) && Str::contains($index, $name);
        });
    }

    /**
     * Build relation function.
     *
     * @param string $column
     *
     * @return string
     */
    public function buildRelationFunction($column)
    {
        $relationName = str_replace('_id', '', $column);
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

        if ($this->option('own-namespace') || starts_with($name, $rootNamespace)) {
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
     */
    protected function writeFactoryFile($path, $data, $modelClass)
    {
        if (0 === count($data)) {
            return $this->error('We could not find any data for your factory. Did you `php artisan migrate` already?');
        }

        $content = $this->laravel->view->make('prefill-factory-helper::factory', [
            'modelReflection' => new ReflectionClass($modelClass),
            'data' => $data,
        ])->render();

        File::put($path, "<?php\n\n" . $content);
    }
}

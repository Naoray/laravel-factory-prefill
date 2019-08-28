use Faker\Generator as Faker;
use {{ $modelReflection->getName() }};

$factory->define({{ class_basename($modelReflection->getName()) }}::class, function (Faker $faker) {
  return [
    @foreach($data as $value)
      {!! $value !!},
    @endforeach
  ];
});');
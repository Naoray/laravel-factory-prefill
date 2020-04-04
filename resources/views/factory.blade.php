use Faker\Generator as Faker;

$factory->define({{ $modelClass }}::class, function (Faker $faker) {
    return [
    @foreach($data as $value)
    {!! $value !!},
    @endforeach
];
});
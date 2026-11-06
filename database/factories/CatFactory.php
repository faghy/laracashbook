<?php

namespace Database\Factories;

use App\Models\Movimento;
use Faker\Core\Number;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cat>
 */
class CatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'descriz_cat' => $this->faker->text(20),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
            'entity_id' => rand(1,3),
        ];
    }
}

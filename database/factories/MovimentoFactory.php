<?php

namespace Database\Factories;

use App\Models\Cat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movimento>
 */
class MovimentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->numberBetween(1,3),
            'entity_id' => $this->faker->numberBetween(1,3),
            'categoria' => $this->faker->numberBetween(1,6),
            'descrizione' => $this->faker->text(20),
           # 'datecreated' => $this->faker->date(),
            'entratauscita' => $this->faker->numberBetween(0,1),
            'importo' => $this->faker->numberBetween(1,500),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
         //   'cat_fk' => Cat::factory()

        ];
    }
}

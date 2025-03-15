<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'type' => $this->faker->randomElement(['deposit', 'transfer']),
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

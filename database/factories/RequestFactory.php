<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Request>
 */
class RequestFactory extends Factory
{
    protected $model = \App\Models\Request::class;

    public function definition(): array
    {
        return [
            'type'           => 'service',
            'status'         => 'pending',
            'invoice_number' => 'T-TEST-' . fake()->unique()->numerify('######'),
            'address'        => fake()->streetAddress(),
            'latitude'       => fake()->latitude(22.0, 24.0),
            'longitude'      => fake()->longitude(57.0, 59.5),
            'scheduled_at'   => now()->addDays(3)->format('Y-m-d'),
            'service_type'   => 'maintenance',
            'description'    => fake()->sentence(),
        ];
    }

    public function service(): static
    {
        return $this->state([
            'type'           => 'service',
            'service_type'   => 'maintenance',
            'invoice_number' => 'T-TEST-' . fake()->unique()->numerify('######'),
        ]);
    }

    public function installation(): static
    {
        return $this->state([
            'type'           => 'installation',
            'service_type'   => null,
            'description'    => null,
            'invoice_number' => 'B-TEST-' . fake()->unique()->numerify('######'),
            'product_type'   => 'AC Unit',
            'quantity'       => 1,
            'is_site_ready'  => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function assigned(): static
    {
        return $this->state(['status' => 'assigned']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'completed_at' => now()]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function assignedTo(User $technician): static
    {
        return $this->state([
            'technician_id' => $technician->id,
            'status'        => 'assigned',
        ]);
    }
}

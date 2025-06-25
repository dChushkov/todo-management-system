<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Category Factory
 * 
 * This factory creates test data for Category model.
 * It provides realistic category names for testing.
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(), // Generate unique category names
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    /**
     * Create a work-related category.
     *
     * @return static
     */
    public function work(): static
    {
        $workCategories = [
            'Development',
            'Design',
            'Marketing',
            'Sales',
            'Support',
            'Management',
            'Planning',
            'Testing',
            'Documentation',
            'Deployment'
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->unique()->randomElement($workCategories),
        ]);
    }

    /**
     * Create a personal category.
     *
     * @return static
     */
    public function personal(): static
    {
        $personalCategories = [
            'Shopping',
            'Health',
            'Finance',
            'Home',
            'Fitness',
            'Entertainment',
            'Family',
            'Travel',
            'Learning',
            'Hobbies'
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->unique()->randomElement($personalCategories),
        ]);
    }

    /**
     * Create a project-specific category.
     *
     * @return static
     */
    public function project(): static
    {
        $projectCategories = [
            'Frontend',
            'Backend',
            'Database',
            'API',
            'UI/UX',
            'Mobile',
            'DevOps',
            'Security',
            'Performance',
            'Integration'
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->unique()->randomElement($projectCategories),
        ]);
    }

    /**
     * Create a category with a specific name.
     *
     * @param string $name
     * @return static
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Create a category that was created recently.
     *
     * @return static
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a category that was created a long time ago.
     *
     * @return static
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-6 months'),
        ]);
    }
} 
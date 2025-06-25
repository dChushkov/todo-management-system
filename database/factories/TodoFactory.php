<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Todo Factory
 * 
 * This factory creates test data for Todo model.
 * It provides realistic test data for unit and feature tests.
 */
class TodoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Todo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $this->faker->sentence(3, 6), // Generate realistic todo titles
            'description' => $this->faker->optional(0.7)->paragraph(2, 4), // 70% chance to have description
            'priority' => $this->faker->randomElement(Priority::cases()), // Random priority
            'completed_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'), // 30% chance to be completed
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    /**
     * Indicate that the todo is completed.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the todo is incomplete (pending).
     *
     * @return static
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the todo has high priority.
     *
     * @return static
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Priority::HIGH,
        ]);
    }

    /**
     * Indicate that the todo has medium priority.
     *
     * @return static
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Priority::MEDIUM,
        ]);
    }

    /**
     * Indicate that the todo has low priority.
     *
     * @return static
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Priority::LOW,
        ]);
    }

    /**
     * Indicate that the todo has a long description.
     *
     * @return static
     */
    public function withLongDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $this->faker->paragraphs(3, true), // Multiple paragraphs
        ]);
    }

    /**
     * Indicate that the todo has no description.
     *
     * @return static
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    /**
     * Indicate that the todo was created recently.
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
     * Indicate that the todo was created a long time ago.
     *
     * @return static
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-6 months', '-3 months'),
        ]);
    }

    /**
     * Indicate that the todo belongs to a specific user.
     *
     * @param User $user
     * @return static
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the todo belongs to a specific category.
     *
     * @param Category $category
     * @return static
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Create a todo with realistic work-related content.
     *
     * @return static
     */
    public function workRelated(): static
    {
        $workTitles = [
            'Review project documentation',
            'Prepare presentation slides',
            'Update client requirements',
            'Fix critical bug in production',
            'Schedule team meeting',
            'Update API documentation',
            'Deploy to staging environment',
            'Code review for feature branch',
            'Update database schema',
            'Test new functionality'
        ];

        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->randomElement($workTitles),
            'description' => $this->faker->optional(0.8)->sentence(10, 20),
            'priority' => $this->faker->randomElement([Priority::HIGH, Priority::MEDIUM]), // Work tasks are usually higher priority
        ]);
    }

    /**
     * Create a todo with realistic personal content.
     *
     * @return static
     */
    public function personal(): static
    {
        $personalTitles = [
            'Buy groceries',
            'Call dentist for appointment',
            'Pay utility bills',
            'Clean the house',
            'Go to the gym',
            'Read a book',
            'Watch a movie',
            'Visit family',
            'Plan vacation',
            'Organize closet'
        ];

        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->randomElement($personalTitles),
            'description' => $this->faker->optional(0.5)->sentence(5, 15),
            'priority' => $this->faker->randomElement([Priority::LOW, Priority::MEDIUM]), // Personal tasks are usually lower priority
        ]);
    }

    /**
     * Create a todo that is urgent (high priority and recent).
     *
     * @return static
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Priority::HIGH,
            'created_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'completed_at' => null, // Urgent tasks are usually not completed yet
        ]);
    }

    /**
     * Create a todo that is overdue (old and incomplete).
     *
     * @return static
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'completed_at' => null,
            'priority' => $this->faker->randomElement([Priority::HIGH, Priority::MEDIUM]), // Overdue tasks are usually important
        ]);
    }
} 
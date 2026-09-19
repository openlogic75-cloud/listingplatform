<?php

namespace Tests\Unit;

use App\Support\BlindIndex;
use Tests\TestCase;

class BlindIndexTest extends TestCase
{
    public function test_it_is_deterministic_case_insensitive_and_trimmed(): void
    {
        $this->assertSame(
            BlindIndex::make('Ram@Example.com'),
            BlindIndex::make('  ram@example.com ')
        );
    }

    public function test_different_values_produce_different_indexes(): void
    {
        $this->assertNotSame(
            BlindIndex::make('a@example.test'),
            BlindIndex::make('b@example.test')
        );
    }
}

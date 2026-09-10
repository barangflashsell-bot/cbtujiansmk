<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoadConcurrencyTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test CBT load test command execution for concurrent workload simulation.
     */
    public function test_cbt_load_test_command_runs_successfully(): void
    {
        $this->artisan('cbt:load-test', [
            '--students' => 20,
            '--race-concurrency' => 5,
        ])
            ->expectsOutputToContain('STATUS: PASS / ACCEPTED')
            ->assertSuccessful();
    }
}

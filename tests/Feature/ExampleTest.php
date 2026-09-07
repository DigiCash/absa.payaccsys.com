<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

uses(TestCase::class);

it('returns a successful response from the application root', function (): void {
    $this->get('/')
        ->assertStatus(200);
});

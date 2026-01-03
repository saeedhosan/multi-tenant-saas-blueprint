<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    
    if (! class_exists(\App\Models\User::class)) {
        $this->markTestSkipped('App models are not included in this public case study.');
    }

    Gate::before(function ($user, string $ability) {
        return $ability === 'the access';
    });

    Route::get('/monitor-emails', fn () => 'Monitor Emails')->name('route-name');
});

it('shows monitor emails page for customers', function (): void {
    $company  = \App\Models\Company::factory()->create();
    $customer = User::factory()->create(['portal' => User::CUSTOMER, 'company_id' => $company->id]);

    /** @var Tests\TestCase $this */
    $response = $this->actingAs($customer)->get(route('route-name'));

    $response->assertOk()
        ->assertSee('Monitor Emails')
        ->assertSee('Add private email');
});

it('redirects guests from monitor emails', function (): void {

    /** @var Tests\TestCase $this */
    $response = $this->get(route('route-name'));

    $response->assertRedirect(route('login'));
});

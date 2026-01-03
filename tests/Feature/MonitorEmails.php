<?php

declare(strict_types=1);

use App\Livewire\Ghl\MonitorEmails as MonitorEmailsComponent;
use App\Models\MonitorEmail;
use App\Models\User;
use App\Notifications\VerifyMonitorEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    
    if (! class_exists(\App\Models\User::class)) {
        $this->markTestSkipped('App models are not included in this public case study.');
    }

    if (! class_exists(MonitorEmailsComponent::class)) {
        $this->markTestSkipped('MonitorEmails Livewire component is not included in this public case study.');
    }

    Gate::before(function ($user, string $ability) {
        return $ability === 'test access';
    });

    Route::get('/monitor-emails', fn () => 'Monitor Emails')->name('route-name');
});

it('validates monitor email input', function (): void {

    $company  = \App\Models\Company::factory()->create();
    
    $customer = User::factory()->create([
        'company_id' => $company->id,
        //...
    ]);

    Livewire::actingAs($customer);

    Livewire::test(MonitorEmailsComponent::class)
        ->set('email', '')
        ->call('save')
        ->assertHasErrors(['email' => 'required']);

    Livewire::test(MonitorEmailsComponent::class)
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email' => 'email']);
});

it('blocks duplicate monitor emails', function (): void {

    Notification::fake();

    $company  = \App\Models\Company::factory()->create();
    $customer = User::factory()->create([
        'company_id' => $company->id,
        //...
    ]);

    $monitor = MonitorEmail::factory()->create([
        'user_id' => $customer->id, 
        'email' => 'dup@example.com'
        //...
    ]);

    Livewire::actingAs($customer);

    Livewire::test(MonitorEmailsComponent::class)
        ->set('email', $monitor->email)
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);

    expect(MonitorEmail::count())->toBe(1);

    Notification::assertNothingSent();

});

it('blocks when monitor email limit is reached', function (): void {

    Notification::fake();

    $company  = \App\Models\Company::factory()->create();
    $customer = User::factory()->create([
        'company_id' => $company->id,
        //...
    ]);

    MonitorEmail::factory()->create(['user_id' => $customer->id, 'email' => 'one@example.com']);
    MonitorEmail::factory()->create(['user_id' => $customer->id, 'email' => 'two@example.com']);

    Livewire::actingAs($customer);

    Livewire::test(MonitorEmailsComponent::class)
        ->set('email', 'third@example.com')
        ->call('save')
        ->assertDispatched('error');

    expect(MonitorEmail::where('user_id', $customer->id)->count())->toBe(2);
    
    Notification::assertNothingSent();
});

it('stores monitor email and sends verification', function (): void {

    Notification::fake();

    $company  = \App\Models\Company::factory()->create();
    $customer = User::factory()->create(['portal' => User::CUSTOMER, 'company_id' => $company->id]);

    Livewire::actingAs($customer);

    $component = Livewire::test(MonitorEmailsComponent::class)
        ->set('email', 'new@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('email', '')
        ->assertDispatched('success')
        ->assertDispatched('refreshComponent');

    $record = MonitorEmail::where('email', 'new@example.com')->first();

    expect($record)->not->toBeNull()
        ->and($record->user_id)->toBe($customer->id)
        ->and($record->verified_at)->toBeNull();

    Notification::assertSentOnDemand(VerifyMonitorEmail::class, function ($notification, $channels, $notifiable): bool {
        expect($channels)->toContain('mail');
        expect($notifiable->routes['mail'] ?? null)->toBe('new@example.com');
        return true;
    });

    $component->assertStatus(200);
});

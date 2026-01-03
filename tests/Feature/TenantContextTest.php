<?php

declare(strict_types=1);

use App\Http\Middleware\CompanyMiddleware;
use App\Models\Company;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    
    if (! class_exists(\App\Models\User::class)) {
        $this->markTestSkipped('App models are not included in this public case study.');
    }

    TenantContext::forget();
});

class CaptureTenantContextJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('tenant-context-job', TenantContext::currentId(), 60);
    }
}

it('sets tenant context via company middleware', function (): void {
    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);

    $request = Request::create('/tenant-context-test', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = (new CompanyMiddleware())->handle($request, fn () => response()->noContent());

    expect($response->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    expect(TenantContext::currentId())->toBe($company->id);
});

it('propagates tenant context into queued jobs', function (): void {
    
    config(['queue.default' => 'sync']);

    $company = Company::factory()->create();
    $user    = User::factory()->create(['company_id' => $company->id]);

    TenantContext::set($user->company_id);

    Cache::forget('tenant-context-job');

    dispatch(new CaptureTenantContextJob());

    expect(Cache::get('tenant-context-job'))->toBe($company->id);
});

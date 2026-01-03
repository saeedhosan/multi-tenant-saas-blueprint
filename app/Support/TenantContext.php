<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    protected static ?int $companyId = null;

    /**
     * Set the active tenant company id.
     */
    public static function set(?int $companyId): void
    {
        self::$companyId = $companyId;
    }

    /**
     * Get the active tenant company id.
     */
    public static function currentId(): ?int
    {
        if (self::$companyId !== null) {
            return self::$companyId;
        }

        return Auth::user()?->company_id;
    }

    /**
     * Get the active tenant company model.
     */
    public static function currentCompany(): ?Company
    {
        $companyId = self::currentId();

        return $companyId ? Company::query()->find($companyId) : null;
    }

    /**
     * Forget the active tenant context.
     */
    public static function forget(): void
    {
        self::$companyId = null;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use App\Services\Odoo\OdooServiceInterface;
use Closure;
use Illuminate\Http\Request;

class EnsureFinancialEligibility
{
    public function __construct(private OdooServiceInterface $odoo) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! AppSetting::bool('enforce_financial_eligibility')) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || ! $user->isCustomer() || ! $user->odoo_id) {
            return $next($request);
        }

        $debt = $this->odoo->getCustomerDebt($user->odoo_id);

        if ($debt > 0) {
            return response()->json([
                'success'    => false,
                'message'    => "لديك مبالغ مستحقة غير مسددة بقيمة {$debt} ر.ع. يرجى تسوية حسابك قبل تقديم طلب جديد.",
                'amount_due' => $debt,
            ], 402);
        }

        return $next($request);
    }
}

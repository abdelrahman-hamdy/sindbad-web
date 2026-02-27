<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private \App\Services\Odoo\OdooServiceInterface $odoo) {}

    public function validateOdooUser(string $phone): array
    {
        $customer = $this->odoo->findCustomerByPhoneOrName($phone, null);

        if (! $customer) {
            throw new Exception('لم يتم العثور على حساب مرتبط بهذا الرقم في النظام');
        }

        return $customer;
    }

    public function activateUser(string $phone, string $password, int $odooId, string $name): string
    {
        $user = User::updateOrCreate(
            ['phone' => $phone],
            [
                'name' => $name,
                'odoo_id' => $odooId,
                'password' => Hash::make($password),
                'is_active' => true,
                'role' => UserRole::Customer->value,
            ]
        );

        // Assign Spatie role
        if (! $user->hasRole(UserRole::Customer->value)) {
            $user->assignRole(UserRole::Customer->value);
        }

        return $user->createToken('auth_token')->plainTextToken;
    }
}

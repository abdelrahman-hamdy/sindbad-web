<?php

namespace App\Services\Odoo;

interface OdooServiceInterface
{
    public function findCustomerByPhoneOrName(string $phone, ?string $name): ?array;
    public function getCustomerOrders(int $odooId, ?string $phone = null, ?string $name = null): array;
    public function checkTaskReadiness(int $odooId): bool;
    public function getCustomerDebt(int $odooId): float;
    public function getCustomerInvoices(int $odooId, ?string $phone = null): array;
    public function getUserTasks(int $odooId): array;
    public function bustProductsCache(): void;
    public function getProducts(int $limit = 50, int $offset = 0, string $search = ''): array;
    public function getProductsCount(string $search = ''): int;
}

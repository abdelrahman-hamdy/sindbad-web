<?php

namespace App\Filament\Pages;

use App\Services\Odoo\OdooServiceInterface;
use Filament\Pages\Page;

class InventoryPage extends Page
{
    protected string $view = 'filament.pages.inventory';

    public static function getNavigationLabel(): string { return __('Inventory'); }
    public static function getNavigationGroup(): ?string { return __('System'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationSort(): ?int { return 12; }

    public function getTitle(): string { return __('Product Inventory'); }

    // ── State ────────────────────────────────────────────────────────────────

    public bool   $isLoading   = true;
    public array  $products    = [];
    public string $search      = '';
    public int    $page        = 1;
    public int    $perPage     = 50;
    public int    $total       = 0;
    public int    $totalPages  = 0;

    // ── Lifecycle ────────────────────────────────────────────────────────────

    // mount() intentionally left empty so the page frame renders instantly.
    // wire:init in the view calls loadProducts() after first render.
    public function mount(): void {}

    // ── Data Loading ─────────────────────────────────────────────────────────

    public function loadProducts(): void
    {
        $this->isLoading = true;

        try {
            $odoo = app(OdooServiceInterface::class);
            $offset = ($this->page - 1) * $this->perPage;

            [$this->products, $this->total] = [
                $odoo->getProducts($this->perPage, $offset, $this->search),
                $odoo->getProductsCount($this->search),
            ];
            $this->totalPages = (int) ceil($this->total / $this->perPage);
        } catch (\Exception $e) {
            $this->products   = [];
            $this->total      = 0;
            $this->totalPages = 0;
        }

        $this->isLoading = false;
    }

    // ── Search ───────────────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadProducts();
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    public function nextPage(): void
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
            $this->loadProducts();
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadProducts();
        }
    }

    // ── Refresh (clears cache) ────────────────────────────────────────────────

    public function refresh(): void
    {
        app(OdooServiceInterface::class)->bustProductsCache();
        $this->page   = 1;
        $this->search = '';
        $this->loadProducts();
    }
}

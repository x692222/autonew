<?php

namespace App\Http\Controllers\Backoffice\Shared;

use App\Http\Controllers\Controller;
use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Models\Invoice\InvoiceLineItem;
use App\Models\Quotation\Customer;
use App\Models\Quotation\Quotation;
use App\Models\Quotation\QuotationLineItem;
use App\Models\Stock\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuotationLookupsController extends Controller
{
    public function searchSystemCustomers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Quotation::class);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 3) {
            return response()->json(['options' => []]);
        }

        $options = Customer::query()
            ->whereNull('dealer_id')
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('firstname', 'like', '%' . $query . '%')
                    ->orWhere('lastname', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('contact_number', 'like', '%' . $query . '%');
            })
            ->orderBy('firstname')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => $this->mapCustomerOption($customer))
            ->values()
            ->all();

        return response()->json(['options' => $options]);
    }

    public function storeSystemCustomer(Request $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $data = $request->validate([
            'type' => ['required', 'in:individual,company'],
            'title' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'address' => ['required', 'string', 'min:20', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\/-]+$/'],
        ]);

        $customer = Customer::query()->create([
            ...$data,
            'dealer_id' => null,
        ]);

        return response()->json([
            'id' => $customer->id,
            'label' => $this->buildCustomerLabel($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ]);
    }

    public function searchSystemInvoiceCustomers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 3) {
            return response()->json(['options' => []]);
        }

        $options = Customer::query()
            ->whereNull('dealer_id')
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('firstname', 'like', '%' . $query . '%')
                    ->orWhere('lastname', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('contact_number', 'like', '%' . $query . '%');
            })
            ->orderBy('firstname')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => $this->mapCustomerOption($customer))
            ->values()
            ->all();

        return response()->json(['options' => $options]);
    }

    public function storeSystemInvoiceCustomer(Request $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $data = $request->validate([
            'type' => ['required', 'in:individual,company'],
            'title' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'address' => ['required', 'string', 'min:20', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\/-]+$/'],
        ]);

        $customer = Customer::query()->create([
            ...$data,
            'dealer_id' => null,
        ]);

        return response()->json([
            'id' => $customer->id,
            'label' => $this->buildCustomerLabel($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ]);
    }

    public function searchDealerCustomers(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showQuotations', $dealer);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 3) {
            return response()->json(['options' => []]);
        }

        $options = Customer::query()
            ->where('dealer_id', $dealer->id)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('firstname', 'like', '%' . $query . '%')
                    ->orWhere('lastname', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('contact_number', 'like', '%' . $query . '%');
            })
            ->orderBy('firstname')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => $this->mapCustomerOption($customer))
            ->values()
            ->all();

        return response()->json(['options' => $options]);
    }

    public function storeDealerCustomer(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('createCustomer', $dealer);

        $data = $request->validate([
            'type' => ['required', 'in:individual,company'],
            'title' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'address' => ['required', 'string', 'min:20', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\/-]+$/'],
        ]);

        $customer = Customer::query()->create([
            ...$data,
            'dealer_id' => $dealer->id,
        ]);

        return response()->json([
            'id' => $customer->id,
            'label' => $this->buildCustomerLabel($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ]);
    }

    public function searchDealerInvoiceCustomers(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showInvoices', $dealer);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 3) {
            return response()->json(['options' => []]);
        }

        $options = Customer::query()
            ->where('dealer_id', $dealer->id)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('firstname', 'like', '%' . $query . '%')
                    ->orWhere('lastname', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('contact_number', 'like', '%' . $query . '%');
            })
            ->orderBy('firstname')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => $this->mapCustomerOption($customer))
            ->values()
            ->all();

        return response()->json(['options' => $options]);
    }

    public function storeDealerInvoiceCustomer(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('createCustomer', $dealer);

        $data = $request->validate([
            'type' => ['required', 'in:individual,company'],
            'title' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'address' => ['required', 'string', 'min:20', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\/-]+$/'],
        ]);

        $customer = Customer::query()->create([
            ...$data,
            'dealer_id' => $dealer->id,
        ]);

        return response()->json([
            'id' => $customer->id,
            'label' => $this->buildCustomerLabel($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ]);
    }

    public function searchDealerConfigurationCustomers(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexQuotations', $dealer);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 3) {
            return response()->json(['options' => []]);
        }

        $options = Customer::query()
            ->where('dealer_id', $dealer->id)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('firstname', 'like', '%' . $query . '%')
                    ->orWhere('lastname', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('contact_number', 'like', '%' . $query . '%');
            })
            ->orderBy('firstname')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => $this->mapCustomerOption($customer))
            ->values()
            ->all();

        return response()->json(['options' => $options]);
    }

    public function storeDealerConfigurationCustomer(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateCustomer', $dealer);

        $data = $request->validate([
            'type' => ['required', 'in:individual,company'],
            'title' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'address' => ['required', 'string', 'min:20', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\/-]+$/'],
        ]);

        $customer = Customer::query()->create([
            ...$data,
            'dealer_id' => $dealer->id,
        ]);

        return response()->json([
            'id' => $customer->id,
            'label' => $this->buildCustomerLabel($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ]);
    }

    public function searchDealerConfigurationInvoiceCustomers(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexInvoices', $dealer);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 3) {
            return response()->json(['options' => []]);
        }

        $options = Customer::query()
            ->where('dealer_id', $dealer->id)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('firstname', 'like', '%' . $query . '%')
                    ->orWhere('lastname', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('contact_number', 'like', '%' . $query . '%');
            })
            ->orderBy('firstname')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => $this->mapCustomerOption($customer))
            ->values()
            ->all();

        return response()->json(['options' => $options]);
    }

    public function storeDealerConfigurationInvoiceCustomer(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateCustomer', $dealer);

        $data = $request->validate([
            'type' => ['required', 'in:individual,company'],
            'title' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'address' => ['required', 'string', 'min:20', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\/-]+$/'],
        ]);

        $customer = Customer::query()->create([
            ...$data,
            'dealer_id' => $dealer->id,
        ]);

        return response()->json([
            'id' => $customer->id,
            'label' => $this->buildCustomerLabel($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ]);
    }

    public function lineItemSuggestionsForSystem(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Quotation::class);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'section' => ['required', 'string'],
        ]);

        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $lineItems = QuotationLineItem::query()
            ->whereNull('dealer_id')
            ->where('section', $section)
            ->whereNotNull('sku')
            ->where('sku', 'like', '%' . $q . '%')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (QuotationLineItem $item) => [
                'source' => 'history',
                'sku' => $item->sku,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'qty' => 1,
                'total' => (float) $item->amount,
                'stock_id' => null,
            ])
            ->values()
            ->all();

        return response()->json([
            'stock' => [],
            'history' => $lineItems,
        ]);
    }

    public function lineItemSuggestionsForDealer(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showQuotations', $dealer);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'section' => ['required', 'string'],
        ]);

        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $stock = $section === 'general'
            ? Stock::query()
                ->whereHas('branch', fn ($query) => $query->where('dealer_id', $dealer->id))
                ->with([
                    'vehicleItem.make',
                    'vehicleItem.model',
                    'commercialItem.make',
                    'commercialItem.model',
                    'motorbikeItem.make',
                    'motorbikeItem.model',
                ])
                ->where('is_active', true)
                ->where('is_sold', false)
                ->where('internal_reference', 'like', '%' . $q . '%')
                ->orderBy('internal_reference')
                ->limit(5)
                ->get()
                ->map(fn (Stock $item) => $this->mapStockSuggestion($item))
                ->values()
                ->all()
            : [];

        $lineItems = QuotationLineItem::query()
            ->where('dealer_id', $dealer->id)
            ->where('section', $section)
            ->whereNotNull('sku')
            ->where('sku', 'like', '%' . $q . '%')
            ->when($section === 'general', function ($query) use ($dealer): void {
                $query->whereNotExists(function ($subQuery) use ($dealer): void {
                    $subQuery->selectRaw('1')
                        ->from('stock')
                        ->join('dealer_branches as db', 'db.id', '=', 'stock.branch_id')
                        ->whereColumn('stock.internal_reference', 'quotation_line_items.sku')
                        ->where('db.dealer_id', $dealer->id);
                });
            })
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (QuotationLineItem $item) => [
                'source' => 'history',
                'sku' => $item->sku,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'qty' => 1,
                'total' => (float) $item->amount,
                'stock_id' => null,
            ])
            ->values()
            ->all();

        return response()->json([
            'stock' => $stock,
            'history' => $lineItems,
        ]);
    }

    public function lineItemSuggestionsForDealerConfiguration(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexQuotations', $dealer);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'section' => ['required', 'string'],
        ]);

        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $stock = $section === 'general'
            ? Stock::query()
                ->whereHas('branch', fn ($query) => $query->where('dealer_id', $dealer->id))
                ->with([
                    'vehicleItem.make',
                    'vehicleItem.model',
                    'commercialItem.make',
                    'commercialItem.model',
                    'motorbikeItem.make',
                    'motorbikeItem.model',
                ])
                ->where('is_active', true)
                ->where('is_sold', false)
                ->where('internal_reference', 'like', '%' . $q . '%')
                ->orderBy('internal_reference')
                ->limit(5)
                ->get()
                ->map(fn (Stock $item) => $this->mapStockSuggestion($item))
                ->values()
                ->all()
            : [];

        $lineItems = QuotationLineItem::query()
            ->where('dealer_id', $dealer->id)
            ->where('section', $section)
            ->whereNotNull('sku')
            ->where('sku', 'like', '%' . $q . '%')
            ->when($section === 'general', function ($query) use ($dealer): void {
                $query->whereNotExists(function ($subQuery) use ($dealer): void {
                    $subQuery->selectRaw('1')
                        ->from('stock')
                        ->join('dealer_branches as db', 'db.id', '=', 'stock.branch_id')
                        ->whereColumn('stock.internal_reference', 'quotation_line_items.sku')
                        ->where('db.dealer_id', $dealer->id);
                });
            })
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (QuotationLineItem $item) => [
                'source' => 'history',
                'sku' => $item->sku,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'qty' => 1,
                'total' => (float) $item->amount,
                'stock_id' => null,
            ])
            ->values()
            ->all();

        return response()->json([
            'stock' => $stock,
            'history' => $lineItems,
        ]);
    }

    public function lineItemSuggestionsForSystemInvoices(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'section' => ['required', 'string'],
        ]);

        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $lineItems = InvoiceLineItem::query()
            ->whereNull('dealer_id')
            ->where('section', $section)
            ->whereNotNull('sku')
            ->where('sku', 'like', '%' . $q . '%')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (InvoiceLineItem $item) => [
                'source' => 'history',
                'sku' => $item->sku,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'qty' => 1,
                'total' => (float) $item->amount,
                'stock_id' => null,
            ])
            ->values()
            ->all();

        return response()->json([
            'stock' => [],
            'history' => $lineItems,
        ]);
    }

    public function lineItemSuggestionsForDealerInvoices(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showInvoices', $dealer);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'section' => ['required', 'string'],
        ]);

        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $stock = $section === 'general'
            ? Stock::query()
                ->whereHas('branch', fn ($query) => $query->where('dealer_id', $dealer->id))
                ->with([
                    'vehicleItem.make',
                    'vehicleItem.model',
                    'commercialItem.make',
                    'commercialItem.model',
                    'motorbikeItem.make',
                    'motorbikeItem.model',
                ])
                ->where('is_active', true)
                ->where('is_sold', false)
                ->where('internal_reference', 'like', '%' . $q . '%')
                ->orderBy('internal_reference')
                ->limit(5)
                ->get()
                ->map(fn (Stock $item) => $this->mapStockSuggestion($item))
                ->values()
                ->all()
            : [];

        $lineItems = InvoiceLineItem::query()
            ->where('dealer_id', $dealer->id)
            ->where('section', $section)
            ->whereNotNull('sku')
            ->where('sku', 'like', '%' . $q . '%')
            ->when($section === 'general', function ($query) use ($dealer): void {
                $query->whereNotExists(function ($subQuery) use ($dealer): void {
                    $subQuery->selectRaw('1')
                        ->from('stock')
                        ->join('dealer_branches as db', 'db.id', '=', 'stock.branch_id')
                        ->whereColumn('stock.internal_reference', 'invoice_line_items.sku')
                        ->where('db.dealer_id', $dealer->id);
                });
            })
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (InvoiceLineItem $item) => [
                'source' => 'history',
                'sku' => $item->sku,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'qty' => 1,
                'total' => (float) $item->amount,
                'stock_id' => null,
            ])
            ->values()
            ->all();

        return response()->json([
            'stock' => $stock,
            'history' => $lineItems,
        ]);
    }

    public function lineItemSuggestionsForDealerConfigurationInvoices(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexInvoices', $dealer);

        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'section' => ['required', 'string'],
        ]);

        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $stock = $section === 'general'
            ? Stock::query()
                ->whereHas('branch', fn ($query) => $query->where('dealer_id', $dealer->id))
                ->with([
                    'vehicleItem.make',
                    'vehicleItem.model',
                    'commercialItem.make',
                    'commercialItem.model',
                    'motorbikeItem.make',
                    'motorbikeItem.model',
                ])
                ->where('is_active', true)
                ->where('is_sold', false)
                ->where('internal_reference', 'like', '%' . $q . '%')
                ->orderBy('internal_reference')
                ->limit(5)
                ->get()
                ->map(fn (Stock $item) => $this->mapStockSuggestion($item))
                ->values()
                ->all()
            : [];

        $lineItems = InvoiceLineItem::query()
            ->where('dealer_id', $dealer->id)
            ->where('section', $section)
            ->whereNotNull('sku')
            ->where('sku', 'like', '%' . $q . '%')
            ->when($section === 'general', function ($query) use ($dealer): void {
                $query->whereNotExists(function ($subQuery) use ($dealer): void {
                    $subQuery->selectRaw('1')
                        ->from('stock')
                        ->join('dealer_branches as db', 'db.id', '=', 'stock.branch_id')
                        ->whereColumn('stock.internal_reference', 'invoice_line_items.sku')
                        ->where('db.dealer_id', $dealer->id);
                });
            })
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (InvoiceLineItem $item) => [
                'source' => 'history',
                'sku' => $item->sku,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'qty' => 1,
                'total' => (float) $item->amount,
                'stock_id' => null,
            ])
            ->values()
            ->all();

        return response()->json([
            'stock' => $stock,
            'history' => $lineItems,
        ]);
    }

    private function mapStockSuggestion(Stock $item): array
    {
        $typed = $this->typedStockItem($item);

        return [
            'source' => 'stock',
            'stock_id' => $item->id,
            'sku' => $item->internal_reference,
            'description' => $item->name ?: $item->internal_reference,
            'amount' => (float) $item->price,
            'qty' => 1,
            'total' => (float) $item->price,
            'meta' => collect([
                $item->type ? strtoupper((string) $item->type) : null,
                $typed?->condition ? 'Condition ' . strtoupper((string) $typed->condition) : null,
                $typed?->year_model ? 'Year ' . $typed->year_model : null,
                $typed?->millage ? 'Millage ' . $typed->millage : null,
                $typed?->make?->name ? 'Make ' . strtoupper((string) $typed->make->name) : null,
                $typed?->model?->name ? 'Model ' . strtoupper((string) $typed->model->name) : null,
                $item->is_active ? 'ACTIVE' : 'INACTIVE',
                $item->is_sold ? 'SOLD' : 'UNSOLD',
            ])->filter()->implode(' | '),
        ];
    }

    private function typedStockItem(Stock $item): ?Model
    {
        return $item->vehicleItem
            ?? $item->commercialItem
            ?? $item->motorbikeItem;
    }

    private function mapCustomerOption(Customer $customer): array
    {
        return [
            'value' => $customer->id,
            'label' => $this->buildCustomerLabel($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ];
    }

    private function buildCustomerLabel(Customer $customer): string
    {
        $name = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));
        $type = strtoupper((string) ($customer->type?->value ?? $customer->type ?? '-'));
        $parts = collect([
            $customer->email ? 'Email: ' . $customer->email : null,
            $customer->contact_number ? 'Contact: ' . $customer->contact_number : null,
            $customer->id_number ? 'ID: ' . $customer->id_number : null,
            'Type: ' . $type,
        ])->filter()->implode(' | ');

        return $parts !== '' ? $name . ' (' . $parts . ')' : $name;
    }
}

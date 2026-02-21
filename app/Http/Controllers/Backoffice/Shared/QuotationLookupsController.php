<?php

namespace App\Http\Controllers\Backoffice\Shared;

use App\Actions\Backoffice\Shared\Customers\UpsertCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\QuotationLookups\LineItemSuggestionsRequest;
use App\Http\Requests\Backoffice\Shared\QuotationLookups\UpsertQuotationLookupCustomerRequest;
use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Models\Quotation\Customer;
use App\Models\Quotation\Quotation;
use App\Support\Lookups\QuotationLookupFormattingService;
use App\Support\Lookups\QuotationLookupQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuotationLookupsController extends Controller
{
    public function __construct(
        private readonly QuotationLookupFormattingService $formattingService,
        private readonly QuotationLookupQueryService $queryService,
        private readonly UpsertCustomerAction $upsertCustomerAction,
    ) {}

    public function searchSystemCustomers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Quotation::class);

        $query = trim((string) $request->query('q', ''));
        return $this->searchCustomersResponse(query: $query, dealer: null);
    }

    public function storeSystemCustomer(UpsertQuotationLookupCustomerRequest $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $data = $request->validated();

        return $this->storeCustomerResponse(data: $data, dealer: null);
    }

    public function searchSystemInvoiceCustomers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = trim((string) $request->query('q', ''));
        return $this->searchCustomersResponse(query: $query, dealer: null);
    }

    public function storeSystemInvoiceCustomer(UpsertQuotationLookupCustomerRequest $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $data = $request->validated();

        return $this->storeCustomerResponse(data: $data, dealer: null);
    }

    public function searchDealerCustomers(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showQuotations', $dealer);

        $query = trim((string) $request->query('q', ''));
        return $this->searchCustomersResponse(query: $query, dealer: $dealer);
    }

    public function storeDealerCustomer(UpsertQuotationLookupCustomerRequest $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('createCustomer', $dealer);

        $data = $request->validated();

        return $this->storeCustomerResponse(data: $data, dealer: $dealer);
    }

    public function searchDealerInvoiceCustomers(Request $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showInvoices', $dealer);

        $query = trim((string) $request->query('q', ''));
        return $this->searchCustomersResponse(query: $query, dealer: $dealer);
    }

    public function storeDealerInvoiceCustomer(UpsertQuotationLookupCustomerRequest $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('createCustomer', $dealer);

        $data = $request->validated();

        return $this->storeCustomerResponse(data: $data, dealer: $dealer);
    }

    public function searchDealerConfigurationCustomers(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexQuotations', $dealer);

        $query = trim((string) $request->query('q', ''));
        return $this->searchCustomersResponse(query: $query, dealer: $dealer);
    }

    public function storeDealerConfigurationCustomer(UpsertQuotationLookupCustomerRequest $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateCustomer', $dealer);

        $data = $request->validated();

        return $this->storeCustomerResponse(data: $data, dealer: $dealer);
    }

    public function searchDealerConfigurationInvoiceCustomers(Request $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexInvoices', $dealer);

        $query = trim((string) $request->query('q', ''));
        return $this->searchCustomersResponse(query: $query, dealer: $dealer);
    }

    public function storeDealerConfigurationInvoiceCustomer(UpsertQuotationLookupCustomerRequest $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationCreateCustomer', $dealer);

        $data = $request->validated();

        return $this->storeCustomerResponse(data: $data, dealer: $dealer);
    }

    public function lineItemSuggestionsForSystem(LineItemSuggestionsRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Quotation::class);

        return $this->quotationSuggestionResponse(
            data: $request->validated(),
            dealer: null
        );
    }

    public function lineItemSuggestionsForDealer(LineItemSuggestionsRequest $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showQuotations', $dealer);

        return $this->quotationSuggestionResponse(
            data: $request->validated(),
            dealer: $dealer
        );
    }

    public function lineItemSuggestionsForDealerConfiguration(LineItemSuggestionsRequest $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexQuotations', $dealer);

        return $this->quotationSuggestionResponse(
            data: $request->validated(),
            dealer: $dealer
        );
    }

    public function lineItemSuggestionsForSystemInvoices(LineItemSuggestionsRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        return $this->invoiceSuggestionResponse(
            data: $request->validated(),
            dealer: null
        );
    }

    public function lineItemSuggestionsForDealerInvoices(LineItemSuggestionsRequest $request, Dealer $dealer): JsonResponse
    {
        Gate::authorize('showInvoices', $dealer);

        return $this->invoiceSuggestionResponse(
            data: $request->validated(),
            dealer: $dealer
        );
    }

    public function lineItemSuggestionsForDealerConfigurationInvoices(LineItemSuggestionsRequest $request): JsonResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexInvoices', $dealer);

        return $this->invoiceSuggestionResponse(
            data: $request->validated(),
            dealer: $dealer
        );
    }

    private function searchCustomersResponse(string $query, ?Dealer $dealer): JsonResponse
    {
        if (mb_strlen($query) < 3) {
            return response()->json(['options' => []]);
        }

        $customers = $this->queryService->searchCustomers(dealer: $dealer, query: $query);
        $options = $this->formattingService->mapCustomerOptions($customers);

        return response()->json(['options' => $options]);
    }

    private function storeCustomerResponse(array $data, ?Dealer $dealer): JsonResponse
    {
        $customer = $this->upsertCustomerAction->execute(customer: null, data: $data, dealer: $dealer);

        return response()->json($this->formattingService->mapCreatedCustomer($customer));
    }

    private function quotationSuggestionResponse(array $data, ?Dealer $dealer): JsonResponse
    {
        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $stock = $dealer && $section === 'general'
            ? $this->formattingService->stockSuggestions($dealer, $q)
            : [];

        $lineItems = $this->queryService->quotationHistoryLineItems(
            dealer: $dealer,
            section: $section,
            query: $q
        );

        return response()->json([
            'stock' => $stock,
            'history' => $this->formattingService->mapHistoryLineItems($lineItems),
        ]);
    }

    private function invoiceSuggestionResponse(array $data, ?Dealer $dealer): JsonResponse
    {
        $q = trim((string) $data['q']);
        $section = (string) $data['section'];

        $stock = $dealer && $section === 'general'
            ? $this->formattingService->stockSuggestions($dealer, $q)
            : [];

        $lineItems = $this->queryService->invoiceHistoryLineItems(
            dealer: $dealer,
            section: $section,
            query: $q
        );

        return response()->json([
            'stock' => $stock,
            'history' => $this->formattingService->mapHistoryLineItems($lineItems),
        ]);
    }

}

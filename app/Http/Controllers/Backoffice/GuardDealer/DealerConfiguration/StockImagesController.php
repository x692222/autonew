<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;
use App\Http\Controllers\Controller;
use App\Models\Media\Media;
use App\Models\Stock\Stock;
use App\Support\Stock\StockImagesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class StockImagesController extends Controller
{
    public function __construct(private readonly StockImagesService $stockImagesService)
    {
    }

    public function index(Request $request, Stock $stock): JsonResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditStock', $stock);

        return response()->json(
            $this->stockImagesService->index(
                dealer: $actor->dealer,
                stock: $stock,
                isDealerSession: true,
                dealerUserId: (string) $actor->id,
                bucketPage: (int) $request->input('bucket_page', 1),
                perPage: (int) $request->input('per_page', 60),
            )
        );
    }

    public function upload(Request $request, Stock $stock): JsonResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditStock', $stock);

        $validator = Validator::make($request->all(), [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
            'max_images' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        try {
            $this->stockImagesService->upload($stock, $request->file('images', []), (int) ($request->input('max_images', 8)));
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['errors' => ['images' => [$e->getMessage()]]], 422);
        }
    }

    public function assign(Request $request, Stock $stock): JsonResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditStock', $stock);

        $validator = Validator::make($request->all(), [
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['string'],
            'media_id' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        $ids = collect($request->input('media_ids', []))->push($request->input('media_id'))->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();

        try {
            $this->stockImagesService->assignFromBucket($actor->dealer, $stock, $ids, true, (string) $actor->id);
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['errors' => ['media_ids' => [$e->getMessage()]]], 422);
        }
    }

    public function destroy(Request $request, Stock $stock, Media $media): JsonResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditStock', $stock);

        $this->stockImagesService->destroy($stock, (string) $media->id);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Stock $stock): JsonResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditStock', $stock);

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        $this->stockImagesService->reorder($stock, $request->input('ids', []));

        return response()->json(['ok' => true]);
    }

    public function moveBackToBucket(Request $request, Stock $stock): JsonResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditStock', $stock);

        $validator = Validator::make($request->all(), [
            'media_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        try {
            $this->stockImagesService->moveBackToBucket($stock, (string) $actor->id, (string) $request->input('media_id'));
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['errors' => ['media_id' => [$e->getMessage()]]], 422);
        }
    }
}

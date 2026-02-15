<?php

namespace App\Support\Stock;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUserBucket;
use App\Models\Media\Media;
use App\Models\Stock\Stock;
use App\Support\Services\MediaHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StockImagesService
{
    public function index(Dealer $dealer, Stock $stock, bool $isDealerSession, ?string $dealerUserId, int $bucketPage = 1, int $perPage = 60): array
    {
        $bucket = null;
        $userBuckets = collect();

        if ($isDealerSession && $dealerUserId) {
            $bucket = DealerUserBucket::query()->firstOrCreate(
                ['user_id' => $dealerUserId],
                ['name' => 'Default Bucket']
            );
        }

        if (! $isDealerSession) {
            $userBuckets = $dealer->buckets()->select('dealer_user_buckets.id')->get();
        }

        $bucketQuery = Media::query()
            ->with(['model.user:id,firstname,lastname,email'])
            ->when($isDealerSession && $bucket, fn ($query) => $query->where('model_id', (string) $bucket->id))
            ->when(! $isDealerSession, fn ($query) => $query->whereIn('model_id', $userBuckets->pluck('id')->all()))
            ->where('model_type', DealerUserBucket::class)
            ->where('collection_name', 'dealer_user_bucket')
            ->whereIn(DB::raw('LOWER(SUBSTRING_INDEX(file_name, ".", -1))'), config('siteConfig.uploads.allowed_image_types'))
            ->orderBy('created_at', 'asc');

        $bucketPaginated = $bucketQuery->paginate($perPage, ['*'], 'bucket_page', $bucketPage);

        $stockMedia = Media::query()
            ->where('model_type', Stock::class)
            ->where('model_id', (string) $stock->id)
            ->where('collection_name', 'stock_images')
            ->orderBy('order_column')
            ->orderBy('created_at')
            ->get();

        return [
            'bucket' => [
                'data' => $bucketPaginated->getCollection()->map(function (Media $media) {
                    $payload = MediaHelper::mediaPayload($media);
                    $user = $media->model?->user;
                    $payload['owner'] = $user ? [
                        'id' => $user->id,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'email' => $user->email,
                    ] : null;
                    return $payload;
                })->values()->all(),
                'current_page' => $bucketPaginated->currentPage(),
                'last_page' => $bucketPaginated->lastPage(),
                'total' => $bucketPaginated->total(),
            ],
            'stock' => $stockMedia->map(fn (Media $media) => MediaHelper::mediaPayload($media))->values()->all(),
            'is_dealer_user_session' => $isDealerSession,
        ];
    }

    public function upload(Stock $stock, array $images, int $maxImages = 8): void
    {
        $currentCount = $stock->media()->where('collection_name', 'stock_images')->count();
        if ($currentCount + count($images) > $maxImages) {
            throw new \RuntimeException("Only {$maxImages} images are allowed per stock item.");
        }

        /** @var UploadedFile $file */
        foreach ($images as $file) {
            $stock->addMedia($file)->toMediaCollection('stock_images');
        }
    }

    public function assignFromBucket(Dealer $dealer, Stock $stock, array $mediaIds, bool $isDealerSession, ?string $dealerUserId): void
    {
        if ($mediaIds === []) {
            return;
        }

        if ($isDealerSession) {
            $bucketIds = DealerUserBucket::query()->where('user_id', $dealerUserId)->pluck('id');
        } else {
            $bucketIds = DealerUserBucket::query()
                ->whereIn('user_id', $dealer->users()->pluck('id')->all())
                ->pluck('id');
        }

        $mediaItems = Media::query()
            ->whereIn('id', $mediaIds)
            ->where('model_type', DealerUserBucket::class)
            ->whereIn('model_id', $bucketIds->all())
            ->where('collection_name', 'dealer_user_bucket')
            ->get();

        if ($mediaItems->count() !== count($mediaIds)) {
            throw new \RuntimeException('Invalid bucket selection.');
        }

        foreach ($mediaItems as $media) {
            $media->move($stock, 'stock_images');
        }
    }

    public function destroy(Stock $stock, string $mediaId): void
    {
        $media = Media::query()
            ->where('id', $mediaId)
            ->where('model_type', Stock::class)
            ->where('model_id', (string) $stock->id)
            ->where('collection_name', 'stock_images')
            ->firstOrFail();

        $media->delete();
    }

    public function reorder(Stock $stock, array $mediaIds): void
    {
        $ids = collect($mediaIds)->map(fn ($id) => (string) $id)->unique()->values();

        $existing = Media::query()
            ->where('model_type', Stock::class)
            ->where('model_id', (string) $stock->id)
            ->where('collection_name', 'stock_images')
            ->whereIn('id', $ids->all())
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $lookup = array_flip($existing);

        foreach ($ids as $index => $mediaId) {
            if (! isset($lookup[$mediaId])) {
                continue;
            }

            Media::query()->where('id', $mediaId)->update(['order_column' => $index + 1]);
        }
    }

    public function moveBackToBucket(Stock $stock, string $dealerUserId, string $mediaId): void
    {
        $bucket = DealerUserBucket::query()->firstOrCreate(
            ['user_id' => $dealerUserId],
            ['name' => 'Default Bucket']
        );

        $media = Media::query()
            ->where('id', $mediaId)
            ->where('model_type', Stock::class)
            ->where('model_id', (string) $stock->id)
            ->where('collection_name', 'stock_images')
            ->firstOrFail();

        $media->move($bucket, 'dealer_user_bucket');
    }
}

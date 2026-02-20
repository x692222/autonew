<?php

namespace App\Http\Resources\Backoffice\Shared\Stock;
use App\Models\Stock\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Stock */
class StockIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $context = (array) $request->attributes->get('stock_context', []);

        $includeDealer = (bool) ($context['include_dealer'] ?? false);
        $canToggleActive = (bool) ($context['can_toggle_active'] ?? false);
        $canShowNotes = (bool) ($context['can_show_notes'] ?? false);
        $canView = (bool) ($context['can_view'] ?? false);
        $canEdit = (bool) ($context['can_edit'] ?? false);
        $canDelete = (bool) ($context['can_delete'] ?? false);

        $branch = $this->branch;
        $dealer = $branch?->dealer;

        $typed = match ($this->type) {
            Stock::STOCK_TYPE_VEHICLE => $this->vehicleItem,
            Stock::STOCK_TYPE_COMMERCIAL => $this->commercialItem,
            Stock::STOCK_TYPE_LEISURE => $this->leisureItem,
            Stock::STOCK_TYPE_MOTORBIKE => $this->motorbikeItem,
            Stock::STOCK_TYPE_GEAR => $this->gearItem,
            default => null,
        };

        $paymentStatus = '';
        if ((bool) ($this->has_full_payment ?? false)) {
            $paymentStatus = 'full';
        } elseif ((bool) ($this->has_partial_payment ?? false)) {
            $paymentStatus = 'partial';
        }

        $makeName = '-';
        $modelName = '-';
        $condition = '-';
        $isImportLabel = '-';
        $millage = '-';
        $colorTitle = '-';
        $gearboxType = '-';
        $driveType = '-';
        $fuelType = '-';

        if ($typed) {
            if (isset($typed->condition)) {
                $condition = Str::headline((string) $typed->condition);
            }

            if (isset($typed->is_import)) {
                $isImportLabel = $typed->is_import ? 'Yes' : 'No';
            }

            if (isset($typed->millage) && $typed->millage !== null) {
                $millage = (int) $typed->millage;
            }

            if (isset($typed->color) && $typed->color !== null) {
                $colorTitle = Str::headline((string) $typed->color);
            }

            if (isset($typed->gearbox_type) && $typed->gearbox_type !== null) {
                $gearboxType = Str::headline((string) $typed->gearbox_type);
            }

            if (isset($typed->drive_type) && $typed->drive_type !== null) {
                $driveType = Str::upper((string) $typed->drive_type);
            }

            if (isset($typed->fuel_type) && $typed->fuel_type !== null) {
                $fuelType = Str::headline((string) $typed->fuel_type);
            }

            if ($typed->relationLoaded('make') && $typed->make) {
                $makeName = (string) $typed->make->name;
            }

            if (isset($typed->model_id) && $typed->model_id) {
                $labels = (array) ($context['model_labels'] ?? []);
                $modelName = $labels[(string) $typed->model_id] ?? '-';
            }
        }

        return [
            'id' => $this->id,
            'dealer_id' => $dealer?->id,
            'dealer_name' => $includeDealer ? ($dealer?->name ?? '-') : null,
            'dealer_is_active' => $includeDealer ? (bool) ($dealer?->is_active ?? false) : null,
            'branch_name' => $branch?->name ?? '-',
            'type_title' => Str::headline((string) $this->type),
            'internal_reference' => $this->internal_reference,
            'is_live' => (bool) $this->isLive($this->resource),
            'payment_status' => $paymentStatus,
            'price' => $this->price,
            'discounted_price' => $this->discounted_price,
            'condition' => $condition,
            'stock_images_count' => (int) ($this->stock_images_count ?? 0),
            'published_at' => $this->published_at,
            'name' => $this->name,
            'is_active' => (bool) $this->is_active,
            'active_label' => $this->is_active ? 'Yes' : '-',
            'is_paid' => (bool) $this->is_paid,
            'is_sold' => (bool) $this->is_sold,
            'sold_label' => $this->is_sold ? 'Yes' : '-',
            'make_name' => $makeName,
            'model_name' => $modelName,
            'millage' => $millage,
            'color_title' => $colorTitle,
            'gearbox_type' => $gearboxType,
            'drive_type' => $driveType,
            'fuel_type' => $fuelType,
            'is_import_label' => $isImportLabel,
            'notes_count' => (int) ($this->notes_count ?? 0),
            'can' => [
                'toggle_active' => $canToggleActive,
                'show_notes' => $canShowNotes,
                'view' => $canView,
                'edit' => $canEdit,
                'delete' => $canDelete,
            ],
        ];
    }
}

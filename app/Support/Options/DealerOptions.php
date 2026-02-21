<?php

namespace App\Support\Options;

use App\Http\Resources\KeyValueOptions\DealerPipelineIdCollection;
use App\Http\Resources\KeyValueOptions\GeneralCollection;
use App\Http\Resources\KeyValueOptions\DealerIdCollection;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerUser;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use App\Models\WhatsappNumber;

final class DealerOptions extends AbstractOptions
{
    public static function dealersList(bool $withAll = false): GeneralCollection
    {
        $items = Dealer::query()
            ->orderBy('name')
            ->select(['id as value', 'name as label'])
            ->get()
            ->map(fn($m) => [
                'label' => $m->label,
                'value' => $m->value,
            ])
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function branchesList(?string $dealerId, bool $withAll = false): DealerIdCollection
    {
        $items = DealerBranch::query()
            ->when($dealerId, function($q) use ($dealerId) {
                $q->where('dealer_id', $dealerId);
            })
            ->select(['id as value', 'name as label', 'dealer_id'])
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'label' => $m->label,
                'value' => $m->value,
                'dealer_id' => $m->dealer_id,
            ])
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new DealerIdCollection($options);
    }

    public static function branchesSimpleList(?string $dealerId, bool $withAll = false): GeneralCollection
    {
        $items = DealerBranch::query()
            ->when($dealerId, function($q) use ($dealerId) {
                $q->where('dealer_id', $dealerId);
            })
            ->select(['id as value', 'name as label'])
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'label' => $m->label,
                'value' => $m->value,
            ])
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function usersList(?string $dealerId, bool $withAll = false): DealerIdCollection
    {
        $items = DealerUser::query()
            ->when($dealerId, function($q) use ($dealerId) {
                $q->where('dealer_id', $dealerId);
            })
            ->select(['id', 'firstname', 'lastname', 'dealer_id'])
            ->orderBy('firstname')
            ->get()
            ->map(fn ($m) => [
                'label' => sprintf("%s %s", $m->firstname, $m->lastname),
                'value' => $m->id,
                'dealer_id' => $m->dealer_id,
            ])
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new DealerIdCollection($options);
    }

    public static function pipelinesList(?string $dealerId, bool $withAll = false): DealerIdCollection
    {
        $items = LeadPipeline::query()
            ->when($dealerId, function($q) use ($dealerId) {
                $q->where('dealer_id', $dealerId);
            })
            ->select(['id', 'name', 'dealer_id'])
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'label' => $m->name,
                'value' => $m->id,
                'dealer_id' => $m->dealer_id,
            ])
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new DealerIdCollection($options);
    }

    public static function stagesList(?string $dealerId, ?string $pipelineId, bool $withAll = false): DealerPipelineIdCollection
    {
        $items = LeadStage::query()
            ->when($dealerId, function ($q) use ($dealerId) {
                $q->whereHas('pipeline', function ($q) use ($dealerId) {
                    $q->where('dealer_id', $dealerId);
                });
            })
            ->when($pipelineId, function ($q) use ($pipelineId) {
                $q->where('pipeline_id', $pipelineId);
            })
            ->select(['id', 'name', 'pipeline_id'])
            ->orderBy('sort_order')
            ->get()
            ->map(function (LeadStage $s) {
                return [
                    'value'       => (string) $s->getKey(),
                    'label'       => (string) $s->name,
                    'pipeline_id' => (string) $s->pipeline_id,
                    'dealer_id'   => $s->pipeline?->dealer_id, // will be null unless you eager load
                ];
            })
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new DealerPipelineIdCollection($options);
    }

    public static function availableWhatsappNumbers(bool $withAll = false): GeneralCollection
    {
        $items = WhatsappNumber::query()
            ->select(['id as value', 'msisdn as label'])
            ->where('type', WhatsappNumber::TYPE_DEALER)
            ->whereNull('dealer_id')
            ->whereNull('deleted_at')
            ->orderBy('msisdn')
            ->get()
            ->map(fn ($m) => [
                'label' => $m->label,
                'value' => $m->value,
            ])
            ->all();

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

}

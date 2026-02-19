<?php

namespace App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\Dealer\Dealer;
use App\Models\WhatsappNumber;

class AssignDealerWhatsappNumberAction
{
    public function execute(Dealer $dealer, ?WhatsappNumber $whatsappNumber): bool
    {
        if (!$whatsappNumber) {
            return true;
        }

        return WhatsappNumber::query()
            ->whereKey($whatsappNumber->getKey())
            ->whereNull('dealer_id')
            ->update([
                'dealer_id' => $dealer->getKey(),
                'type' => WhatsappNumber::TYPE_DEALER,
            ]) > 0;
    }
}

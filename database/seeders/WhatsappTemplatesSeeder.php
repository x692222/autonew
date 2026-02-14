<?php

namespace Database\Seeders;

use App\Models\Messaging\WhatsappTemplate;
use App\Models\Dealer\Dealer;
use App\Models\System\Configuration\WhatsappProvider;
use Illuminate\Database\Seeder;

class WhatsappTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $dealerId = Dealer::query()->value('id');
        if (!$dealerId) {
            return;
        }

        $provider = WhatsappProvider::query()
            ->select(['id', 'identifier'])
            ->where('identifier', 'twilio')
            ->firstOrFail();

        $templates = [
            [
                'name'     => 'lead_followup_1',
                'language' => 'en_US',
                'category' => 'utility',
                'body'     => 'Hi {{1}}, thanks for your enquiry about the {{2}}. A consultant will contact you shortly.',
            ],
            [
                'name'     => 'lead_followup_2',
                'language' => 'en_US',
                'category' => 'utility',
                'body'     => 'Hi {{1}}, just checking in about the {{2}} you viewed. Would you like to book a test drive?',
            ],
            [
                'name'     => 'trade_in_offer',
                'language' => 'en_US',
                'category' => 'utility',
                'body'     => 'Hi {{1}}, we can assist with a trade-in valuation for your vehicle. Would you like us to proceed?',
            ],
            [
                'name'     => 'finance_available',
                'language' => 'en_US',
                'category' => 'utility',
                'body'     => 'Hi {{1}}, finance options are available for the {{2}}. Would you like a repayment estimate?',
            ],
            [
                'name'     => 'appointment_confirmation',
                'language' => 'en_US',
                'category' => 'utility',
                'body'     => 'Hi {{1}}, your appointment to view the {{2}} has been booked. Please let us know if you need to reschedule.',
            ],
        ];

        foreach ($templates as $t) {
            WhatsappTemplate::query()->create([
                'dealer_id'            => (string) $dealerId,
                'provider_id'          => (string) $provider->getKey(),
                'provider_template_id' => null, // set later when you sync from provider
                'name'                 => $t['name'],
                'language'             => $t['language'],
                'category'             => $t['category'],
                'status'               => WhatsappTemplate::STATUS_APPROVED,
                'body'                 => $t['body'],
                'components'           => [
                    [
                        'type' => 'BODY',
                        'text' => $t['body'],
                    ],
                ],
            ]);
        }
    }
}

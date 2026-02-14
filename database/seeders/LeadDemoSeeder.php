<?php

namespace Database\Seeders;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerUser;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadConversation;
use App\Models\Leads\LeadMessage;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use App\Models\Leads\LeadStageEvent;
use App\Models\Leads\Channels\EmailMessage;
use App\Models\Leads\Channels\EmailThread;
use App\Models\Leads\Channels\WhatsappMessage;
use App\Models\Leads\Channels\WhatsappThread;
use App\Models\Stock\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadDemoSeeder extends Seeder
{
    /**
     * Safety knobs (tune if your DB is big)
     */
    private const MIN_LEADS_PER_STOCK = 1;
    private const MAX_LEADS_PER_STOCK = 35; // can be 200, but this can explode fast depending on stock counts
    private const MAX_MESSAGES_PER_CONVERSATION = 30;

    // Edge case ratios (0.0 - 1.0)
    private const RATIO_LEADS_WITHOUT_STOCK = 0.12;
    private const RATIO_LEADS_WITHOUT_CONVERSATION = 0.10;
    private const RATIO_CONVERSATIONS_WITHOUT_LEAD = 0.10;
    private const RATIO_CONVERSATIONS_WITHOUT_MESSAGES = 0.15;

    // Channels
    private const CHANNELS = ['whatsapp', 'email'];

    public function run(): void
    {
        $truncate = (bool) env('LEADS_SEED_TRUNCATE', false);

        if ($truncate) {
            $this->truncateLeadTables();
        }

        $faker = \Faker\Factory::create('en_ZA');

        Dealer::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(50, function ($dealers) use ($faker) {
                foreach ($dealers as $dealer) {
                    $this->seedDealer($dealer, $faker);
                }
            });
    }

    private function seedDealer(Dealer $dealer, \Faker\Generator $faker): void
    {
        // Ensure baseline “real world” pipeline setup per dealer
        $pipelines = $this->ensureDealerPipelines($dealer);

        $defaultPipeline = $pipelines['Sales Pipeline'];
        $defaultStages = $pipelines['_stages_by_pipeline'][$defaultPipeline->id];

        $dealerUsers = DealerUser::query()
            ->where('dealer_id', $dealer->id)
            ->whereNull('deleted_at')
            ->get();

        $branches = DealerBranch::query()
            ->where('dealer_id', $dealer->id)
            ->whereNull('deleted_at')
            ->get();

        // Stock for this dealer
        $stockItems = Stock::query()
            ->whereIn('branch_id', $branches->pluck('id')->all())
            ->whereNull('deleted_at')
            ->get();

        // If there’s no stock, we still create some leads/conversations to test dealer-only edge cases.
        if ($stockItems->isEmpty()) {
            $this->seedDealerWithoutStock($dealer, $branches, $dealerUsers, $defaultPipeline, $defaultStages, $faker);
            return;
        }

        // 1) For each stock item: generate 10s to 100s of leads + conversations/messages
        foreach ($stockItems as $stock) {
            $branch = $branches->firstWhere('id', $stock->branch_id);

            // leads per stock
            $leadCount = random_int(self::MIN_LEADS_PER_STOCK, self::MAX_LEADS_PER_STOCK);

            for ($i = 0; $i < $leadCount; $i++) {
                $lead = $this->createLeadForStock(
                    dealer: $dealer,
                    branch: $branch,
                    stock: $stock,
                    dealerUsers: $dealerUsers,
                    pipeline: $defaultPipeline,
                    stages: $defaultStages,
                    faker: $faker
                );

                // some leads will have zero conversations (edge case)
                if ($this->roll(self::RATIO_LEADS_WITHOUT_CONVERSATION)) {
                    continue;
                }

                // 0..2 conversations per lead
                $convCount = random_int(0, 2);
                for ($c = 0; $c < $convCount; $c++) {
                    $conversation = $this->createConversation(
                        dealer: $dealer,
                        branch: $branch,
                        stock: $stock,
                        lead: $lead,
                        faker: $faker
                    );

                    $this->maybeCreateMessagesForConversation(
                        dealer: $dealer,
                        branch: $branch,
                        stock: $stock,
                        lead: $lead,
                        conversation: $conversation,
                        dealerUsers: $dealerUsers,
                        faker: $faker
                    );
                }
            }
        }

        // 2) Create some leads WITHOUT stock (unassigned / AI must classify later)
        $extraLeads = random_int(25, 120);
        for ($i = 0; $i < $extraLeads; $i++) {
            if (!$this->roll(self::RATIO_LEADS_WITHOUT_STOCK)) {
                continue;
            }

            $branch = $branches->isNotEmpty() ? $branches->random() : null;

            $lead = $this->createLeadWithoutStock(
                dealer: $dealer,
                branch: $branch,
                dealerUsers: $dealerUsers,
                pipeline: $defaultPipeline,
                stages: $defaultStages,
                faker: $faker
            );

            if ($this->roll(self::RATIO_LEADS_WITHOUT_CONVERSATION)) {
                continue;
            }

            // 1 conversation typically for these
            $conversation = $this->createConversation(
                dealer: $dealer,
                branch: $branch,
                stock: null,
                lead: $lead,
                faker: $faker
            );

            $this->maybeCreateMessagesForConversation(
                dealer: $dealer,
                branch: $branch,
                stock: null,
                lead: $lead,
                conversation: $conversation,
                dealerUsers: $dealerUsers,
                faker: $faker
            );
        }

        // 3) Dealer inbox conversations WITHOUT lead (billing edge case)
        $orphanConversations = random_int(10, 60);
        for ($i = 0; $i < $orphanConversations; $i++) {
            if (!$this->roll(self::RATIO_CONVERSATIONS_WITHOUT_LEAD)) {
                continue;
            }

            $branch = $branches->isNotEmpty() ? $branches->random() : null;

            $conversation = $this->createConversation(
                dealer: $dealer,
                branch: $branch,
                stock: null,
                lead: null,
                faker: $faker
            );

            $this->maybeCreateMessagesForConversation(
                dealer: $dealer,
                branch: $branch,
                stock: null,
                lead: null,
                conversation: $conversation,
                dealerUsers: $dealerUsers,
                faker: $faker
            );
        }
    }

    private function seedDealerWithoutStock(
        Dealer $dealer,
               $branches,
               $dealerUsers,
        LeadPipeline $pipeline,
        $stages,
        \Faker\Generator $faker
    ): void {
        // Create a smaller dataset but still covers edge cases.
        $leadCount = random_int(40, 140);

        for ($i = 0; $i < $leadCount; $i++) {
            $branch = $branches->isNotEmpty() ? $branches->random() : null;

            $lead = $this->createLeadWithoutStock(
                dealer: $dealer,
                branch: $branch,
                dealerUsers: $dealerUsers,
                pipeline: $pipeline,
                stages: $stages,
                faker: $faker
            );

            if ($this->roll(self::RATIO_LEADS_WITHOUT_CONVERSATION)) {
                continue;
            }

            $conversation = $this->createConversation(
                dealer: $dealer,
                branch: $branch,
                stock: null,
                lead: $lead,
                faker: $faker
            );

            $this->maybeCreateMessagesForConversation(
                dealer: $dealer,
                branch: $branch,
                stock: null,
                lead: $lead,
                conversation: $conversation,
                dealerUsers: $dealerUsers,
                faker: $faker
            );
        }

        // Also add some dealer-only conversations with no lead
        $orphanConversations = random_int(10, 35);
        for ($i = 0; $i < $orphanConversations; $i++) {
            $conversation = $this->createConversation(
                dealer: $dealer,
                branch: $branches->isNotEmpty() ? $branches->random() : null,
                stock: null,
                lead: null,
                faker: $faker
            );

            $this->maybeCreateMessagesForConversation(
                dealer: $dealer,
                branch: null,
                stock: null,
                lead: null,
                conversation: $conversation,
                dealerUsers: $dealerUsers,
                faker: $faker
            );
        }
    }

    private function ensureDealerPipelines(Dealer $dealer): array
    {
        // “Real world” pipelines for a dealership CRM
        $pipelineNames = [
            'Sales Pipeline',
            'Test Drive & Follow-up',
            'Finance & Approval',
        ];

        $pipelines = [];
        $stagesByPipeline = [];

        foreach ($pipelineNames as $idx => $name) {
            /** @var LeadPipeline $pipeline */
            $pipeline = LeadPipeline::query()->firstOrCreate(
                [
                    'dealer_id' => (string) $dealer->id,
                    'name' => $name,
                ],
                [
                    'is_default' => $idx === 0,
                ]
            );

            // if multiple defaults exist from previous runs, keep at least one
            if ($idx === 0 && !$pipeline->is_default) {
                $pipeline->is_default = true;
                $pipeline->save();
            }

            $pipelines[$name] = $pipeline;
            $stagesByPipeline[$pipeline->id] = $this->ensureStagesForPipeline($dealer, $pipeline);
        }

        // ensure only one default pipeline per dealer
        LeadPipeline::query()
            ->where('dealer_id', $dealer->id)
            ->where('id', '!=', $pipelines['Sales Pipeline']->id)
            ->update(['is_default' => false]);

        $pipelines['_stages_by_pipeline'] = $stagesByPipeline;

        return $pipelines;
    }

    private function ensureStagesForPipeline(Dealer $dealer, LeadPipeline $pipeline)
    {
        $stages = match ($pipeline->name) {
            'Sales Pipeline' => [
                ['name' => 'New Inquiry', 'sort' => 10],
                ['name' => 'Contacted', 'sort' => 20],
                ['name' => 'Qualified', 'sort' => 30],
                ['name' => 'Negotiation', 'sort' => 40],
                ['name' => 'Deposit Paid', 'sort' => 50],
                ['name' => 'Sold (Won)', 'sort' => 90, 'is_terminal' => true, 'is_won' => true],
                ['name' => 'Lost', 'sort' => 95, 'is_terminal' => true, 'is_lost' => true],
            ],
            'Test Drive & Follow-up' => [
                ['name' => 'Booked Test Drive', 'sort' => 10],
                ['name' => 'Completed Test Drive', 'sort' => 20],
                ['name' => 'Follow-up Sent', 'sort' => 30],
                ['name' => 'Second Visit', 'sort' => 40],
                ['name' => 'Closed (Won)', 'sort' => 90, 'is_terminal' => true, 'is_won' => true],
                ['name' => 'Closed (Lost)', 'sort' => 95, 'is_terminal' => true, 'is_lost' => true],
            ],
            'Finance & Approval' => [
                ['name' => 'Finance Requested', 'sort' => 10],
                ['name' => 'Docs Received', 'sort' => 20],
                ['name' => 'Awaiting Approval', 'sort' => 30],
                ['name' => 'Approved', 'sort' => 40],
                ['name' => 'Declined', 'sort' => 95, 'is_terminal' => true, 'is_lost' => true],
                ['name' => 'Paid Out (Won)', 'sort' => 90, 'is_terminal' => true, 'is_won' => true],
            ],
            default => [
                ['name' => 'New', 'sort' => 10],
                ['name' => 'Closed', 'sort' => 90, 'is_terminal' => true],
            ],
        };

        $out = collect();

        foreach ($stages as $s) {
            /** @var LeadStage $stage */
            $stage = LeadStage::query()->firstOrCreate(
                [
                    'dealer_id' => (string) $dealer->id,
                    'pipeline_id' => (string) $pipeline->id,
                    'name' => $s['name'],
                ],
                [
                    'sort_order' => $s['sort'] ?? 0,
                    'is_terminal' => (bool) ($s['is_terminal'] ?? false),
                    'is_won' => (bool) ($s['is_won'] ?? false),
                    'is_lost' => (bool) ($s['is_lost'] ?? false),
                ]
            );

            // keep sort flags consistent if rerun
            $stage->sort_order = $s['sort'] ?? $stage->sort_order;
            $stage->is_terminal = (bool) ($s['is_terminal'] ?? $stage->is_terminal);
            $stage->is_won = (bool) ($s['is_won'] ?? $stage->is_won);
            $stage->is_lost = (bool) ($s['is_lost'] ?? $stage->is_lost);
            $stage->save();

            $out->push($stage);
        }

        return $out->sortBy('sort_order')->values();
    }

    private function createLeadForStock(
        Dealer $dealer,
        ?DealerBranch $branch,
        Stock $stock,
        $dealerUsers,
        LeadPipeline $pipeline,
        $stages,
        \Faker\Generator $faker
    ): Lead {
        $stage = $stages->random();

        $assignedUserId = null;
        if ($dealerUsers->isNotEmpty() && $this->roll(0.75)) {
            $assignedUserId = $dealerUsers->random()->id;
        }

        $channel = self::CHANNELS[array_rand(self::CHANNELS)];

        $lead = Lead::query()->create([
            'dealer_id' => (string) $dealer->id,
            'branch_id' => $branch?->id ? (string) $branch->id : null,
            'stock_id' => (string) $stock->id,

            'assigned_to_dealer_user_id' => $assignedUserId ? (string) $assignedUserId : null,

            'pipeline_id' => (string) $pipeline->id,
            'stage_id' => (string) $stage->id,

            'firstname' => $faker->firstName,
            'lastname' => $faker->lastName,
            'email' => $faker->safeEmail,
            'contact_no' => $this->randomZaPhone($faker),

            'reference' => 'LD-' . strtoupper(Str::random(8)),
            'source' => $faker->randomElement(['website_form', 'whatsapp', 'email', 'call', 'walk_in']),
            'channel' => $channel,
            'status' => $faker->randomElement(['open', 'open', 'open', 'closed']),
        ]);

        // optional: stage event history entry (basic)
        LeadStageEvent::query()->create([
            'lead_id' => (string) $lead->id,
            'from_stage_id' => null,
            'to_stage_id' => (string) $stage->id,
            'changed_by_dealer_user_id' => $assignedUserId ? (string) $assignedUserId : null,
            'reason' => 'seed',
            'meta' => ['seeded' => true],
        ]);

        return $lead;
    }

    private function createLeadWithoutStock(
        Dealer $dealer,
        ?DealerBranch $branch,
        $dealerUsers,
        LeadPipeline $pipeline,
        $stages,
        \Faker\Generator $faker
    ): Lead {
        $stage = $stages->random();

        $assignedUserId = null;
        if ($dealerUsers->isNotEmpty() && $this->roll(0.50)) {
            $assignedUserId = $dealerUsers->random()->id;
        }

        $channel = self::CHANNELS[array_rand(self::CHANNELS)];

        $lead = Lead::query()->create([
            'dealer_id' => (string) $dealer->id,
            'branch_id' => $branch?->id ? (string) $branch->id : null,
            'stock_id' => null,

            'assigned_to_dealer_user_id' => $assignedUserId ? (string) $assignedUserId : null,

            'pipeline_id' => (string) $pipeline->id,
            'stage_id' => (string) $stage->id,

            'firstname' => $faker->firstName,
            'lastname' => $faker->lastName,
            'email' => $faker->boolean(75) ? $faker->safeEmail : null,
            'contact_no' => $faker->boolean(80) ? $this->randomZaPhone($faker) : null,

            'reference' => 'LD-' . strtoupper(Str::random(8)),
            'source' => $faker->randomElement(['whatsapp', 'email', 'call', 'website_form']),
            'channel' => $channel,
            'status' => $faker->randomElement(['open', 'open', 'closed']),
        ]);

        LeadStageEvent::query()->create([
            'lead_id' => (string) $lead->id,
            'from_stage_id' => null,
            'to_stage_id' => (string) $stage->id,
            'changed_by_dealer_user_id' => $assignedUserId ? (string) $assignedUserId : null,
            'reason' => 'seed',
            'meta' => ['seeded' => true, 'unassigned_stock' => true],
        ]);

        return $lead;
    }

    private function createConversation(
        Dealer $dealer,
        ?DealerBranch $branch,
        ?Stock $stock,
        ?Lead $lead,
        \Faker\Generator $faker
    ): LeadConversation {
        $channel = self::CHANNELS[array_rand(self::CHANNELS)];

        // Create channel thread (polymorphic)
        if ($channel === 'whatsapp') {
            $thread = WhatsappThread::query()->create([
                'twilio_conversation_sid' => 'CH' . strtoupper(Str::random(32)),
                'twilio_from' => 'whatsapp:' . $this->randomZaPhone($faker),
                'twilio_to' => 'whatsapp:' . $this->randomZaPhone($faker),
                'customer_wa' => $this->randomZaPhone($faker),
                'dealer_wa' => $this->randomZaPhone($faker),
                'meta' => [
                    'seed' => true,
                    'profile' => $faker->name,
                ],
            ]);

            $channelableType = WhatsappThread::class;
            $channelableId = $thread->id;
            $subject = null;
            $participant = $thread->customer_wa;
            $externalRef = $thread->twilio_conversation_sid;
        } else {
            $thread = EmailThread::query()->create([
                'thread_external_id' => 'thr_' . strtolower(Str::random(18)),
                'from_email' => $faker->safeEmail,
                'to_email' => $faker->safeEmail,
                'cc' => $faker->boolean(20) ? [$faker->safeEmail] : [],
                'subject' => $faker->sentence(6),
                'meta' => ['seed' => true],
            ]);

            $channelableType = EmailThread::class;
            $channelableId = $thread->id;
            $subject = $thread->subject;
            $participant = $thread->from_email;
            $externalRef = $thread->thread_external_id;
        }

        return LeadConversation::query()->create([
            'dealer_id' => (string) $dealer->id,
            'lead_id' => $lead?->id ? (string) $lead->id : null,
            'branch_id' => $branch?->id ? (string) $branch->id : null,
            'stock_id' => $stock?->id ? (string) $stock->id : null,

            'channel' => $channel,
            'status' => $faker->randomElement(['open', 'open', 'open', 'closed']),

            'subject' => $subject,
            'external_ref' => $externalRef,
            'participant' => $participant,

            'channelable_type' => $channelableType,
            'channelable_id' => (string) $channelableId,

            'last_message_id' => null,
            'last_message_at' => null,
        ]);
    }

    private function maybeCreateMessagesForConversation(
        Dealer $dealer,
        ?DealerBranch $branch,
        ?Stock $stock,
        ?Lead $lead,
        LeadConversation $conversation,
        $dealerUsers,
        \Faker\Generator $faker
    ): void {
        // Some conversations have no messages (edge case)
        if ($this->roll(self::RATIO_CONVERSATIONS_WITHOUT_MESSAGES)) {
            return;
        }

        $messageCount = random_int(1, self::MAX_MESSAGES_PER_CONVERSATION);

        $lastMessageId = null;
        $lastMessageAt = null;

        // Seed a “timeline” so sent_at makes sense
        $start = now()->subDays(random_int(0, 60))->subMinutes(random_int(0, 600));

        for ($i = 0; $i < $messageCount; $i++) {
            $direction = $faker->boolean(55) ? 'inbound' : 'outbound';

            $createdByDealerUserId = null;
            if ($direction === 'outbound' && $dealerUsers->isNotEmpty() && $this->roll(0.80)) {
                $createdByDealerUserId = $dealerUsers->random()->id;
            }

            $sentAt = (clone $start)->addMinutes($i * random_int(1, 25));

            // Create channel message payload
            if ($conversation->channel === 'whatsapp') {
                $wm = WhatsappMessage::query()->create([
                    'twilio_message_sid' => 'SM' . strtoupper(Str::random(32)),
                    'from_wa' => $direction === 'inbound' ? $this->randomZaPhone($faker) : $this->randomZaPhone($faker),
                    'to_wa' => $direction === 'inbound' ? $this->randomZaPhone($faker) : $this->randomZaPhone($faker),
                    'type' => 'text',
                    'media' => [],
                    'raw_payload' => [
                        'seed' => true,
                        'direction' => $direction,
                    ],
                    'pricing' => [
                        'category' => $faker->randomElement(['utility', 'marketing', 'authentication', 'service']),
                        'unit_price' => $faker->randomFloat(2, 0.10, 2.50),
                    ],
                ]);

                $messageableType = WhatsappMessage::class;
                $messageableId = $wm->id;

                $body = $faker->boolean(10)
                    ? "Hi, is this still available?\nRef: " . ($stock?->internal_reference ?? 'N/A')
                    : $faker->sentences(random_int(1, 3), true);
            } else {
                $em = EmailMessage::query()->create([
                    'message_id_header' => '<' . Str::uuid() . '@example.test>',
                    'in_reply_to' => $faker->boolean(40) ? '<' . Str::uuid() . '@example.test>' : null,
                    'references' => [],
                    'from_email' => $faker->safeEmail,
                    'to_email' => [$faker->safeEmail],
                    'cc' => $faker->boolean(20) ? [$faker->safeEmail] : [],
                    'bcc' => [],
                    'subject' => $faker->sentence(6),
                    'attachments' => [],
                    'raw_headers' => ['seed' => true],
                    'raw_payload' => $faker->paragraphs(random_int(1, 2), true),
                ]);

                $messageableType = EmailMessage::class;
                $messageableId = $em->id;

                $body = $faker->paragraphs(random_int(1, 2), true);
            }

            $msg = LeadMessage::query()->create([
                'dealer_id' => (string) $dealer->id,
                'conversation_id' => (string) $conversation->id,

                'lead_id' => $lead?->id ? (string) $lead->id : null,
                'branch_id' => $branch?->id ? (string) $branch->id : null,
                'stock_id' => $stock?->id ? (string) $stock->id : null,

                'channel' => $conversation->channel,
                'direction' => $direction,

                'body' => $body,
                'body_html' => null,
                'preview' => Str::limit(preg_replace("/\s+/", " ", (string) $body), 140),

                'status' => $faker->randomElement(['sent', 'delivered', 'read', 'failed']),
                'sent_at' => $sentAt,
                'delivered_at' => $faker->boolean(80) ? (clone $sentAt)->addMinutes(random_int(0, 15)) : null,
                'read_at' => $faker->boolean(60) ? (clone $sentAt)->addMinutes(random_int(1, 60)) : null,

                'error_code' => null,
                'error_message' => null,

                'created_by_dealer_user_id' => $createdByDealerUserId ? (string) $createdByDealerUserId : null,

                'messageable_type' => $messageableType,
                'messageable_id' => (string) $messageableId,
            ]);

            $lastMessageId = $msg->id;
            $lastMessageAt = $sentAt;
        }

        // update conversation pointers
        if ($lastMessageId) {
            $conversation->last_message_id = $lastMessageId;
            $conversation->last_message_at = $lastMessageAt;
            $conversation->save();
        }
    }

    private function truncateLeadTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // child -> parent order
        DB::table('lead_stage_events')->truncate();
        DB::table('lead_messages')->truncate();
        DB::table('lead_conversations')->truncate();

        DB::table('whatsapp_messages')->truncate();
        DB::table('email_messages')->truncate();
        DB::table('whatsapp_threads')->truncate();
        DB::table('email_threads')->truncate();

        DB::table('leads')->truncate();
        DB::table('lead_stages')->truncate();
        DB::table('lead_pipelines')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function roll(float $probability): bool
    {
        $probability = max(0.0, min(1.0, $probability));
        return mt_rand() / mt_getrandmax() <= $probability;
    }

    private function randomZaPhone(\Faker\Generator $faker): string
    {
        // E.164-ish ZA mobile (not perfect but consistent)
        // e.g. +2782xxxxxxx
        return '+27' . $faker->numberBetween(60, 89) . $faker->numerify('#######');
    }
}

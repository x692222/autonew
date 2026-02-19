<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'

defineOptions({ layout: Layout })

defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    dealer: { type: Object, required: true },
    pageTab: { type: String, default: 'billings' },
})
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
    </div>

    <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

    <q-card flat bordered>
        <q-card-section>
            <div class="text-h6 q-pb-sm">Billings</div>
            <div class="text-body2 text-grey-8">Dealer billings tab placeholder.</div>
        </q-card-section>
    </q-card>
</template>

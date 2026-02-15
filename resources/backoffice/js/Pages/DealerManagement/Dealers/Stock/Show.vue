<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/DealerManagement/Dealers/_Tabs.vue'
import StockShowCard from 'bo@/Components/Stock/StockShowCard.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    dealer: { type: Object, required: true },
    stock: { type: Object, required: true },
    totalImagesCount: { type: Number, default: 0 },
})
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">Dealer Management</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
    </div>

    <DealerTabs page-tab="stock" :dealer-id="dealer.id" />

    <StockShowCard
        public-title="View Stock"
        :dealer="dealer"
        :stock="stock"
        :total-images-count="totalImagesCount"
        :back-url="route('backoffice.dealer-management.dealers.stock', dealer.id)"
        :edit-url="route('backoffice.dealer-management.dealers.stock.edit', [dealer.id, stock.id])"
    />
</template>

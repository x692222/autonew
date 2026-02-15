<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerConfigurationNav from 'bo@/Pages/DealerConfiguration/_Nav.vue'
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
            <div class="text-h5 text-weight-regular text-grey-9">Configuration</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
    </div>

    <DealerConfigurationNav tab="stock" />

    <StockShowCard
        public-title="View Stock"
        :dealer="dealer"
        :stock="stock"
        :total-images-count="totalImagesCount"
        :back-url="route('backoffice.dealer-configuration.stock.index')"
        :edit-url="route('backoffice.dealer-configuration.stock.edit', stock.id)"
    />
</template>

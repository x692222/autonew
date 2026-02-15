<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerConfigurationNav from 'bo@/Pages/DealerConfiguration/_Nav.vue'
import StockEditForm from 'bo@/Components/Stock/StockEditForm.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    dealer: { type: Object, required: true },
    stock: { type: Object, required: true },
    canEditReference: { type: Boolean, required: true },
    branches: { type: Array, required: true },
    makes: { type: Array, required: true },
    vehicleModelsByMakeId: { type: Object, required: true },
    featureOptions: { type: Array, required: true },
    enumOptions: { type: Object, required: true },
    totalImagesCount: { type: Number, default: 0 },
    routeNames: { type: Object, required: true },
    returnTo: { type: String, required: true },
    currencySymbol: { type: String, default: 'N$' },
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

    <StockEditForm
        :dealer="dealer"
        :stock="stock"
        :can-edit-reference="canEditReference"
        :branches="branches"
        :makes="makes"
        :vehicle-models-by-make-id="vehicleModelsByMakeId"
        :feature-options="featureOptions"
        :enum-options="enumOptions"
        :total-images-count="totalImagesCount"
        :route-names="routeNames"
        :route-params="[]"
        :return-to="returnTo"
        :currency-symbol="currencySymbol"
    />
</template>

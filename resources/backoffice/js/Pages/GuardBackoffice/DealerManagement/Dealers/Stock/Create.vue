<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import StockCreateForm from 'bo@/Components/Stock/StockCreateForm.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Create Stock' },
    pageTab: { type: String, default: 'stock' },
    dealer: { type: Object, required: true },
    returnTo: { type: String, required: true },
    branches: { type: Array, required: true },
    typeOptions: { type: Array, required: true },
    typeMeta: { type: Object, required: true },
    makesByType: { type: Object, required: true },
    vehicleModelsByMakeId: { type: Object, required: true },
    featureTagsByType: { type: Object, required: true },
    enumOptions: { type: Object, required: true },
    currencySymbol: { type: String, default: 'N$' },
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

    <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

    <StockCreateForm
        :dealer="dealer"
        :return-to="returnTo"
        :route-names="{ store: 'backoffice.dealer-management.dealers.stock.store' }"
        :route-params="[dealer.id]"
        :branches="branches"
        :type-options="typeOptions"
        :type-meta="typeMeta"
        :makes-by-type="makesByType"
        :vehicle-models-by-make-id="vehicleModelsByMakeId"
        :feature-tags-by-type="featureTagsByType"
        :enum-options="enumOptions"
        :currency-symbol="currencySymbol"
    />
</template>

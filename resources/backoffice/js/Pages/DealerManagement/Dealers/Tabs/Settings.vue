<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/DealerManagement/Dealers/_Tabs.vue'
import SettingsForm from 'bo@/Components/Settings/SettingsForm.vue'

defineOptions({ layout: Layout })

defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    dealer: { type: Object, required: true },
    pageTab: { type: String, default: 'settings' },
    settings: { type: Array, default: () => [] },
    timezoneOptions: { type: Array, default: () => [] },
    stockTypeOptions: { type: Array, default: () => [] },
    updateRoute: { type: String, required: true },
    canUpdate: { type: Boolean, default: false },
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

    <SettingsForm
        :settings="settings"
        :timezone-options="timezoneOptions"
        :stock-type-options="stockTypeOptions"
        :update-route="updateRoute"
        :can-update="canUpdate"
        :show-backoffice-only-badge="true"
    />
</template>

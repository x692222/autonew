<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import SettingsForm from 'bo@/Components/Settings/SettingsForm.vue'

defineOptions({ layout: Layout })

defineProps({
    publicTitle: { type: String, default: 'System Configuration' },
    settings: { type: Array, default: () => [] },
    timezoneOptions: { type: Array, default: () => [] },
    updateRoute: { type: String, required: true },
    canUpdate: { type: Boolean, default: false },
})
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
        </div>
    </div>

    <SettingsForm
        :settings="settings"
        :timezone-options="timezoneOptions"
        :update-route="updateRoute"
        :can-update="canUpdate"
    />
</template>

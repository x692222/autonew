<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'

defineOptions({ layout: Layout })

defineProps({
    publicTitle: { type: String, default: 'Configuration' },
    dealer: { type: Object, required: true },
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

    <DealerConfigurationNav tab="edit-dealership" />

    <q-card flat bordered>
        <q-card-section>
            <div class="text-h6 q-pb-sm">Edit Dealership</div>
            <div class="text-body2 text-grey-8">This page will manage dealership details for the logged-in dealer context.</div>
        </q-card-section>
    </q-card>
</template>

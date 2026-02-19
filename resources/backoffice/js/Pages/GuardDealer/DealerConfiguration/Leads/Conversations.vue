<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import LeadTabs from 'bo@/Components/Leads/LeadTabs.vue'

defineOptions({ layout: Layout })

const props = defineProps({ publicTitle: { type: String, default: 'Lead Conversations' }, dealer: { type: Object, required: true }, lead: { type: Object, required: true }, returnTo: { type: String, default: '' } })

const tabRoutes = {
  overview: 'backoffice.dealer-configuration.leads.overview',
  conversations: 'backoffice.dealer-configuration.leads.conversations',
  'stage-history': 'backoffice.dealer-configuration.leads.stage-history',
}
</script>

<template>
  <Head><title>{{ $page.props.appName }}</title></Head>
  <div class="row nowrap justify-between items-center q-mb-md"><div><div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div><div class="text-caption text-grey-7">{{ dealer.name }}</div></div></div>
  <LeadTabs :page-tab="'conversations'" :lead-id="lead.id" :routes="tabRoutes" :route-params="{ return_to: returnTo }" />
  <q-card flat bordered><q-card-section><div class="text-body1">Conversations will be implemented in a later task.</div></q-card-section></q-card>
</template>

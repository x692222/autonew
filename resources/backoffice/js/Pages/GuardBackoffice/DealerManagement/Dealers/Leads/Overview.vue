<script setup>
import { Head, router } from '@inertiajs/vue3'
import { inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import LeadTabs from 'bo@/Components/Leads/LeadTabs.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const route = inject('route')

const props = defineProps({
  publicTitle: { type: String, default: 'Lead Overview' },
  dealer: { type: Object, required: true },
  lead: { type: Object, required: true },
  returnTo: { type: String, default: '' },
})

const loading = ref(false)
const notesRef = ref(null)
const { confirmAction } = useConfirmAction(loading)

const tabRoutes = {
  overview: 'backoffice.dealer-management.dealers.leads.overview',
  conversations: 'backoffice.dealer-management.dealers.leads.conversations',
  'stage-history': 'backoffice.dealer-management.dealers.leads.stage-history',
}

const withDealerLead = (name) => route(name, { dealer: props.dealer.id, lead: props.lead.id, return_to: props.returnTo })

const goBack = () => {
  router.visit(props.returnTo || route('backoffice.dealer-management.dealers.leads', props.dealer.id))
}
</script>

<template>
  <Head><title>{{ $page.props.appName }}</title></Head>

  <div class="row nowrap justify-between items-center q-mb-md">
    <div>
      <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
      <div class="text-caption text-grey-7">{{ dealer.name }}</div>
    </div>

    <div class="q-gutter-sm">
      <q-btn color="grey-4" text-color="standard" label="Back" no-wrap unelevated @click="goBack" />
      <q-btn v-if="lead.can?.show_notes" color="primary" icon="sticky_note_2" label="Notes" no-wrap unelevated @click="notesRef?.open(lead)" />
      <q-btn v-if="lead.can?.edit" color="primary" label="Edit" no-wrap unelevated @click="router.visit(withDealerLead('backoffice.dealer-management.dealers.leads.edit'))" />
      <q-btn
        v-if="lead.can?.delete"
        color="negative"
        label="Delete"
        no-wrap
        unelevated
        :loading="loading"
        :disable="loading"
        @click="confirmAction({ title: 'Delete Lead', message: 'Are you sure you want to delete this lead?', okLabel: 'Delete', okColor: 'negative', cancelLabel: 'Cancel', method: 'delete', actionUrl: withDealerLead('backoffice.dealer-management.dealers.leads.destroy') })"
      />
    </div>
  </div>

  <LeadTabs :page-tab="'overview'" :lead-id="lead.id" :routes="tabRoutes" :route-params="{ dealer: dealer.id, return_to: returnTo }" />

  <q-card flat bordered>
    <q-card-section>
      <div class="text-h6 q-pb-sm">Lead Details</div>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <div><span class="text-weight-medium">First name:</span> {{ lead.firstname || '-' }}</div>
          <div><span class="text-weight-medium">Last name:</span> {{ lead.lastname || '-' }}</div>
          <div><span class="text-weight-medium">Email:</span> {{ lead.email || '-' }}</div>
          <div><span class="text-weight-medium">Contact:</span> {{ lead.contact_no || '-' }}</div>
        </div>
        <div class="col-12 col-md-6">
          <div><span class="text-weight-medium">Source:</span> {{ lead.source || '-' }}</div>
          <div><span class="text-weight-medium">Status:</span> {{ lead.status || '-' }}</div>
          <div><span class="text-weight-medium">Correspondence Language:</span> {{ lead.correspondence_language || '-' }}</div>
          <div><span class="text-weight-medium">Registration Date:</span> {{ lead.registration_date || '-' }}</div>
          <div><span class="text-weight-medium">Assigned to:</span> {{ lead.assigned_to || '-' }}</div>
          <div><span class="text-weight-medium">Branch:</span> {{ lead.branch || '-' }}</div>
        </div>
      </div>

      <q-separator class="q-my-md" />

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6"><span class="text-weight-medium">Pipeline:</span> {{ lead.pipeline || '-' }}</div>
        <div class="col-12 col-md-6"><span class="text-weight-medium">Stage:</span> {{ lead.stage || '-' }}</div>
      </div>

      <q-separator class="q-my-md" />

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4"><span class="text-weight-medium">Notes:</span> {{ lead.counts?.notes ?? 0 }}</div>
        <div class="col-12 col-md-4"><span class="text-weight-medium">Conversations:</span> {{ lead.counts?.conversations ?? 0 }}</div>
        <div class="col-12 col-md-4"><span class="text-weight-medium">Stage events:</span> {{ lead.counts?.stage_events ?? 0 }}</div>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-subtitle1 q-mb-sm">Associated Stock</div>
      <div v-if="!(lead.stock_items || []).length" class="text-grey-7">No stock linked.</div>
      <div v-else class="q-gutter-sm">
        <q-card v-for="item in lead.stock_items" :key="item.id" flat bordered class="q-pa-sm">
          <div class="row items-center justify-between">
            <div class="text-weight-medium">{{ item.name || '-' }}</div>
            <div class="row items-center q-gutter-xs">
              <span class="text-caption">Live</span>
              <q-icon :name="item.is_live ? 'check_circle' : 'cancel'" :color="item.is_live ? 'positive' : 'negative'" size="18px" />
            </div>
          </div>
          <div class="text-caption text-grey-7">{{ item.internal_reference || '-' }}</div>
        </q-card>
      </div>
    </q-card-section>
  </q-card>

  <NotesHost ref="notesRef" noteable-type="lead" :title-fn="row => `${row.firstname || ''} ${row.lastname || ''}`.trim() || `Lead ${row.id}`" />
</template>

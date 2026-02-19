<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import LeadTabs from 'bo@/Components/Leads/LeadTabs.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  publicTitle: { type: String, default: 'Lead Stage History' },
  dealer: { type: Object, required: true },
  lead: { type: Object, required: true },
  records: { type: Object, required: true },
  returnTo: { type: String, default: '' },
})

const loading = ref(false)

const tabRoutes = {
  overview: 'backoffice.dealer-management.dealers.leads.overview',
  conversations: 'backoffice.dealer-management.dealers.leads.conversations',
  'stage-history': 'backoffice.dealer-management.dealers.leads.stage-history',
}

const gotoPage = (page) => {
  loading.value = true
  router.get(
    route('backoffice.dealer-management.dealers.leads.stage-history', { dealer: props.dealer.id, lead: props.lead.id }),
    { page, rowsPerPage: props.records?.per_page || 25 },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['records', 'filters', 'flash'],
      onFinish: () => { loading.value = false },
    }
  )
}
</script>

<template>
  <Head><title>{{ $page.props.appName }}</title></Head>

  <div class="row nowrap justify-between items-center q-mb-md">
    <div>
      <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
      <div class="text-caption text-grey-7">{{ dealer.name }}</div>
    </div>
  </div>

  <LeadTabs :page-tab="'stage-history'" :lead-id="lead.id" :routes="tabRoutes" :route-params="{ dealer: dealer.id, return_to: returnTo }" />

  <q-card flat bordered>
    <q-card-section>
      <q-timeline color="grey-8">
        <q-timeline-entry
          v-for="event in (records.data || [])"
          :key="event.id"
          :title="`${event.from || '-'} → ${event.to || '-'}`"
          :subtitle="event.created_at"
        >
          <div v-if="event.changed_by"><span class="text-weight-medium">Changed by:</span> {{ event.changed_by }}</div>
          <div v-if="event.reason"><span class="text-weight-medium">Reason:</span> {{ event.reason }}</div>
          <pre v-if="event.meta" class="q-mt-sm">{{ JSON.stringify(event.meta, null, 2) }}</pre>
        </q-timeline-entry>
      </q-timeline>

      <div class="row justify-end q-mt-md">
        <q-pagination
          v-if="records?.last_page > 1"
          :model-value="records.current_page"
          :max="records.last_page"
          direction-links
          boundary-links
          :disable="loading"
          @update:model-value="gotoPage"
        />
      </div>
    </q-card-section>
  </q-card>
</template>

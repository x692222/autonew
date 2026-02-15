<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/DealerManagement/Dealers/_Tabs.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  publicTitle: { type: String, default: 'Dealer Management' },
  dealer: { type: Object, required: true },
  pageTab: { type: String, default: 'leads' },
  returnTo: { type: String, default: '' },
  options: { type: Object, default: () => ({}) },
})

const form = useForm({
  return_to: props.returnTo || '',
  branch_id: '',
  assigned_to_dealer_user_id: '',
  pipeline_id: '',
  stage_id: '',
  firstname: '',
  lastname: '',
  email: '',
  contact_no: '',
  source: '',
  status: '',
})

const stageOptions = computed(() => {
  if (!form.pipeline_id) return props.options?.stages || []
  return (props.options?.stages || []).filter((item) => !item.pipeline_id || String(item.pipeline_id) === String(form.pipeline_id))
})

const onPipelineChange = () => {
  form.stage_id = ''
}

const submit = () => {
  form.post(route('backoffice.dealer-management.dealers.leads.store', props.dealer.id), { preserveScroll: true })
}

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
    <q-btn color="grey-4" text-color="standard" label="Back" no-wrap unelevated @click="goBack" />
  </div>

  <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

  <q-card flat bordered>
    <q-card-section>
      <div class="text-h6 q-pb-lg">Create Lead</div>

      <q-form @submit.prevent="submit">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6"><q-input v-model="form.firstname" filled dense label="First name" :error="!!form.errors.firstname" :error-message="form.errors.firstname" /></div>
          <div class="col-12 col-md-6"><q-input v-model="form.lastname" filled dense label="Last name" :error="!!form.errors.lastname" :error-message="form.errors.lastname" /></div>
          <div class="col-12 col-md-6"><q-input v-model="form.email" filled dense label="Email" :error="!!form.errors.email" :error-message="form.errors.email" /></div>
          <div class="col-12 col-md-6"><q-input v-model="form.contact_no" filled dense label="Contact number" :error="!!form.errors.contact_no" :error-message="form.errors.contact_no" /></div>
          <div class="col-12 col-md-6"><q-select v-model="form.branch_id" filled dense emit-value map-options clearable label="Branch" :options="options.branches || []" :error="!!form.errors.branch_id" :error-message="form.errors.branch_id" /></div>
          <div class="col-12 col-md-6"><q-select v-model="form.assigned_to_dealer_user_id" filled dense emit-value map-options clearable label="Assigned to" :options="options.dealer_users || []" :error="!!form.errors.assigned_to_dealer_user_id" :error-message="form.errors.assigned_to_dealer_user_id" /></div>
          <div class="col-12 col-md-6"><q-select v-model="form.pipeline_id" filled dense emit-value map-options clearable label="Pipeline" :options="options.pipelines || []" :error="!!form.errors.pipeline_id" :error-message="form.errors.pipeline_id" @update:model-value="onPipelineChange" /></div>
          <div class="col-12 col-md-6"><q-select v-model="form.stage_id" filled dense emit-value map-options clearable label="Stage" :options="stageOptions" :error="!!form.errors.stage_id" :error-message="form.errors.stage_id" /></div>
          <div class="col-12 col-md-6"><q-select v-model="form.source" filled dense emit-value map-options clearable label="Source" :options="options.sources || []" :error="!!form.errors.source" :error-message="form.errors.source" /></div>
          <div class="col-12 col-md-6"><q-select v-model="form.status" filled dense emit-value map-options clearable label="Status" :options="options.statuses || []" :error="!!form.errors.status" :error-message="form.errors.status" /></div>
        </div>

        <div class="row justify-end q-mt-lg">
          <div class="q-gutter-sm">
            <q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="goBack" />
            <q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" />
          </div>
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

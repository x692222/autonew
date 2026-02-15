<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({ publicTitle: { type: String, default: 'Dealer Management' }, dealer: { type: Object, required: true }, returnTo: { type: String, default: '' }, pipelines: { type: Array, default: () => [] }, data: { type: Object, required: true } })

const form = useForm({
  return_to: props.returnTo || '',
  pipeline_id: props.data?.pipeline_id || '',
  name: props.data?.name || '',
  sort_order: props.data?.sort_order ?? 0,
  is_terminal: !!props.data?.is_terminal,
  is_won: !!props.data?.is_won,
  is_lost: !!props.data?.is_lost,
})

const submit = () => form.patch(route('backoffice.dealer-management.dealers.lead-stages.update', { dealer: props.dealer.id, leadStage: props.data.id }), { preserveScroll: true })
const goBack = () => router.visit(props.returnTo || route('backoffice.dealer-management.dealers.lead-stages.index', props.dealer.id))
</script>

<template>
  <Head><title>{{ $page.props.appName }}</title></Head>
  <div class="row nowrap justify-between items-center q-mb-md"><div><div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div><div class="text-caption text-grey-7">{{ dealer.name }}</div></div><q-btn color="grey-4" text-color="standard" label="Back" no-wrap unelevated @click="goBack" /></div>
  <q-card flat bordered><q-card-section>
    <div class="text-h6 q-pb-lg">Edit Lead Stage</div>
    <q-form @submit.prevent="submit">
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6"><q-select v-model="form.pipeline_id" filled dense emit-value map-options label="Pipeline" :options="pipelines || []" :error="!!form.errors.pipeline_id" :error-message="form.errors.pipeline_id" /></div>
        <div class="col-12 col-md-6"><q-input v-model="form.name" filled dense label="Stage name" :error="!!form.errors.name" :error-message="form.errors.name" /></div>
        <div class="col-12 col-md-6"><q-input v-model.number="form.sort_order" type="number" filled dense label="Sort order" :error="!!form.errors.sort_order" :error-message="form.errors.sort_order" /></div>
      </div>
      <div class="q-gutter-md q-mt-sm">
        <q-toggle v-model="form.is_terminal" label="Terminal stage" />
        <q-toggle v-model="form.is_won" label="Won stage" />
        <q-toggle v-model="form.is_lost" label="Lost stage" />
      </div>
      <div class="row justify-end q-mt-lg"><div class="q-gutter-sm"><q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="goBack" /><q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" /></div></div>
    </q-form>
  </q-card-section></q-card>
</template>

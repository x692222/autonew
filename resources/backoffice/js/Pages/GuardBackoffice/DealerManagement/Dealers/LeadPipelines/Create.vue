<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({ publicTitle: { type: String, default: 'Dealer Management' }, dealer: { type: Object, required: true }, returnTo: { type: String, default: '' } })

const form = useForm({ return_to: props.returnTo || '', name: '', is_default: false })

const submit = () => form.post(route('backoffice.dealer-management.dealers.lead-pipelines.store', props.dealer.id), { preserveScroll: true })
const goBack = () => router.visit(props.returnTo || route('backoffice.dealer-management.dealers.lead-pipelines.index', props.dealer.id))
</script>

<template>
  <Head><title>{{ $page.props.appName }}</title></Head>
  <div class="row nowrap justify-between items-center q-mb-md">
    <div><div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div><div class="text-caption text-grey-7">{{ dealer.name }}</div></div>
    <q-btn color="grey-4" text-color="standard" no-wrap unelevated label="Back" @click="goBack" />
  </div>
  <q-card flat bordered><q-card-section>
    <div class="text-h6 q-pb-lg">Create Lead Pipeline</div>
    <q-form @submit.prevent="submit">
      <q-input v-model="form.name" filled dense label="Name" maxlength="255" counter :error="!!form.errors.name" :error-message="form.errors.name" />
      <q-toggle v-model="form.is_default" label="Default Pipeline" class="q-mt-md" />
      <div class="row justify-end q-mt-lg"><div class="q-gutter-sm"><q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="goBack" /><q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" /></div></div>
    </q-form>
  </q-card-section></q-card>
</template>

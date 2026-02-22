<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  publicTitle: { type: String, default: 'Dealer Management' },
  dealer: { type: Object, required: true },
  pageTab: { type: String, default: 'users' },
  returnTo: { type: String, default: '' },
})

const form = useForm({
  return_to: props.returnTo || '',
  firstname: '',
  lastname: '',
  email: '',
})

const submit = () => {
  form.post(route('backoffice.dealer-management.dealers.users.store', props.dealer.id), { preserveScroll: true })
}

const cancel = () => {
  router.visit(props.returnTo || route('backoffice.dealer-management.dealers.users', props.dealer.id))
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

  <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

  <q-card flat bordered>
    <q-card-section>
      <div class="text-h6 q-pb-lg">Create Dealer User</div>
      <q-form @submit.prevent="submit">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6"><q-input v-model="form.firstname" filled dense label="First name" maxlength="50" counter :error="!!form.errors.firstname" :error-message="form.errors.firstname" /></div>
          <div class="col-12 col-md-6"><q-input v-model="form.lastname" filled dense label="Last name" maxlength="50" counter :error="!!form.errors.lastname" :error-message="form.errors.lastname" /></div>
          <div class="col-12 col-md-6"><q-input v-model="form.email" filled dense label="Email" maxlength="150" counter :error="!!form.errors.email" :error-message="form.errors.email" /></div>
        </div>
        <div class="row justify-end q-mt-lg"><div class="q-gutter-sm"><q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="cancel" /><q-btn color="primary" label="Save" no-wrap unelevated :loading="form.processing" :disable="form.processing" @click="submit" /></div></div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

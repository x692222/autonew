<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const route = inject('route')

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    returnTo: { type: String, default: '' },
    data: { type: Object, required: true },
})

const form = useForm({
    return_to: props.returnTo || '',
    name: props.data?.name ?? '',
})

const submit = () => {
    form.patch(route('backoffice.dealer-management.dealers.update', props.data.id), {
        preserveScroll: true,
    })
}

const cancel = () => {
    router.visit(props.returnTo || route('backoffice.dealer-management.dealers.index'))
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center">
        <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>

        <q-btn color="grey-4" text-color="standard" no-wrap unelevated
           
           
            label="Go Back"
           
           
            @click="cancel"
        />
    </div>

    <q-card flat bordered class="q-mt-md">
        <q-card-section>
            <div class="text-h6 q-pb-lg">Edit Dealer</div>

            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12">
                        <q-input
                            v-model="form.name"
                            label="Dealer name"
                            filled
                            dense
                            maxlength="255"
                            counter
                            :error="!!form.errors.name"
                            :error-message="form.errors.name"
                            autocomplete="off"
                        />
                    </div>
                </div>

                <div class="row justify-end q-mt-lg">
                    <div class="q-gutter-sm">
                        <q-btn color="grey-4" text-color="standard" label="Cancel" no-wrap unelevated @click="cancel" />
                        <q-btn
                            color="primary"
                            label="Save"
                            no-wrap
                            unelevated
                            :loading="form.processing"
                            :disable="form.processing"
                            @click="submit"
                        />
                    </div>
                </div>
            </q-form>
        </q-card-section>
    </q-card>
</template>

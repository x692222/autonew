<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const route = inject('route')

defineProps({
    publicTitle: { type: String, default: 'User Management' },
})

const form = useForm({
    firstname: '',
    lastname: '',
    email: '',
    password: ''
})

const submit = () => {
    form.post(route('backoffice.system.user-management.users.store'), {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    })
}

const cancel = () => {
    router.visit(route('backoffice.system.user-management.users.index'))
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center">
        <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
    </div>

    <q-card flat bordered class="q-mt-md">
        <q-card-section>
            <div class="text-h6 q-pb-lg">Register User</div>

            <q-form @submit.prevent="submit">
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="form.firstname"
                            label="First name"
                            filled
                            dense
                            maxlength="50"
                            counter
                            :error="!!form.errors.firstname"
                            :error-message="form.errors.firstname"
                            autocomplete="off"
                        />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="form.lastname"
                            label="Last name"
                            filled
                            dense
                            maxlength="50"
                            counter
                            :error="!!form.errors.lastname"
                            :error-message="form.errors.lastname"
                            autocomplete="off"
                        />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="form.email"
                            type="email"
                            label="Email"
                            filled
                            dense
                            maxlength="150"
                            counter
                            :error="!!form.errors.email"
                            :error-message="form.errors.email"
                            :input-attrs="{ autocomplete: 'off' }"
                        />
                    </div>

                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="form.password"
                            type="password"
                            label="Password (optional)"
                            filled
                            dense
                            :error="!!form.errors.password"
                            :error-message="form.errors.password"
                            :input-attrs="{ autocomplete: 'new-password' }"
                        />
                    </div>
                </div>
            </q-form>

            <div class="row justify-end">
                <div class="q-gutter-sm">
                    <q-btn
                        color="grey-4"
                        text-color="standard"
                        label="Cancel"
                        no-wrap
                        unelevated
                        @click="cancel"
                    />
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
        </q-card-section>
    </q-card>
</template>

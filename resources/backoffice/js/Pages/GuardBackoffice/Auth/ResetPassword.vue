<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import { useQuasar } from 'quasar'

const route = inject('route')
const $q = useQuasar()

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
    submitRoute: { type: String, default: 'backoffice.auth.password.update' }
})

const form = useForm({
    token: props.token,
    email: props.email || '',
    password: '',
    password_confirmation: ''
})

const submit = () => {
    form.post(route(props.submitRoute), {
        preserveScroll: true,
        onSuccess: () => {
            $q.notify({ type: 'positive', message: 'Password updated. You can log in now.' })
        }
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="flex flex-center" style="min-height: 100vh;">
        <q-card flat bordered class="q-pa-lg" style="width: 460px; max-width: 92vw;">
            <div class="column items-center q-mb-md">
                <img src="/images/img.png" alt="Logo" style="max-width: 160px;" class="q-mb-md" />
                <div class="text-h6 text-grey-9">Reset password</div>
                <div class="text-caption text-grey-7 q-mt-xs">
                    Choose a new password for your account.
                </div>
            </div>

            <q-form @submit.prevent="submit">
                <q-input
                    v-model="form.email"
                    type="email"
                    label="Email"
                    filled
                    dense
                    maxlength="190"
                    counter
                    :error="!!form.errors.email"
                    :error-message="form.errors.email"
                    :input-attrs="{ autocomplete: 'username' }"
                    class="q-mb-sm"
                />

                <q-input
                    v-model="form.password"
                    type="password"
                    label="New password"
                    filled
                    dense
                    :error="!!form.errors.password"
                    :error-message="form.errors.password"
                    :input-attrs="{ autocomplete: 'new-password' }"
                    class="q-mb-sm"
                />

                <q-input
                    v-model="form.password_confirmation"
                    type="password"
                    label="Confirm new password"
                    filled
                    dense
                    :error="!!form.errors.password_confirmation"
                    :error-message="form.errors.password_confirmation"
                    :input-attrs="{ autocomplete: 'new-password' }"
                    class="q-mb-md"
                />

                <q-btn
                    color="primary"
                    label="Reset password"
                    no-wrap
                    unelevated
                    class="full-width"
                    type="submit"
                    :loading="form.processing"
                    :disable="form.processing"
                />
            </q-form>

            <div class="row justify-end q-mt-md">
                <Link :href="route('backoffice.auth.login.show')" class="text-primary">
                    Back to login
                </Link>
            </div>
        </q-card>
    </div>
</template>

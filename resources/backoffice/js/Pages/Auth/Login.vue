<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import { useQuasar } from 'quasar'

const route = inject('route')
const $q = useQuasar()
const page = usePage()

const status = computed(() => page.props.flash?.status || page.props.status || null)

const form = useForm({
    email: '',
    password: '',
    remember: false
})

const submit = () => {
    form.post(route('backoffice.auth.login.store'), {
        preserveScroll: true,
        onSuccess: () => {
        }
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="flex flex-center" style="min-height: 100vh;">
        <q-card flat bordered class="q-pa-lg" style="width: 420px; max-width: 92vw;">
            <div class="column items-center q-mb-md">
                <img src="/images/img.png" alt="Logo" style="max-width: 160px;" class="q-mb-md" />
                <div class="text-h6 text-grey-9">Backoffice Login</div>
                <div class="text-caption text-grey-7 q-mt-xs">Please sign in to continue.</div>
            </div>

            <q-banner
                v-if="status"
                dense
                class="q-mb-md bg-green-1 text-green-10"
                rounded
            >
                {{ status }}
            </q-banner>

            <q-form @submit.prevent="submit">
                <q-input
                    v-model="form.email"
                    type="email"
                    label="Email"
                    filled
                    dense
                    :error="!!form.errors.email"
                    :error-message="form.errors.email"
                    :input-attrs="{ autocomplete: 'username' }"
                    class="q-mb-sm"
                />

                <q-input
                    v-model="form.password"
                    type="password"
                    label="Password"
                    filled
                    dense
                    :error="!!form.errors.password"
                    :error-message="form.errors.password"
                    :input-attrs="{ autocomplete: 'current-password' }"
                    class="q-mb-sm"
                />

                <div class="row items-center justify-between q-mb-md">
                    <q-checkbox v-model="form.remember" label="Remember me" dense />
                    <Link :href="route('backoffice.auth.password.request')" class="text-primary">
                        Forgot password?
                    </Link>
                </div>

                <q-btn
                    color="primary"
                    label="Log in"
                    no-wrap
                    unelevated
                    class="full-width"
                    type="submit"
                    :loading="form.processing"
                    :disable="form.processing"
                />
            </q-form>
        </q-card>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import SettingsFields from 'bo@/Components/Settings/SettingsFields.vue'

const props = defineProps({
    settings: { type: Array, default: () => [] },
    updateRoute: { type: String, required: true },
    canUpdate: { type: Boolean, default: false },
    timezoneOptions: { type: Array, default: () => [] },
    stockTypeOptions: { type: Array, default: () => [] },
    showBackofficeOnlyBadge: { type: Boolean, default: false },
})

const initialSettings = Object.fromEntries((props.settings || []).map((row) => [row.key, row.value]))
const form = useForm({ settings: initialSettings })

const requiredKeys = computed(() => {
    const keys = (props.settings || []).map((row) => row.key)
    const isDealerSettings = keys.includes('dealer_currency')
    if (!isDealerSettings) return []

    return [
        'contact_no_prefix',
        'dealer_currency',
        'rate_per_ai_input_million_tokens',
        'rate_per_ai_output_million_tokens',
        'rate_per_standard_whatsapp_message',
        'rate_per_template_whatsapp_message',
        'hours_to_reassign',
        'lead_acknowledgement_minutes',
        'max_historical_published_stock_items',
        'minimum_images_required_for_live',
        'max_concurrent_published_stock_items',
        'maximum_images',
        'maximum_files_in_bucket',
    ]
})

const submit = () => {
    form.patch(props.updateRoute, {
        preserveScroll: true,
    })
}
</script>

<template>
    <q-form @submit.prevent="submit">
        <div>
            <SettingsFields
                v-model="form.settings"
                :settings="settings"
                :errors="form.errors"
                :disabled="!canUpdate || form.processing"
                :timezone-options="timezoneOptions"
                :stock-type-options="stockTypeOptions"
                :show-backoffice-only-badge="showBackofficeOnlyBadge"
                :required-keys="requiredKeys"
            />

            <div class="row justify-end q-gutter-sm q-mt-md">
                <q-btn
                    v-if="canUpdate"
                    color="primary"
                    unelevated
                    type="submit"
                    :loading="form.processing"
                    label="Save Settings"
                />
            </div>
        </div>
    </q-form>
</template>

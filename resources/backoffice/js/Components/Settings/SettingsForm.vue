<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    settings: { type: Array, default: () => [] },
    updateRoute: { type: String, required: true },
    canUpdate: { type: Boolean, default: false },
    timezoneOptions: { type: Array, default: () => [] },
    stockTypeOptions: { type: Array, default: () => [] },
    showBackofficeOnlyBadge: { type: Boolean, default: false },
})

const BANKING_DETAILS_MAX_LENGTH = 200

const initialSettings = Object.fromEntries((props.settings || []).map((row) => [row.key, row.value]))
const form = useForm({ settings: initialSettings })

const groupedSettings = computed(() => {
    const groups = {}
    const preferredCategoryOrder = ['general', 'billing']

    for (const setting of props.settings || []) {
        if (!groups[setting.category]) {
            groups[setting.category] = []
        }
        groups[setting.category].push(setting)
    }

    const categoryRank = (category) => {
        const index = preferredCategoryOrder.indexOf(category)
        return index === -1 ? preferredCategoryOrder.length : index
    }

    return Object.entries(groups)
        .sort((a, b) => {
            const rankDiff = categoryRank(a[0]) - categoryRank(b[0])
            if (rankDiff !== 0) return rankDiff
            return a[0].localeCompare(b[0])
        })
        .map(([category, items]) => ({ category, items }))
})

const categoryLabel = (category) => category.replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase())

const fieldError = (key) => form.errors[`settings.${key}`]

const sanitizeContactNoPrefix = (key, value) => {
    if (key !== 'contact_no_prefix') return
    form.settings[key] = String(value || '').replace(/\s+/g, '')
}

const isBankingDetails = (key) => key === 'banking_details'

const bankingDetailsRemaining = (key) => {
    if (!isBankingDetails(key)) return null
    const length = String(form.settings[key] || '').length
    return Math.max(BANKING_DETAILS_MAX_LENGTH - length, 0)
}

const fieldColumnClass = (key) => (isBankingDetails(key) ? 'col-12' : 'col-12 col-md-6')

const submit = () => {
    form.patch(props.updateRoute, {
        preserveScroll: true,
    })
}
</script>

<template>
    <q-form @submit.prevent="submit">
        <div class="column q-gutter-md">
            <q-card v-for="group in groupedSettings" :key="group.category" flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">{{ categoryLabel(group.category) }}</div>

                    <div class="row q-col-gutter-md">
                        <div v-for="setting in group.items" :key="setting.key" :class="fieldColumnClass(setting.key)">
                            <div class="text-subtitle2 text-grey-9 row items-center q-gutter-xs">
                                <span>{{ setting.label }}</span>
                                <q-badge
                                    v-if="showBackofficeOnlyBadge && setting.backoffice_only"
                                    color="orange"
                                    text-color="black"
                                    label="Backoffice only"
                                />
                            </div>
                            <div class="text-caption text-grey-7 q-mb-sm">{{ setting.description }}</div>

                            <q-select
                                v-if="setting.key === 'default_stock_type_filter'"
                                v-model="form.settings[setting.key]"
                                dense
                                outlined
                                clearable
                                emit-value
                                map-options
                                option-label="label"
                                option-value="value"
                                :disable="!canUpdate || form.processing"
                                :options="stockTypeOptions"
                                :error="!!fieldError(setting.key)"
                                :error-message="fieldError(setting.key)"
                            />

                            <q-toggle
                                v-else-if="setting.type === 'boolean'"
                                v-model="form.settings[setting.key]"
                                :disable="!canUpdate || form.processing"
                                dense
                                left-label
                                checked-icon="check"
                                unchecked-icon="close"
                                :label="form.settings[setting.key] ? 'Enabled' : 'Disabled'"
                            />

                            <q-select
                                v-else-if="setting.type === 'timezone'"
                                v-model="form.settings[setting.key]"
                                dense
                                outlined
                                use-input
                                fill-input
                                input-debounce="0"
                                clearable
                                emit-value
                                map-options
                                option-label="label"
                                option-value="value"
                                :disable="!canUpdate || form.processing"
                                :options="timezoneOptions"
                                :error="!!fieldError(setting.key)"
                                :error-message="fieldError(setting.key)"
                            />

                            <q-input
                                v-else-if="isBankingDetails(setting.key)"
                                v-model="form.settings[setting.key]"
                                type="textarea"
                                rows="5"
                                dense
                                outlined
                                counter
                                :maxlength="BANKING_DETAILS_MAX_LENGTH"
                                :disable="!canUpdate || form.processing"
                                :error="!!fieldError(setting.key)"
                                :error-message="fieldError(setting.key)"
                            >
                                <template #hint>
                                    {{ bankingDetailsRemaining(setting.key) }} characters left
                                </template>
                            </q-input>

                            <q-input
                                v-else
                                v-model="form.settings[setting.key]"
                                :type="setting.type === 'number' || setting.type === 'float' ? 'number' : 'text'"
                                :step="setting.type === 'float' ? '0.0001' : '1'"
                                dense
                                outlined
                                clearable
                                :disable="!canUpdate || form.processing"
                                :error="!!fieldError(setting.key)"
                                :error-message="fieldError(setting.key)"
                                @update:model-value="(value) => sanitizeContactNoPrefix(setting.key, value)"
                            />
                        </div>
                    </div>
                </q-card-section>
            </q-card>

            <div class="row justify-end q-gutter-sm">
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

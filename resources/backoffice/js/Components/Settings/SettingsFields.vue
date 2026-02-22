<script setup>
import { computed } from 'vue'

const props = defineProps({
    settings: { type: Array, default: () => [] },
    modelValue: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    disabled: { type: Boolean, default: false },
    timezoneOptions: { type: Array, default: () => [] },
    stockTypeOptions: { type: Array, default: () => [] },
    showBackofficeOnlyBadge: { type: Boolean, default: false },
    requiredKeys: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

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
const fieldColumnClass = () => 'col-12 col-md-6'

const getValue = (key) => props.modelValue?.[key] ?? null

const setValue = (key, value) => {
    let nextValue = value
    if (key === 'contact_no_prefix') {
        nextValue = String(value || '').replace(/\s+/g, '')
    }

    emit('update:modelValue', {
        ...(props.modelValue || {}),
        [key]: nextValue,
    })
}

const fieldError = (key) => props.errors?.[`settings.${key}`] ?? ''
const fieldMin = (setting) => (setting?.min ?? undefined)
const fieldMax = (setting) => (setting?.max ?? undefined)
</script>

<template>
    <div class="column q-gutter-md">
        <q-card v-for="group in groupedSettings" :key="group.category" flat bordered>
            <q-card-section>
                <div class="text-h6 q-pb-sm">{{ categoryLabel(group.category) }}</div>

                <div class="row q-col-gutter-md">
                    <div v-for="setting in group.items" :key="setting.key" :class="fieldColumnClass()">
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
                            :model-value="getValue(setting.key)"
                            dense
                            outlined
                            emit-value
                            map-options
                            option-label="label"
                            option-value="value"
                            :disable="disabled"
                            :options="stockTypeOptions"
                            :error="!!fieldError(setting.key)"
                            :error-message="fieldError(setting.key)"
                            @update:model-value="(value) => setValue(setting.key, value)"
                        />

                        <q-toggle
                            v-else-if="setting.type === 'boolean'"
                            :model-value="!!getValue(setting.key)"
                            :disable="disabled"
                            dense
                            left-label
                            checked-icon="check"
                            unchecked-icon="close"
                            :label="getValue(setting.key) ? 'Enabled' : 'Disabled'"
                            @update:model-value="(value) => setValue(setting.key, value)"
                        />

                        <q-select
                            v-else-if="setting.type === 'timezone'"
                            :model-value="getValue(setting.key)"
                            dense
                            outlined
                            emit-value
                            map-options
                            option-label="label"
                            option-value="value"
                            :disable="disabled"
                            :options="timezoneOptions"
                            :error="!!fieldError(setting.key)"
                            :error-message="fieldError(setting.key)"
                            @update:model-value="(value) => setValue(setting.key, value)"
                        />

                        <q-input
                            v-else
                            :model-value="getValue(setting.key)"
                            :type="setting.type === 'number' || setting.type === 'float' ? 'number' : 'text'"
                            :step="setting.type === 'float' ? '0.0001' : '1'"
                            :min="fieldMin(setting)"
                            :max="fieldMax(setting)"
                            dense
                            outlined
                            :disable="disabled"
                            :error="!!fieldError(setting.key)"
                            :error-message="fieldError(setting.key)"
                            @update:model-value="(value) => setValue(setting.key, value)"
                        />
                    </div>
                </div>
            </q-card-section>
        </q-card>
    </div>
</template>

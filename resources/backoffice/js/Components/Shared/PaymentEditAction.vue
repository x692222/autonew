<script setup>
const props = defineProps({
    canEdit: { type: Boolean, default: false },
    isApproved: { type: Boolean, default: false },
    iconMode: { type: Boolean, default: true },
    label: { type: String, default: 'Edit' },
})

defineEmits(['click'])
</script>

<template>
    <q-btn
        v-if="canEdit && !isApproved"
        :round="iconMode"
        :dense="iconMode"
        :flat="iconMode"
        :color="iconMode ? undefined : 'primary'"
        icon="edit"
        :label="iconMode ? undefined : label"
        no-wrap
        unelevated
        @click="$emit('click')"
    />
</template>

<script setup>
import { ref, watch } from 'vue'
import NotesDialog from 'bo@/Components/Notes/NotesDialog.vue'

const props = defineProps({
    noteableType: { type: String, required: true },     // e.g. 'dealer-branch'
    idKey: { type: String, default: 'id' },             // row[idKey] used as noteableId
    titleKey: { type: String, default: '' },            // optional: row[titleKey]
    titleFn: { type: Function, default: null },         // optional: (row) => string
    titlePrefix: { type: String, default: 'Notes: ' },
    titleFallback: { type: String, default: 'Notes' },
})

const emit = defineEmits(['closed'])

const notesOpen = ref(false)
const notesTarget = ref({ type: '', id: null, title: props.titleFallback })

const open = (row) => {
    const id = row?.[props.idKey]

    const title =
        (typeof props.titleFn === 'function' ? props.titleFn(row) : '') ||
        (props.titleKey ? row?.[props.titleKey] : '') ||
        props.titleFallback

    notesTarget.value = {
        type: props.noteableType,
        id,
        title: props.titlePrefix + title,
    }

    notesOpen.value = true
}

defineExpose({ open })

watch(
    () => notesOpen.value,
    (isOpen, wasOpen) => {
        if (wasOpen && !isOpen) {
            emit('closed')
        }
    }
)
</script>

<template>
    <NotesDialog
        v-model="notesOpen"
        :noteable-type="notesTarget.type"
        :noteable-id="notesTarget.id ?? ''"
        :title="notesTarget.title"
    />
</template>

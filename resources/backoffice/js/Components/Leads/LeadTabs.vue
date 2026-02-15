<script setup>
import { router } from '@inertiajs/vue3'
import { inject, ref, watch } from 'vue'

const route = inject('route')

const props = defineProps({
  pageTab: { type: String, required: true },
  leadId: { type: String, required: true },
  routes: {
    type: Object,
    required: true,
  },
  routeParams: {
    type: Object,
    default: () => ({}),
  },
})

const tab = ref(props.pageTab)

watch(() => props.pageTab, (v) => {
  tab.value = v
}, { immediate: true })

const go = (name) => {
  tab.value = name

  const routeName = props.routes?.[name]
  if (!routeName) return

  router.visit(route(routeName, { ...props.routeParams, lead: props.leadId }))
}
</script>

<template>
  <q-tabs
    v-model="tab"
    inline-label
    align="left"
    class="bg-grey-2 text-grey-9 q-mb-md"
    active-color="primary"
  >
    <q-tab name="overview" label="Overview" @click="go('overview')" />
    <q-tab name="conversations" label="Conversations" @click="go('conversations')" />
    <q-tab name="stage-history" label="Stage History" @click="go('stage-history')" />
  </q-tabs>
</template>

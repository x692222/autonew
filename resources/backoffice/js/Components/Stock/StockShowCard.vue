<script setup>
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'
import PublishStatusCard from 'bo@/Components/Stock/PublishStatusCard.vue'

const props = defineProps({
    publicTitle: { type: String, default: 'View Stock' },
    dealer: { type: Object, required: true },
    stock: { type: Object, required: true },
    totalImagesCount: { type: Number, default: 0 },
    backUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
})

const enumKeys = new Set(['condition', 'color', 'gearbox_type', 'drive_type', 'fuel_type', 'category'])
const hiddenTypedKeys = new Set(['id', 'stock_id', 'make_id', 'model_id', 'created_at', 'updated_at', 'deleted_at'])

const typedRows = computed(() => {
    const typed = props.stock?.typed || {}
    return Object.entries(typed)
        .filter(([key, value]) => !hiddenTypedKeys.has(key) && value !== null && value !== '')
        .map(([key, value]) => ({
            key,
            label: key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
            value,
            isBoolean: typeof value === 'boolean',
        }))
})

const baseRows = computed(() => ([
    { key: 'name', label: 'Name', value: props.stock?.name || '—', isBoolean: false },
    { key: 'type', label: 'Type', value: props.stock?.type_label || props.stock?.type || '—', isBoolean: false },
    { key: 'price', label: 'Price', value: props.stock?.price ?? '—', isBoolean: false },
    { key: 'internal_reference', label: 'Internal Reference', value: props.stock?.internal_reference || '—', isBoolean: false },
    { key: 'description', label: 'Description', value: props.stock?.description || '—', isBoolean: false },
    { key: 'branch_name', label: 'Branch', value: props.stock?.branch_name || '—', isBoolean: false },
    { key: 'published_at', label: 'Published At', value: props.stock?.published_at || '—', isBoolean: false },
    { key: 'is_live', label: 'Is Live', value: !!props.stock?.is_live, isBoolean: true },
    { key: 'is_active', label: 'Is Active', value: !!props.stock?.is_active, isBoolean: true },
    { key: 'is_sold', label: 'Is Sold', value: !!props.stock?.is_sold, isBoolean: true },
]))

const formatTypedValue = (key, value) => {
    if (key === 'make_name' || key === 'model_name') {
        return value || '—'
    }

    if (enumKeys.has(key)) {
        return String(value).replace(/_/g, ' ').toUpperCase()
    }

    return value
}
</script>

<template>
    <div class="row nowrap justify-between items-center">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">Dealer: <span class="text-weight-medium">{{ dealer?.name }}</span></div>
        </div>

        <div class="q-gutter-sm">
            <q-btn color="grey-7" label="Back" no-caps no-wrap outline @click="router.visit(backUrl)" />
            <q-btn color="primary" label="Edit" no-caps no-wrap unelevated @click="router.visit(editUrl)" />
        </div>
    </div>

    <div class="row q-my-sm q-col-gutter-md">
        <div class="col-lg-8 col-sm-12">
            <q-banner v-if="stock.is_sold" class="bg-grey-10 text-white q-mb-md" rounded dense inline-actions>
                <div class="row items-center justify-center full-width q-gutter-sm">
                    <q-icon name="sell" size="20px" />
                    <div class="text-weight-medium">This stock item is marked as sold</div>
                </div>
            </q-banner>

            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Stock</div>

                    <q-list bordered separator>
                        <q-item v-for="row in baseRows" :key="row.key">
                            <q-item-section>
                                <q-item-label caption>{{ row.label }}</q-item-label>
                                <q-item-label v-if="!row.isBoolean">{{ row.value }}</q-item-label>
                                <q-item-label v-else>
                                    <q-icon :name="row.value ? 'check_circle' : 'cancel'" :color="row.value ? 'positive' : 'negative'" size="18px" />
                                </q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>

                    <q-separator class="q-my-md" />

                    <div class="text-h6 q-pb-sm">Type-specific details</div>
                    <div v-if="!stock.typed" class="text-grey-7">No typed details found.</div>

                    <q-list v-else bordered separator>
                        <q-item v-for="row in typedRows" :key="row.key">
                            <q-item-section>
                                <q-item-label caption>{{ row.label }}</q-item-label>
                                <q-item-label v-if="!row.isBoolean">{{ formatTypedValue(row.key, row.value) ?? '—' }}</q-item-label>
                                <q-item-label v-else>
                                    <q-icon :name="row.value ? 'check_circle' : 'cancel'" :color="row.value ? 'positive' : 'negative'" size="18px" />
                                </q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>
                </q-card-section>
            </q-card>
        </div>

        <div class="col-lg-4 col-sm-12">
            <PublishStatusCard :dealer="dealer" :stock="stock" :total-images-count="totalImagesCount" class="q-mb-md" />

            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Features / Tags</div>
                    <div v-if="!stock.feature_tags?.length" class="text-grey-7">No tags.</div>
                    <div v-else class="q-gutter-xs">
                        <q-chip v-for="t in stock.feature_tags" :key="t.id || t" outline>{{ t.name || t.label || t }}</q-chip>
                    </div>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>

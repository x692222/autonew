<script setup>
import { computed } from 'vue'

const props = defineProps({
    permissions: { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const normalizeLabel = (value, fallback = '') => {
    const source = String(value || fallback || '')
    if (!source) {
        return 'General'
    }

    return source
        .replace(/_/g, ' ')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

const permissionRows = computed(() =>
    (props.permissions || [])
        .map((permission, index) => {
            const nested = (typeof permission === 'object' && permission?.data)
                ? permission.data
                : permission

            const name = typeof permission === 'string'
                ? permission
                : (nested?.name ?? '')

            const group = (typeof nested === 'object' && nested?.group)
                ? String(nested.group)
                : null

            return {
                id: (typeof nested === 'object' && nested?.id) ? nested.id : `permission-${index}`,
                name,
                group,
                label: normalizeLabel(name, 'Unknown Permission'),
            }
        })
        .filter((permission) => permission.name.length > 0)
)

const groupedPermissions = computed(() => {
    const grouped = new Map()

    permissionRows.value.forEach((permission) => {
        const groupKey = permission.group || 'general'

        if (!grouped.has(groupKey)) {
            grouped.set(groupKey, {
                key: groupKey,
                title: normalizeLabel(groupKey, 'General'),
                permissions: [],
            })
        }

        grouped.get(groupKey).permissions.push(permission)
    })

    return Array.from(grouped.values())
})

const selectedPermissions = computed(() => new Set(props.modelValue || []))

const setPermission = (permissionName, shouldEnable) => {
    const next = new Set(selectedPermissions.value)

    if (shouldEnable) {
        next.add(permissionName)
    } else {
        next.delete(permissionName)
    }

    emit('update:modelValue', Array.from(next))
}

const togglePermission = (permissionName) => setPermission(permissionName, !selectedPermissions.value.has(permissionName))
</script>

<template>
    <div class="column q-gutter-md">
        <q-card
            v-for="group in groupedPermissions"
            :key="group.key"
            flat
            bordered
        >
            <q-card-section class="q-pb-sm">
                <div class="text-subtitle1 text-weight-medium text-grey-9">{{ group.title }}</div>
            </q-card-section>

            <q-separator />

            <q-list separator>
                <q-item
                    v-for="permission in group.permissions"
                    :key="permission.id"
                    clickable
                    @click="togglePermission(permission.name)"
                >
                    <q-item-section avatar>
                        <q-checkbox
                            :model-value="selectedPermissions.has(permission.name)"
                            color="primary"
                            @click.stop
                            @update:model-value="(checked) => setPermission(permission.name, !!checked)"
                        />
                    </q-item-section>

                    <q-item-section>
                        <q-item-label class="text-grey-9 text-body1">{{ permission.label }}</q-item-label>
                        <q-item-label caption class="text-grey-7">{{ permission.name }}</q-item-label>
                    </q-item-section>
                </q-item>
            </q-list>
        </q-card>
    </div>
</template>

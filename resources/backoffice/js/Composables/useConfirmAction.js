import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

/**
 * Generic confirm dialog + Inertia action runner (delete/post/patch/put/get).
 *
 * @param {import('vue').Ref<boolean>|null} loadingRef optional loading ref to toggle
 */
export function useConfirmAction (loadingRef = null) {
    const $q = useQuasar()

    /**
     * @param {object} params
     * @param {string} params.title - dialog title
     * @param {string} params.message - dialog message (FULL message passed in)
     * @param {string} params.actionUrl - route(...) resolved url
     * @param {'delete'|'post'|'patch'|'put'|'get'} [params.method='delete']
     * @param {object} [params.data={}] - payload for post/patch/put/get
     * @param {string} [params.okLabel='OK']
     * @param {string} [params.okColor='negative'] - default is negative (as requested)
     * @param {string} [params.cancelLabel='Cancel']
     * @param {boolean} [params.persistent=true]
     * @param {object} [params.inertia={}] - extra inertia options
     */
    function confirmAction ({
                                title,
                                message,
                                actionUrl,
                                method = 'delete',
                                data = {},
                                okLabel = 'OK',
                                okColor = 'negative',
                                cancelLabel = 'Cancel',
                                persistent = true,
                                inertia = {}
                            }) {
        if (!actionUrl) {
            throw new Error('confirmAction: actionUrl is required')
        }

        $q.dialog({
            title: title ?? 'Confirm',
            message: message ?? 'Are you sure?',
            ok: {
                label: okLabel,
                color: okColor || 'negative',
                unelevated: true
            },
            cancel: {
                label: cancelLabel,
                flat: true
            },
            persistent
        }).onOk(() => {
            const m = String(method || 'delete').toLowerCase()

            const visitOptions = {
                preserveScroll: true,
                onStart: () => {
                    if (loadingRef) loadingRef.value = true
                },
                onFinish: () => {
                    if (loadingRef) loadingRef.value = false
                },
                ...inertia
            }

            // Use router.<method> where available, otherwise fall back to router.visit
            if (m === 'delete') return router.delete(actionUrl, visitOptions)
            if (m === 'post') return router.post(actionUrl, data, visitOptions)
            if (m === 'patch') return router.patch(actionUrl, data, visitOptions)
            if (m === 'put') return router.put(actionUrl, data, visitOptions)
            if (m === 'get') return router.get(actionUrl, data, visitOptions)

            return router.visit(actionUrl, { method: m, data, ...visitOptions })
        })
    }

    return { confirmAction }
}

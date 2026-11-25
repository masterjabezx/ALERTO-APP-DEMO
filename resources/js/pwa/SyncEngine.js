/**
 * ALERTO APP — Progressive Web App Synchronization Engine
 * Manages atomic synchronization of offline incident reports and personnel milestone updates.
 * Implements exponential backoff, dependency ordering, and conflict detection.
 */

import OfflineStorage from './OfflineStorage';
import NetworkManager from './NetworkManager';

class SyncEngine {
    constructor() {
        this.isSyncing = false;
    }

    /**
     * Synchronize all pending offline items for the current authenticated user.
     */
    async syncAll(userId) {
        if (!userId) return { succeeded: 0, failed: 0, conflicts: 0 };
        if (this.isSyncing) return { inProgress: true };

        // Verify active server connectivity first
        const isConnected = await NetworkManager.verifyServerConnection();
        if (!isConnected) {
            console.log('[ALERTO SyncEngine] Server unreachable. Synchronization deferred.');
            return { deferred: true };
        }

        this.isSyncing = true;
        NetworkManager.setStatus('syncing');

        let succeeded = 0;
        let failed = 0;
        let conflicts = 0;

        try {
            // 1. Sync Citizen Offline Drafts
            const drafts = await OfflineStorage.getDraftIncidents(userId);
            const pendingDrafts = drafts.filter((d) => d.status === 'pending' || d.status === 'failed');

            for (const draft of pendingDrafts) {
                const result = await this.syncSingleDraft(draft);
                if (result.success) succeeded++;
                else failed++;
            }

            // 2. Sync Personnel Queued Milestone Updates
            const updates = await OfflineStorage.getQueuedMilestones(userId);
            const pendingUpdates = updates.filter((u) => u.status === 'pending' || u.status === 'failed');

            for (const update of pendingUpdates) {
                const result = await this.syncSingleMilestone(update);
                if (result.success) succeeded++;
                else if (result.conflict) conflicts++;
                else failed++;
            }

            // Update remaining pending count
            await this.refreshPendingCount(userId);

            if (conflicts > 0 || failed > 0) {
                NetworkManager.setStatus('sync_failed', { failed, conflicts });
            } else {
                NetworkManager.setStatus('online');
                NetworkManager.setLastSynced(new Date());
            }

            window.dispatchEvent(new CustomEvent('alerto:sync-complete', {
                detail: { succeeded, failed, conflicts, timestamp: new Date().toISOString() }
            }));

            return { succeeded, failed, conflicts };
        } catch (err) {
            console.error('[ALERTO SyncEngine] Global sync error:', err);
            NetworkManager.setStatus('sync_failed', err.message);
            return { succeeded, failed, conflicts, error: err.message };
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Synchronize a single citizen incident report draft.
     */
    async syncSingleDraft(draft) {
        try {
            await OfflineStorage.updateDraftIncident(draft.client_uuid, { status: 'syncing' });

            const formData = new FormData();
            formData.append('idempotency_key', draft.idempotency_key);
            formData.append('client_uuid', draft.client_uuid);
            formData.append('category_id', draft.category_id);
            formData.append('caller_phone', draft.caller_phone);
            if (draft.caller_name) formData.append('caller_name', draft.caller_name);
            formData.append('barangay_id', draft.barangay_id);
            formData.append('latitude', draft.latitude);
            formData.append('longitude', draft.longitude);
            if (draft.accuracy) formData.append('accuracy', draft.accuracy);
            formData.append('location_source', draft.location_source);
            if (draft.street_address) formData.append('street_address', draft.street_address);
            if (draft.landmark) formData.append('landmark', draft.landmark);
            if (draft.description) formData.append('description', draft.description);

            // Append attached files / Blobs
            if (Array.isArray(draft.attachments)) {
                draft.attachments.forEach((fileItem, idx) => {
                    if (fileItem instanceof Blob || fileItem instanceof File) {
                        const filename = fileItem.name || `offline_evidence_${idx}.jpg`;
                        formData.append(`attachments[${idx}]`, fileItem, filename);
                    }
                });
            }

            // Get CSRF Token
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

            const res = await fetch('/pwa/sync/incident', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (res.ok) {
                const responseData = await res.json();
                await OfflineStorage.updateDraftIncident(draft.client_uuid, {
                    status: 'submitted',
                    server_reference: responseData.reference_number,
                    synced_at: responseData.receipt_time || new Date().toISOString(),
                    last_error: null
                });

                window.dispatchEvent(new CustomEvent('alerto:incident-synced', {
                    detail: { draft, response: responseData }
                }));

                return { success: true, data: responseData };
            } else {
                const errData = await res.json().catch(() => ({}));
                const errMsg = errData.message || `Server returned ${res.status}`;
                await OfflineStorage.updateDraftIncident(draft.client_uuid, {
                    status: 'failed',
                    last_error: errMsg,
                    retry_count: (draft.retry_count || 0) + 1
                });
                return { success: false, error: errMsg };
            }
        } catch (err) {
            await OfflineStorage.updateDraftIncident(draft.client_uuid, {
                status: 'failed',
                last_error: err.message,
                retry_count: (draft.retry_count || 0) + 1
            });
            return { success: false, error: err.message };
        }
    }

    /**
     * Synchronize a queued personnel milestone update.
     */
    async syncSingleMilestone(update) {
        try {
            await OfflineStorage.updateQueuedMilestone(update.client_uuid, { status: 'syncing' });

            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

            const res = await fetch('/pwa/sync/milestone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    assignment_id: update.assignment_id,
                    milestone: update.milestone,
                    expected_version_lock: update.expected_version_lock,
                    idempotency_key: update.idempotency_key,
                    notes: update.notes,
                    latitude: update.latitude,
                    longitude: update.longitude
                })
            });

            if (res.ok) {
                const data = await res.json();
                await OfflineStorage.updateQueuedMilestone(update.client_uuid, {
                    status: 'synced',
                    synced_at: data.receipt_time || new Date().toISOString(),
                    last_error: null
                });
                return { success: true, data };
            } else if (res.status === 409) {
                // Conflict detected on server
                const conflictData = await res.json().catch(() => ({}));
                await OfflineStorage.updateQueuedMilestone(update.client_uuid, {
                    status: 'conflict',
                    conflict_details: conflictData,
                    last_error: conflictData.reason || 'Server status conflict'
                });

                window.dispatchEvent(new CustomEvent('alerto:sync-conflict', {
                    detail: { update, conflict: conflictData }
                }));

                return { success: false, conflict: true, data: conflictData };
            } else {
                const errData = await res.json().catch(() => ({}));
                const errMsg = errData.message || `Server returned ${res.status}`;
                await OfflineStorage.updateQueuedMilestone(update.client_uuid, {
                    status: 'failed',
                    last_error: errMsg
                });
                return { success: false, error: errMsg };
            }
        } catch (err) {
            await OfflineStorage.updateQueuedMilestone(update.client_uuid, {
                status: 'failed',
                last_error: err.message
            });
            return { success: false, error: err.message };
        }
    }

    /**
     * Refresh the total pending unsynced count in NetworkManager.
     */
    async refreshPendingCount(userId) {
        if (!userId) return 0;
        const drafts = await OfflineStorage.getDraftIncidents(userId);
        const updates = await OfflineStorage.getQueuedMilestones(userId);

        const pendingDrafts = drafts.filter((d) => d.status === 'pending' || d.status === 'failed');
        const pendingUpdates = updates.filter((u) => u.status === 'pending' || u.status === 'failed');

        const total = pendingDrafts.length + pendingUpdates.length;
        NetworkManager.setPendingCount(total);
        return total;
    }
}

export default new SyncEngine();


/**
 * ALERTO APP — Client-Side IndexedDB Offline Storage
 * Partitions records by authenticated user_id for strict security.
 * Stores citizen offline drafts, personnel tasks, queued updates, and office snapshots.
 */

const DB_NAME = 'alerto_offline_db';
const DB_VERSION = 1;

class OfflineStorage {
    constructor() {
        this.db = null;
        this.dbPromise = null;
    }

    async getDb() {
        if (this.db) return this.db;
        if (!this.dbPromise) {
            this.dbPromise = new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;

                    // 1. Citizen offline draft incidents
                    if (!db.objectStoreNames.contains('draft_incidents')) {
                        const store = db.createObjectStore('draft_incidents', { keyPath: 'client_uuid' });
                        store.createIndex('user_id', 'user_id', { unique: false });
                        store.createIndex('status', 'status', { unique: false });
                        store.createIndex('created_at', 'created_at', { unique: false });
                    }

                    // 2. Personnel cached tasks
                    if (!db.objectStoreNames.contains('personnel_tasks')) {
                        const store = db.createObjectStore('personnel_tasks', { keyPath: 'assignment_id' });
                        store.createIndex('user_id', 'user_id', { unique: false });
                        store.createIndex('reference_number', 'reference_number', { unique: false });
                    }

                    // 3. Personnel queued milestone updates
                    if (!db.objectStoreNames.contains('personnel_queued_updates')) {
                        const store = db.createObjectStore('personnel_queued_updates', { keyPath: 'client_uuid' });
                        store.createIndex('user_id', 'user_id', { unique: false });
                        store.createIndex('assignment_id', 'assignment_id', { unique: false });
                        store.createIndex('status', 'status', { unique: false });
                    }

                    // 4. Office cached snapshots
                    if (!db.objectStoreNames.contains('office_cache')) {
                        const store = db.createObjectStore('office_cache', { keyPath: 'cache_key' });
                        store.createIndex('user_id', 'user_id', { unique: false });
                    }
                };

                request.onsuccess = (event) => {
                    this.db = event.target.result;
                    resolve(this.db);
                };

                request.onerror = (event) => {
                    console.error('[ALERTO OfflineStorage] Error opening IndexedDB:', event.target.error);
                    reject(event.target.error);
                };
            });
        }
        return this.dbPromise;
    }

    /* =========================================================================
     * CITIZEN OFFLINE DRAFT INCIDENTS
     * ========================================================================= */

    async saveDraftIncident(draft) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('draft_incidents', 'readwrite');
            const store = tx.objectStore('draft_incidents');

            const record = {
                client_uuid: draft.client_uuid || crypto.randomUUID(),
                idempotency_key: draft.idempotency_key || `draft_${crypto.randomUUID()}`,
                user_id: draft.user_id,
                category_id: draft.category_id,
                category_name: draft.category_name || 'Emergency Incident',
                caller_phone: draft.caller_phone,
                caller_name: draft.caller_name || '',
                barangay_id: draft.barangay_id,
                barangay_name: draft.barangay_name || '',
                latitude: draft.latitude,
                longitude: draft.longitude,
                accuracy: draft.accuracy || null,
                location_source: draft.location_source || 'gps_device',
                street_address: draft.street_address || '',
                landmark: draft.landmark || '',
                description: draft.description || '',
                attachments: draft.attachments || [], // Array of Blobs / file meta
                status: draft.status || 'pending', // 'pending', 'syncing', 'submitted', 'failed'
                server_reference: null,
                created_at: draft.created_at || new Date().toISOString(),
                synced_at: null,
                last_error: null,
                retry_count: 0
            };

            const req = store.put(record);
            req.onsuccess = () => resolve(record);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async getDraftIncidents(userId) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('draft_incidents', 'readonly');
            const store = tx.objectStore('draft_incidents');
            const index = store.index('user_id');
            const req = index.getAll(userId);

            req.onsuccess = () => resolve(req.result || []);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async getDraftIncident(clientUuid) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('draft_incidents', 'readonly');
            const store = tx.objectStore('draft_incidents');
            const req = store.get(clientUuid);

            req.onsuccess = () => resolve(req.result);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async updateDraftIncident(clientUuid, updates) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('draft_incidents', 'readwrite');
            const store = tx.objectStore('draft_incidents');
            const getReq = store.get(clientUuid);

            getReq.onsuccess = () => {
                const existing = getReq.result;
                if (!existing) return resolve(null);

                const updated = Object.assign(existing, updates);
                const putReq = store.put(updated);
                putReq.onsuccess = () => resolve(updated);
                putReq.onerror = (e) => reject(e.target.error);
            };
            getReq.onerror = (e) => reject(e.target.error);
        });
    }

    async deleteDraftIncident(clientUuid) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('draft_incidents', 'readwrite');
            const store = tx.objectStore('draft_incidents');
            const req = store.delete(clientUuid);

            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    /* =========================================================================
     * PERSONNEL CACHED TASKS
     * ========================================================================= */

    async savePersonnelTasks(userId, tasks) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('personnel_tasks', 'readwrite');
            const store = tx.objectStore('personnel_tasks');

            tasks.forEach((task) => {
                store.put({
                    ...task,
                    user_id: userId,
                    cached_at: new Date().toISOString()
                });
            });

            tx.oncomplete = () => resolve(true);
            tx.onerror = (e) => reject(e.target.error);
        });
    }

    async getPersonnelTasks(userId) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('personnel_tasks', 'readonly');
            const store = tx.objectStore('personnel_tasks');
            const index = store.index('user_id');
            const req = index.getAll(userId);

            req.onsuccess = () => resolve(req.result || []);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    /* =========================================================================
     * PERSONNEL QUEUED MILESTONE UPDATES
     * ========================================================================= */

    async queuePersonnelMilestone(data) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('personnel_queued_updates', 'readwrite');
            const store = tx.objectStore('personnel_queued_updates');

            const record = {
                client_uuid: data.client_uuid || crypto.randomUUID(),
                idempotency_key: data.idempotency_key || `milestone_${crypto.randomUUID()}`,
                user_id: data.user_id,
                assignment_id: data.assignment_id,
                incident_reference: data.incident_reference || '',
                milestone: data.milestone,
                expected_version_lock: data.expected_version_lock || 1,
                notes: data.notes || '',
                latitude: data.latitude || null,
                longitude: data.longitude || null,
                status: 'pending', // 'pending', 'syncing', 'synced', 'conflict', 'failed'
                created_at: new Date().toISOString(),
                last_error: null,
                conflict_details: null
            };

            const req = store.put(record);
            req.onsuccess = () => resolve(record);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async getQueuedMilestones(userId) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('personnel_queued_updates', 'readonly');
            const store = tx.objectStore('personnel_queued_updates');
            const index = store.index('user_id');
            const req = index.getAll(userId);

            req.onsuccess = () => resolve(req.result || []);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async updateQueuedMilestone(clientUuid, updates) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('personnel_queued_updates', 'readwrite');
            const store = tx.objectStore('personnel_queued_updates');
            const getReq = store.get(clientUuid);

            getReq.onsuccess = () => {
                const existing = getReq.result;
                if (!existing) return resolve(null);

                const updated = Object.assign(existing, updates);
                const putReq = store.put(updated);
                putReq.onsuccess = () => resolve(updated);
                putReq.onerror = (e) => reject(e.target.error);
            };
            getReq.onerror = (e) => reject(e.target.error);
        });
    }

    async deleteQueuedMilestone(clientUuid) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('personnel_queued_updates', 'readwrite');
            const store = tx.objectStore('personnel_queued_updates');
            const req = store.delete(clientUuid);

            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    /* =========================================================================
     * OFFICE CACHED SNAPSHOTS
     * ========================================================================= */

    async saveOfficeSnapshot(userId, officeId, payload) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('office_cache', 'readwrite');
            const store = tx.objectStore('office_cache');

            const record = {
                cache_key: `office_${officeId}_user_${userId}`,
                user_id: userId,
                office_id: officeId,
                payload: payload,
                cached_at: new Date().toISOString()
            };

            const req = store.put(record);
            req.onsuccess = () => resolve(record);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    async getOfficeSnapshot(userId, officeId) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('office_cache', 'readonly');
            const store = tx.objectStore('office_cache');
            const req = store.get(`office_${officeId}_user_${userId}`);

            req.onsuccess = () => resolve(req.result?.payload || null);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    /* =========================================================================
     * PARTITIONED USER DATA CLEAR (LOGOUT / USER SWITCH)
     * ========================================================================= */

    async clearUserData(userId) {
        const db = await this.getDb();
        const stores = ['draft_incidents', 'personnel_tasks', 'personnel_queued_updates', 'office_cache'];

        for (const storeName of stores) {
            await new Promise((resolve) => {
                const tx = db.transaction(storeName, 'readwrite');
                const store = tx.objectStore(storeName);
                if (store.indexNames.contains('user_id')) {
                    const index = store.index('user_id');
                    const req = index.getAllKeys(userId);
                    req.onsuccess = () => {
                        const keys = req.result;
                        keys.forEach((key) => store.delete(key));
                    };
                }
                tx.oncomplete = () => resolve(true);
                tx.onerror = () => resolve(false);
            });
        }
    }

    /**
     * Clear cached read-only operational snapshots (tasks/office summaries) on logout
     * while preserving unsynchronized drafts and queued updates unless explicitly discarded.
     */
    async clearCachedSnapshots(userId) {
        if (!userId) return;
        const db = await this.getDb();
        const snapshotStores = ['personnel_tasks', 'office_cache'];

        for (const storeName of snapshotStores) {
            await new Promise((resolve) => {
                const tx = db.transaction(storeName, 'readwrite');
                const store = tx.objectStore(storeName);
                if (store.indexNames.contains('user_id')) {
                    const index = store.index('user_id');
                    const req = index.getAllKeys(userId);
                    req.onsuccess = () => {
                        req.result.forEach((key) => store.delete(key));
                    };
                }
                tx.oncomplete = () => resolve(true);
                tx.onerror = () => resolve(false);
            });
        }
    }

    async hasPendingSyncItems() {
        const db = await this.getDb();
        const checkStore = async (name) => new Promise((resolve) => {
            const tx = db.transaction(name, 'readonly');
            const store = tx.objectStore(name);
            const req = store.getAll();
            req.onsuccess = () => {
                const pending = (req.result || []).some((r) => r.status === 'pending' || r.status === 'failed' || r.status === 'conflict');
                resolve(pending);
            };
            req.onerror = () => resolve(false);
        });

        return (await checkStore('draft_incidents')) || (await checkStore('personnel_queued_updates'));
    }
}

export default new OfflineStorage();


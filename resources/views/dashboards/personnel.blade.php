<x-layouts.personnel>
    <div class="space-y-6" x-data="personnelTelemetry()">

        {{-- Operative Status & Fast Response Header --}}
        <div class="alerto-card p-6 relative overflow-hidden">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/30 flex items-center justify-center shrink-0 font-black text-lg">
                        {{ strtoupper(substr($user->name ?? 'P', 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-extrabold text-[var(--text-primary)]">{{ $user->name }}</h2>
                            @php
                                $opStatus = $availability?->operational_status ?? 'off_duty';
                            @endphp
                            @if($opStatus === 'available')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A] border border-[#27845B]/35">
                                    <span class="w-2 h-2 rounded-full bg-[#27845B] dark:bg-[#5BD19A] animate-pulse"></span> Available (Ready)
                                </span>
                            @elseif($opStatus === 'dispatched')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#397BA8]/15 text-[#25597E] dark:text-[#7BB5DB] border border-[#397BA8]/35">
                                    <span class="w-2 h-2 rounded-full bg-[#397BA8] dark:bg-[#7BB5DB] animate-pulse"></span> Dispatched (En Route)
                                </span>
                            @elseif($opStatus === 'on_scene')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] border border-[#E87524]/35">
                                    <span class="w-2 h-2 rounded-full bg-[#E87524]"></span> On Scene
                                </span>
                            @elseif($opStatus === 'busy')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#D99024]/15 text-[#9A5D07] dark:text-[#F6BA53] border border-[#D99024]/35">
                                    <span class="w-2 h-2 rounded-full bg-[#D99024]"></span> Busy
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[var(--surface-panel)] text-[var(--text-secondary)] border border-[var(--border-subtle)]">
                                    <span class="w-2 h-2 rounded-full bg-[var(--text-muted)]"></span> Off Duty
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-[var(--text-secondary)] font-medium mt-0.5">
                            {{ $personnel?->office?->name ?? 'Hinoba-an Quick Response Unit' }} · Badge #{{ $personnel?->badge_number ?? 'OP-00' }}
                            @if($personnel?->teams->isNotEmpty())
                                · Team: {{ $personnel->teams->pluck('call_sign')->join(', ') }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Status Change Action & GPS Broadcast --}}
                <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                    <button type="button" 
                            @click="toggleGpsTracking()" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border cursor-pointer"
                            :class="trackingActive ? 'bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A] border-[#27845B]/40' : 'bg-[var(--surface-panel)] text-[var(--text-secondary)] border-[var(--border-subtle)] hover:border-[#E87524]/40'">
                        <i data-lucide="navigation" class="w-3.5 h-3.5" :class="trackingActive ? 'text-[#27845B] dark:text-[#5BD19A] animate-spin' : 'text-[var(--text-muted)]'"></i>
                        <span x-text="trackingActive ? 'Duty GPS Active' : 'Start Duty GPS'"></span>
                    </button>

                    <a href="{{ route('personnel.availability') }}" 
                       class="px-3.5 py-2 rounded-xl text-xs font-bold bg-[#17191C] hover:bg-[#292D32] text-white transition-all shadow-xs flex items-center gap-1.5 border border-white/10">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-[#E87524]"></i>
                        <span>Change Duty Status</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-[#27845B]/12 border border-[#27845B]/30 text-[#1B6644] dark:text-[#5BD19A] text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-[#27845B] dark:text-[#5BD19A] shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-[#C83F43]/12 border border-[#C83F43]/30 text-[#9F292D] dark:text-[#F27D80] text-xs font-medium flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-[#C83F43] dark:text-[#F27D80] shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Prominent Field Responder Distress SOS --}}
        <x-sos-button-widget
            :endpoint="route('personnel.sos.trigger')"
            sender-type="personnel"
            :sender-name="$user->name"
            :sender-phone="$user->phone_number"
            :last-known-lat="$personnel?->latestLocation?->latitude"
            :last-known-lng="$personnel?->latestLocation?->longitude"
        />

        {{-- Statistics Row --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="alerto-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] block">Assigned Tasks</span>
                <p class="text-2xl font-black text-[#C83F43] dark:text-[#F27D80] font-mono mt-1">{{ $activeAssignments->count() }}</p>
                <span class="text-[10px] text-[var(--text-muted)]">Active emergency runs</span>
            </div>

            <div class="alerto-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] block">Assigned Teams</span>
                <p class="text-2xl font-black text-[var(--text-primary)] font-mono mt-1">{{ $personnel?->teams->count() ?? 0 }}</p>
                <span class="text-[10px] text-[var(--text-muted)]">Operational squads</span>
            </div>

            <div class="alerto-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] block">Coverage</span>
                <p class="text-2xl font-black text-[#B94F12] dark:text-[#F29A56] font-mono mt-1">{{ $personnel?->jurisdictions->count() ?? 0 }}</p>
                <span class="text-[10px] text-[var(--text-muted)]">Hinoba-an Barangays</span>
            </div>

            <div class="alerto-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] block">Telemetry Status</span>
                <p class="text-sm font-black text-[#27845B] dark:text-[#5BD19A] mt-2 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#27845B] dark:bg-[#5BD19A] animate-pulse"></span>
                    <span x-text="heartbeatStatus">Connected</span>
                </p>
                <span class="text-[10px] text-[var(--text-muted)] font-mono">Heartbeat Syncing</span>
            </div>
        </div>

        {{-- Current Assigned Incidents / Tasks Management --}}
        <div class="alerto-card p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-4 mb-4 border-b border-[var(--border-subtle)]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#C83F43]/12 text-[#C83F43] dark:text-[#F27D80] flex items-center justify-center shrink-0">
                        <i data-lucide="radio" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-[var(--text-primary)]">Current Assigned Incidents &amp; Tasks</h3>
                        <p class="text-xs text-[var(--text-secondary)]">Live emergency dispatches requiring immediate tactical response</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-[#9F292D] dark:text-[#F27D80] bg-[#C83F43]/12 px-2.5 py-1 rounded-full border border-[#C83F43]/25">
                    {{ $activeAssignments->count() }} Live Assignment(s)
                </span>
            </div>

            {{-- Queued Offline Updates (Module 4-B: Not Yet Synchronized) --}}
            <div x-show="queuedUpdates.length > 0" x-cloak class="p-4 sm:p-5 rounded-2xl bg-[#17191C] border border-[#D99024]/50 text-[#F5F3EF] space-y-3 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#F6BA53] animate-pulse"></span>
                        <h3 class="font-extrabold text-sm text-white">Queued Offline Updates (<span x-text="queuedUpdates.length"></span>)</h3>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-sm bg-[#D99024]/20 text-[#F6BA53] border border-[#D99024]/40">Not Yet Synchronized</span>
                </div>
                <div class="space-y-2">
                    <template x-for="item in queuedUpdates" :key="item.client_uuid">
                        <div class="p-3 rounded-xl bg-[#292D32] border border-white/10 flex items-center justify-between gap-3 text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-white uppercase" x-text="item.milestone.replace('_', ' ')"></span>
                                    <span class="text-[#A39E98] font-mono text-[11px]" x-text="item.incident_reference"></span>
                                </div>
                                <p class="text-[11px] text-[#D1CEC9] mt-0.5" x-text="item.notes"></p>
                            </div>
                            <span class="px-2 py-1 rounded-lg text-[10px] font-bold"
                                  :class="item.status === 'conflict' ? 'bg-[#C83F43]/25 text-[#F27D80] border border-[#C83F43]/40' : 'bg-[#D99024]/20 text-[#F6BA53] border border-[#D99024]/40'"
                                  x-text="item.status === 'conflict' ? 'Status Conflict' : 'Queued'">
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="space-y-4">
                @forelse($activeAssignments as $assignment)
                    @php
                        $incident = $assignment->incident;
                    @endphp
                    <div class="p-5 rounded-2xl border border-[var(--border-subtle)] hover:border-[#E87524]/50 transition-all bg-[var(--surface-panel)]/70">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-3 border-b border-[var(--border-subtle)]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#17191C] text-[#F29A56]">
                                        {{ $incident?->reference_number }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-[#C83F43]/12 text-[#9F292D] dark:text-[#F27D80]">
                                        {{ $incident?->category?->name ?? 'Emergency Incident' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $incident?->priority === 'critical' ? 'bg-[#C83F43] text-white' : 'bg-[#D99024]/15 text-[#9A5D07] dark:text-[#F6BA53]' }}">
                                        {{ ucfirst($incident?->priority ?? 'high') }} Priority
                                    </span>
                                    <span class="text-[11px] text-[var(--text-muted)]">
                                        Assigned {{ $assignment->assigned_at ? $assignment->assigned_at->diffForHumans() : '' }}
                                    </span>
                                </div>
                                <h4 class="text-base font-bold text-[var(--text-primary)]">{{ $incident?->title ?? ($incident?->category?->name ?? 'Emergency Incident') }}</h4>
                                <p class="text-xs text-[var(--text-secondary)] mt-1 line-clamp-2">{{ $incident?->description }}</p>
                            </div>

                            <div class="shrink-0 flex items-center gap-2">
                                @if($incident?->caller_phone)
                                    <a href="tel:{{ $incident->caller_phone }}" 
                                       class="px-3.5 py-2.5 rounded-xl text-xs font-bold bg-[#27845B]/15 text-[#1B6644] dark:text-[#5BD19A] hover:bg-[#27845B]/25 border border-[#27845B]/35 transition-colors flex items-center gap-1.5">
                                        <i data-lucide="phone-call" class="w-3.5 h-3.5"></i>
                                        <span>Call Reporter ({{ $incident->caller_phone }})</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Scene Location & Details --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 my-3 text-xs">
                            <div class="p-2.5 rounded-xl bg-[var(--surface-card)] border border-[var(--border-subtle)]">
                                <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Scene Barangay</span>
                                <span class="font-bold text-[var(--text-primary)]">{{ $incident?->barangay?->name ?? 'Hinoba-an' }}</span>
                            </div>

                            <div class="p-2.5 rounded-xl bg-[var(--surface-card)] border border-[var(--border-subtle)]">
                                <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Street / Landmark</span>
                                <span class="font-bold text-[var(--text-primary)]">{{ $incident?->location?->street_address ?? $incident?->location?->street_name ?? 'Coordinates Provided' }}</span>
                                @if($incident?->location?->landmark)
                                    <span class="text-[11px] text-[var(--text-secondary)] block">Near: {{ $incident->location->landmark }}</span>
                                @endif
                            </div>

                            <div class="p-2.5 rounded-xl bg-[var(--surface-card)] border border-[var(--border-subtle)]">
                                <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Current Incident Status</span>
                                <span class="font-bold text-[#B94F12] dark:text-[#F29A56] uppercase font-mono">{{ str_replace('_', ' ', $incident?->status ?? 'Active') }}</span>
                            </div>
                        </div>

                        {{-- Milestone Progression Action Buttons (High-Contrast Field Touch Targets) --}}
                        <div class="pt-3 border-t border-[var(--border-subtle)]">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)] block mb-2">Advance Tactical Response Milestone:</span>
                            <div class="flex flex-wrap items-center gap-2">
                                @if(!$assignment->acknowledged_at)
                                    <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                        @csrf
                                        <input type="hidden" name="milestone" value="acknowledged">
                                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#17191C] hover:bg-[#292D32] text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer border border-white/10">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-[#E87524]"></i>
                                            <span>Acknowledge Task</span>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="on_the_way">
                                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#397BA8] hover:bg-[#2E668D] text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>En Route / On The Way</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="on_scene">
                                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#E87524] hover:bg-[#D16419] text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <span>Arrived On Scene</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="in_progress">
                                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#D99024] hover:bg-[#BA781A] text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                                        <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                                        <span>Operations In Progress</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="resolved">
                                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#27845B] hover:bg-[#1E6A49] text-white shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                        <span>Mark Incident Resolved</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-[var(--text-muted)] bg-[var(--surface-panel)]/50 rounded-2xl border border-dashed border-[var(--border-subtle)]">
                        <i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-2 text-[#27845B] dark:text-[#5BD19A]"></i>
                        <p class="font-bold text-[var(--text-primary)] text-sm">No Active Emergency Assignments</p>
                        <p class="text-xs text-[var(--text-muted)] mt-1">You are in standby mode. Keep your duty status ONLINE to receive incoming dispatches.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function personnelTelemetry() {
            return {
                trackingActive: false,
                watchId: null,
                heartbeatStatus: 'Online',
                queuedUpdates: [],

                async init() {
                    this.sendHeartbeat();
                    setInterval(() => this.sendHeartbeat(), 60000);

                    await this.loadQueuedUpdates();
                    window.addEventListener('alerto:sync-complete', () => this.loadQueuedUpdates());
                    window.addEventListener('alerto:sync-conflict', (e) => {
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: {
                                title: 'Task Status Conflict',
                                message: e.detail.conflict?.reason || 'Conflict detected with Command Center record.',
                                type: 'error'
                            }
                        }));
                    });
                },

                async loadQueuedUpdates() {
                    const uid = {{ auth()->id() ?? 'null' }};
                    if (uid && window.AlertoPwa && window.AlertoPwa.storage) {
                        const all = await window.AlertoPwa.storage.getQueuedMilestones(uid);
                        this.queuedUpdates = all.filter(u => u.status !== 'synced');
                    }
                },

                async submitMilestone(e, assignmentId, ref, versionLock) {
                    const isOnline = window.AlertoPwa ? window.AlertoPwa.network.isOnline() : navigator.onLine;
                    const milestoneInput = e.target.querySelector('input[name="milestone"]');
                    const milestone = milestoneInput ? milestoneInput.value : 'acknowledged';

                    if (milestone === 'resolved' && isOnline && !confirm('Are you sure you want to mark this incident as RESOLVED? This clears your active assignment.')) {
                        return;
                    }

                    if (!isOnline) {
                        const uid = {{ auth()->id() ?? 'null' }};
                        const clientUuid = crypto.randomUUID();
                        const idempotencyKey = 'milestone_' + crypto.randomUUID();

                        const updateRecord = {
                            client_uuid: clientUuid,
                            idempotency_key: idempotencyKey,
                            user_id: uid,
                            assignment_id: assignmentId,
                            incident_reference: ref,
                            milestone: milestone,
                            expected_version_lock: versionLock,
                            notes: `Queued milestone: ${milestone} (Recorded offline)`
                        };

                        if (window.AlertoPwa && window.AlertoPwa.storage) {
                            await window.AlertoPwa.storage.queuePersonnelMilestone(updateRecord);
                            await window.AlertoPwa.sync.refreshPendingCount(uid);
                            await this.loadQueuedUpdates();
                        }

                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: {
                                title: 'Update Queued (Offline)',
                                message: `Milestone '${milestone}' saved locally. Labeled: NOT YET SYNCHRONIZED.`,
                                type: 'warning'
                            }
                        }));
                        return;
                    }

                    e.target.submit();
                },

                sendHeartbeat() {
                    fetch('{{ route('personnel.heartbeat') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ device: navigator.userAgent })
                    }).then(res => {
                        if (res.ok) {
                            this.heartbeatStatus = 'Connected';
                        }
                    }).catch(() => {
                        this.heartbeatStatus = 'Disconnected';
                    });
                },

                toggleGpsTracking() {
                    if (this.trackingActive) {
                        if (this.watchId) navigator.geolocation.clearWatch(this.watchId);
                        this.trackingActive = false;
                    } else {
                        if (!navigator.geolocation) {
                            alert('Geolocation is not supported by your browser.');
                            return;
                        }

                        this.trackingActive = true;
                        this.watchId = navigator.geolocation.watchPosition(
                            pos => this.broadcastPosition(pos),
                            err => console.warn('GPS Error:', err.message),
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
                        );
                    }
                },

                broadcastPosition(position) {
                    fetch('{{ route('personnel.location') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                            speed: position.coords.speed,
                            heading: position.coords.heading
                        })
                    }).catch(e => console.error('Position broadcast failed', e));
                }
            }
        }
    </script>
    @endpush
</x-layouts.personnel>

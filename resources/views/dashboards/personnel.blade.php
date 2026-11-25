<x-layouts.personnel>
    <div class="space-y-6" x-data="personnelTelemetry()">

        {{-- Operative Status & Fast Response Header --}}
        <div class="bg-white rounded-[24px] p-6 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)]">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-[#3187D8]/10 text-[#3187D8] border border-[#3187D8]/20 flex items-center justify-center flex-shrink-0 font-bold text-lg">
                        {{ strtoupper(substr($user->name ?? 'P', 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900">{{ $user->name }}</h2>
                            @php
                                $opStatus = $availability?->operational_status ?? 'off_duty';
                            @endphp
                            @if($opStatus === 'available')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Available (Ready)
                                </span>
                            @elseif($opStatus === 'dispatched')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span> Dispatched (En Route)
                                </span>
                            @elseif($opStatus === 'on_scene')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span> On Scene
                                </span>
                            @elseif($opStatus === 'busy')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Busy
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span> Off Duty
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
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
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border"
                            :class="trackingActive ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                        <i data-lucide="navigation" class="w-3.5 h-3.5" :class="trackingActive ? 'text-emerald-600 animate-spin' : 'text-slate-400'"></i>
                        <span x-text="trackingActive ? 'Duty GPS Active' : 'Start Duty GPS'"></span>
                    </button>

                    <a href="{{ route('personnel.availability') }}" 
                       class="px-3.5 py-2 rounded-xl text-xs font-bold bg-[#173957] hover:bg-[#1f4b72] text-white transition-all shadow-xs flex items-center gap-1.5">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Change Duty Status</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Prominent Field Responder Distress SOS --}}
        <x-sos-button-widget
            :endpoint="route('personnel.sos.trigger')"
            sender-type="personnel"
            :sender-name="$user->name"
            :sender-phone="$user->phone_number"
            :last-known-lat="$personnel?->last_latitude"
            :last-known-lng="$personnel?->last_longitude"
        />

        {{-- Statistics Row --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Assigned Tasks</span>
                <p class="text-2xl font-black text-rose-600 mt-1">{{ $activeAssignments->count() }}</p>
                <span class="text-[10px] text-slate-400">Active emergency runs</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Assigned Teams</span>
                <p class="text-2xl font-black text-[#173957] mt-1">{{ $personnel?->teams->count() ?? 0 }}</p>
                <span class="text-[10px] text-slate-400">Operational squads</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Coverage</span>
                <p class="text-2xl font-black text-[#3187D8] mt-1">{{ $personnel?->jurisdictions->count() ?? 0 }}</p>
                <span class="text-[10px] text-slate-400">Hinoba-an Barangays</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Telemetry Status</span>
                <p class="text-sm font-black text-emerald-600 mt-2 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span x-text="heartbeatStatus">Connected</span>
                </p>
                <span class="text-[10px] text-slate-400 font-mono">Heartbeat Syncing</span>
            </div>
        </div>

        {{-- Current Assigned Incidents / Tasks Management --}}
        <div class="bg-white rounded-[24px] p-6 border border-slate-200/80 shadow-[0_4px_20px_-2px_rgba(23,57,87,0.06)]">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#DB4545]/10 text-[#DB4545] flex items-center justify-center">
                        <i data-lucide="radio" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Current Assigned Incidents &amp; Tasks</h3>
                        <p class="text-xs text-slate-500">Live emergency dispatches requiring immediate tactical response</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-[#DB4545] bg-[#DB4545]/10 px-2.5 py-1 rounded-full border border-[#DB4545]/20">
                    {{ $activeAssignments->count() }} Live Assignment(s)
                </span>
            </div>

            {{-- Queued Offline Updates (Module 4-B: Not Yet Synchronized) --}}
            <div x-show="queuedUpdates.length > 0" x-cloak class="p-4 sm:p-5 rounded-2xl bg-amber-950/40 border border-amber-600/60 text-slate-200 space-y-3 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                        <h3 class="font-extrabold text-sm text-white">Queued Offline Updates (<span x-text="queuedUpdates.length"></span>)</h3>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-sm bg-amber-500/20 text-amber-300 border border-amber-500/40">Not Yet Synchronized</span>
                </div>
                <div class="space-y-2">
                    <template x-for="item in queuedUpdates" :key="item.client_uuid">
                        <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-700/80 flex items-center justify-between gap-3 text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-white uppercase" x-text="item.milestone.replace('_', ' ')"></span>
                                    <span class="text-slate-400 font-mono text-[11px]" x-text="item.incident_reference"></span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5" x-text="item.notes"></p>
                            </div>
                            <span class="px-2 py-1 rounded-lg text-[10px] font-bold"
                                  :class="item.status === 'conflict' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40'"
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
                    <div class="p-5 rounded-2xl border border-slate-200 hover:border-[#3187D8]/60 transition-all bg-slate-50/50">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-3 border-b border-slate-200/70">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#173957] text-white">
                                        {{ $incident?->incident_number }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-800">
                                        {{ $incident?->category?->name ?? 'Emergency Incident' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $incident?->priority === 'critical' ? 'bg-red-500 text-white' : 'bg-amber-100 text-amber-800' }}">
                                        {{ ucfirst($incident?->priority ?? 'high') }} Priority
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        Assigned {{ $assignment->assigned_at ? $assignment->assigned_at->diffForHumans() : '' }}
                                    </span>
                                </div>
                                <h4 class="text-base font-bold text-slate-900">{{ $incident?->title }}</h4>
                                <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $incident?->description }}</p>
                            </div>

                            <div class="shrink-0 flex items-center gap-2">
                                @if($incident?->caller_phone)
                                    <a href="tel:{{ $incident->caller_phone }}" 
                                       class="px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors flex items-center gap-1.5">
                                        <i data-lucide="phone-call" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>Call Reporter ({{ $incident->caller_phone }})</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Scene Location & Details --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 my-3 text-xs">
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Scene Barangay</span>
                                <span class="font-bold text-slate-800">{{ $incident?->barangay?->name ?? 'Hinoba-an' }}</span>
                            </div>

                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Street / Landmark</span>
                                <span class="font-bold text-slate-800">{{ $incident?->location?->street_name ?? 'Coordinates Provided' }}</span>
                                @if($incident?->location?->landmark)
                                    <span class="text-[11px] text-slate-500 block">Near: {{ $incident->location->landmark }}</span>
                                @endif
                            </div>

                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Current Incident Status</span>
                                <span class="font-bold text-[#173957] uppercase font-mono">{{ str_replace('_', ' ', $incident?->status ?? 'Active') }}</span>
                            </div>
                        </div>

                        {{-- Milestone Progression Action Buttons --}}
                        <div class="pt-3 border-t border-slate-200/70">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-2">Advance Tactical Response Milestone:</span>
                            <div class="flex flex-wrap items-center gap-2">
                                @if(!$assignment->acknowledged_at)
                                    <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                        @csrf
                                        <input type="hidden" name="milestone" value="acknowledged">
                                        <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-[#173957] hover:bg-[#1f4b72] text-white shadow-xs flex items-center gap-1.5 transition-all">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Acknowledge Task</span>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="on_the_way">
                                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs flex items-center gap-1.5 transition-all">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>En Route / On The Way</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="on_scene">
                                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white shadow-xs flex items-center gap-1.5 transition-all">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <span>Arrived On Scene</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="in_progress">
                                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs flex items-center gap-1.5 transition-all">
                                        <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                                        <span>Operations In Progress</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('personnel.milestone', $assignment->id) }}" onsubmit="if(!window.AlertoPwa?.network.isOnline()) { return true; } return confirm('Are you sure you want to mark this incident as RESOLVED? This clears your active assignment.');" @submit.prevent="submitMilestone($event, {{ $assignment->id }}, '{{ $incident?->reference_number }}', {{ $incident?->version_lock ?? 1 }})">
                                    @csrf
                                    <input type="hidden" name="milestone" value="resolved">
                                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs flex items-center gap-1.5 transition-all">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                        <span>Mark Incident Resolved</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                        <i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-2 text-emerald-500"></i>
                        <p class="font-bold text-slate-700 text-sm">No Active Emergency Assignments</p>
                        <p class="text-xs text-slate-400 mt-1">You are in standby mode. Keep your duty status ONLINE to receive incoming dispatches.</p>
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
<x-layouts.super-admin :title="'Automated Routing Rules & Categories — LDRRMO Super Admin'">
    <div class="space-y-8 text-xs">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-white">Automated Routing Rules &amp; Incident Categories</h1>
            <p class="text-xs text-slate-400 mt-1">
                Configure incident auto-dispatch rules, SOS escalation defaults, and emergency assessment categories used by DIAL DAPH Command Center.
            </p>
        </div>

        {{-- 1. INCIDENT ROUTING RULES --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <form method="POST" action="{{ route('super-admin.routing.incidents.store') }}" class="lg:col-span-5 p-6 rounded-2xl bg-[#0B1726] border border-purple-500/30 space-y-3">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-purple-300">Add Incident Routing Rule</h2>

                <div>
                    <label class="block text-slate-400 font-bold mb-1">Incident Category *</label>
                    <select name="category_id" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Barangay Scope</label>
                        <select name="barangay_id" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="">All Barangays</option>
                            @foreach($barangays as $brgy)
                                <option value="{{ $brgy->id }}">{{ $brgy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Priority Match</label>
                        <select name="priority" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="">Any Priority</option>
                            @foreach(['critical', 'high', 'medium', 'low'] as $prio)
                                <option value="{{ $prio }}">{{ strtoupper($prio) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Primary Target Office *</label>
                        <select name="target_office_id" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="">-- Office --</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Fallback Office</label>
                        <select name="fallback_office_id" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="">None</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Response Team</label>
                        <select name="response_team_id" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="">Any Team</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->team_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Min Responders</label>
                        <input type="number" name="required_responders_count" value="1" min="1" max="20" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Escalation (min)</label>
                        <input type="number" name="escalation_minutes" value="10" min="1" max="240" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 font-bold mb-1">Escalation Notes</label>
                    <input type="text" name="escalation_notes" placeholder="Optional dispatch guidance..." class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                </div>

                <div class="flex flex-wrap gap-4 pt-1">
                    <label class="inline-flex items-center gap-2 text-slate-200">
                        <input type="hidden" name="auto_dispatch" value="0">
                        <input type="checkbox" name="auto_dispatch" value="1" checked class="rounded">
                        Enable Auto-Dispatch
                    </label>
                    <label class="inline-flex items-center gap-2 text-slate-200">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded">
                        Rule Active
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black cursor-pointer">
                    Save Incident Routing Rule
                </button>
            </form>

            <div class="lg:col-span-7 rounded-2xl bg-[#0B1726] border border-white/10 overflow-hidden">
                <div class="p-4 border-b border-white/10">
                    <h2 class="text-sm font-black uppercase tracking-wider text-white">Configured Incident Routing Rules ({{ $incidentRules->count() }})</h2>
                </div>
                <div class="divide-y divide-white/10 max-h-[460px] overflow-y-auto alerto-scrollbar">
                    @forelse($incidentRules as $rule)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-black text-white">{{ $rule->category?->name ?? 'Category' }}</span>
                                    <span class="px-2 py-0.5 rounded bg-white/10 text-slate-300 font-mono text-[10px]">{{ $rule->barangay?->name ?? 'ALL BARANGAYS' }}</span>
                                    @if($rule->priority)
                                        <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 uppercase text-[10px] font-bold">{{ $rule->priority }}</span>
                                    @endif
                                </div>
                                <p class="text-slate-300">
                                    Primary: <strong>{{ $rule->targetOffice?->name }}</strong>
                                    @if($rule->fallbackOffice) · Fallback: {{ $rule->fallbackOffice->name }} @endif
                                    @if($rule->responseTeam) · Team: {{ $rule->responseTeam->team_name }} @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('super-admin.routing.incidents.delete', $rule->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600/80 hover:bg-rose-500 text-white font-bold cursor-pointer">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="p-8 text-center text-slate-400">No automated incident routing rules configured yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- 2. SOS ROUTING RULES --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <form method="POST" action="{{ route('super-admin.routing.sos.store') }}" class="lg:col-span-5 p-6 rounded-2xl bg-[#0B1726] border border-rose-500/30 space-y-3">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-rose-300">Add SOS Routing Rule</h2>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">SOS Source Scope *</label>
                        <select name="source_type" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="all">All SOS Types</option>
                            <option value="citizen">Citizen / Guest SOS</option>
                            <option value="personnel">Personnel Responder SOS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Priority Order</label>
                        <input type="number" name="priority_order" value="1" min="1" max="100" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Default Office *</label>
                        <select name="office_id" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="">-- Office --</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold mb-1">Default Team</label>
                        <select name="team_id" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                            <option value="">Any Team</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->team_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 font-bold mb-1">Routing Notes</label>
                    <input type="text" name="notes" placeholder="Optional escalation or dispatch notes..." class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 text-slate-200">
                        <input type="hidden" name="auto_notify" value="0">
                        <input type="checkbox" name="auto_notify" value="1" checked class="rounded">
                        Auto-Notify Office
                    </label>
                    <label class="inline-flex items-center gap-2 text-slate-200">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded">
                        Rule Active
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black cursor-pointer">
                    Save SOS Routing Rule
                </button>
            </form>

            <div class="lg:col-span-7 rounded-2xl bg-[#0B1726] border border-white/10 overflow-hidden">
                <div class="p-4 border-b border-white/10">
                    <h2 class="text-sm font-black uppercase tracking-wider text-white">Configured SOS Routing Rules ({{ $sosRules->count() }})</h2>
                </div>
                <div class="divide-y divide-white/10">
                    @forelse($sosRules as $sRule)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-white">{{ $sRule->office?->name ?? 'Office' }}</span>
                                    <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 uppercase text-[10px] font-bold">{{ $sRule->source_type }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">Order #{{ $sRule->priority_order }}</span>
                                </div>
                                <p class="text-slate-300 mt-1">
                                    @if($sRule->team) Team: {{ $sRule->team->team_name }} · @endif
                                    {{ $sRule->notes ?: 'Standard automated SOS routing target.' }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('super-admin.routing.sos.delete', $sRule->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600/80 hover:bg-rose-500 text-white font-bold cursor-pointer">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="p-8 text-center text-slate-400">No automated SOS routing rules configured yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- 3. INCIDENT CATEGORIES --}}
        <section class="p-6 rounded-2xl bg-[#0B1726] border border-white/10 space-y-5">
            <h2 class="text-sm font-black uppercase tracking-wider text-cyan-300">Emergency Assessment Categories</h2>

            <form method="POST" action="{{ route('super-admin.categories.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 p-4 rounded-xl bg-black/25 border border-white/10">
                @csrf
                <input type="text" name="name" required placeholder="Category Name (e.g. Medical)" class="sm:col-span-2 px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                <select name="default_priority" required class="px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                    @foreach(['critical', 'high', 'medium', 'low'] as $p)
                        <option value="{{ $p }}">{{ strtoupper($p) }}</option>
                    @endforeach
                </select>
                <input type="text" name="color_code" value="#DC2626" placeholder="#DC2626" class="px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white font-mono">
                <input type="text" name="description" placeholder="Short assessment note..." class="px-3 py-2 rounded-xl bg-slate-900 border border-white/15 text-white">
                <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-black cursor-pointer">Add Category</button>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($categories as $cat)
                    <form method="POST" action="{{ route('super-admin.categories.update', $cat->id) }}" class="p-4 rounded-xl bg-white/5 border border-white/10 space-y-2.5">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input type="text" name="name" value="{{ $cat->name }}" required class="px-3 py-1.5 rounded-lg bg-slate-900 border border-white/15 text-white font-bold">
                            <select name="default_priority" required class="px-3 py-1.5 rounded-lg bg-slate-900 border border-white/15 text-white">
                                @foreach(['critical', 'high', 'medium', 'low'] as $p)
                                    <option value="{{ $p }}" {{ $cat->default_priority === $p ? 'selected' : '' }}>{{ strtoupper($p) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="color_code" value="{{ $cat->color_code }}" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-white/15 text-white font-mono">
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <label class="inline-flex items-center gap-2 text-slate-300">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }} class="rounded">
                                Active
                            </label>
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-purple-600 text-white font-bold cursor-pointer">Update</button>
                        </div>
                    </form>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.super-admin>


<x-layouts.super-admin :title="'Automated Routing Rules & Categories — LDRRMO Super Admin'">
    <div class="space-y-8 text-xs">
        <x-portal.page-header
            eyebrow="Dispatch Policies"
            title="Automated Routing Rules &amp; Incident Categories"
            subtitle="Configure incident auto-dispatch rules, SOS escalation defaults, and emergency assessment categories used by DIAL DAPH Command Center."
            :breadcrumbs="[['label' => 'Executive Dashboard', 'href' => route('super-admin.dashboard')], ['label' => 'Routing & Categories']]"
        />

        {{-- 1. INCIDENT ROUTING RULES --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <form method="POST" action="{{ route('super-admin.routing.incidents.store') }}" class="lg:col-span-5 eoc-card p-6 border-t-2 border-t-[#E87524] space-y-3">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-[#E87524]">Add Incident Routing Rule</h2>

                <div>
                    <label class="block text-[#9D9993] font-bold mb-1">Incident Category *</label>
                    <select name="category_id" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Barangay Scope</label>
                        <select name="barangay_id" class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="">All Barangays</option>
                            @foreach($barangays as $brgy)
                                <option value="{{ $brgy->id }}">{{ $brgy->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Priority Match</label>
                        <select name="priority" class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="">Any Priority</option>
                            @foreach(['critical', 'high', 'medium', 'low'] as $prio)
                                <option value="{{ $prio }}">{{ strtoupper($prio) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Primary Target Office *</label>
                        <select name="target_office_id" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="">-- Office --</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Fallback Office</label>
                        <select name="fallback_office_id" class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="">None</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Response Team</label>
                        <select name="response_team_id" class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="">Any Team</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->team_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Min Responders</label>
                        <input type="number" name="required_responders_count" value="1" min="1" max="20" class="w-full min-h-[40px] px-3 py-2 rounded-xl border font-mono">
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Escalation (min)</label>
                        <input type="number" name="escalation_minutes" value="10" min="1" max="240" class="w-full min-h-[40px] px-3 py-2 rounded-xl border font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[#9D9993] font-bold mb-1">Escalation Notes</label>
                    <input type="text" name="escalation_notes" placeholder="Optional dispatch guidance..." class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                </div>

                <div class="flex flex-wrap gap-4 pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="auto_dispatch" value="0">
                        <input type="checkbox" name="auto_dispatch" value="1" checked class="rounded text-[#E87524]">
                        Enable Auto-Dispatch
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded text-[#E87524]">
                        Rule Active
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-black cursor-pointer transition-colors btn-fixed">
                    Save Incident Routing Rule
                </button>
            </form>

            <div class="lg:col-span-7 eoc-card overflow-hidden">
                <div class="p-4 border-b border-white/10">
                    <h2 class="text-sm font-black uppercase tracking-wider">Configured Incident Routing Rules ({{ $incidentRules->count() }})</h2>
                </div>
                <div class="divide-y divide-white/10 max-h-[460px] overflow-y-auto alerto-scrollbar">
                    @forelse($incidentRules as $rule)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-black">{{ $rule->category?->name ?? 'Category' }}</span>
                                    <span class="px-2 py-0.5 rounded bg-white/10 font-mono text-[10px]">{{ $rule->barangay?->name ?? 'ALL BARANGAYS' }}</span>
                                    @if($rule->priority)
                                        <span class="px-2 py-0.5 rounded bg-[#E87524]/20 text-[#E87524] uppercase text-[10px] font-bold">{{ $rule->priority }}</span>
                                    @endif
                                </div>
                                <p class="text-[#9D9993]">
                                    Primary: <strong class="text-[#E87524]">{{ $rule->targetOffice?->name }}</strong>
                                    @if($rule->fallbackOffice) · Fallback: {{ $rule->fallbackOffice->name }} @endif
                                    @if($rule->responseTeam) · Team: {{ $rule->responseTeam->team_name }} @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('super-admin.routing.incidents.delete', $rule->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#C83F43] hover:bg-[#a93034] text-white font-bold cursor-pointer btn-fixed">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="p-8 text-center text-[#9D9993]">No automated incident routing rules configured yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- 2. SOS ROUTING RULES --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <form method="POST" action="{{ route('super-admin.routing.sos.store') }}" class="lg:col-span-5 eoc-card p-6 border-t-2 border-t-[#C83F43] space-y-3">
                @csrf
                <h2 class="text-sm font-black uppercase tracking-wider text-[#C83F43]">Add SOS Routing Rule</h2>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">SOS Source Scope *</label>
                        <select name="source_type" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="all">All SOS Types</option>
                            <option value="citizen">Citizen / Guest SOS</option>
                            <option value="personnel">Personnel Responder SOS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Priority Order</label>
                        <input type="number" name="priority_order" value="1" min="1" max="100" class="w-full min-h-[40px] px-3 py-2 rounded-xl border font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Default Office *</label>
                        <select name="office_id" required class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="">-- Office --</option>
                            @foreach($offices as $off)
                                <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#9D9993] font-bold mb-1">Default Team</label>
                        <select name="team_id" class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                            <option value="">Any Team</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->team_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[#9D9993] font-bold mb-1">Routing Notes</label>
                    <input type="text" name="notes" placeholder="Optional escalation or dispatch notes..." class="w-full min-h-[40px] px-3 py-2 rounded-xl border">
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="auto_notify" value="0">
                        <input type="checkbox" name="auto_notify" value="1" checked class="rounded text-[#C83F43]">
                        Auto-Notify Office
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded text-[#C83F43]">
                        Rule Active
                    </label>
                </div>

                <button type="submit" class="w-full py-3 rounded-xl bg-[#C83F43] hover:bg-[#a93034] text-white font-black cursor-pointer transition-colors btn-fixed">
                    Save SOS Routing Rule
                </button>
            </form>

            <div class="lg:col-span-7 eoc-card overflow-hidden">
                <div class="p-4 border-b border-white/10">
                    <h2 class="text-sm font-black uppercase tracking-wider">Configured SOS Routing Rules ({{ $sosRules->count() }})</h2>
                </div>
                <div class="divide-y divide-white/10">
                    @forelse($sosRules as $sRule)
                        <div class="p-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-black">{{ $sRule->office?->name ?? 'Office' }}</span>
                                    <span class="px-2 py-0.5 rounded bg-[#C83F43]/20 text-[#C83F43] uppercase text-[10px] font-bold">{{ $sRule->source_type }}</span>
                                    <span class="font-mono text-[10px] text-[#9D9993]">Order #{{ $sRule->priority_order }}</span>
                                </div>
                                <p class="text-[#9D9993] mt-1">
                                    @if($sRule->team) Team: {{ $sRule->team->team_name }} · @endif
                                    {{ $sRule->notes ?: 'Standard automated SOS routing target.' }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('super-admin.routing.sos.delete', $sRule->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#C83F43] hover:bg-[#a93034] text-white font-bold cursor-pointer btn-fixed">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="p-8 text-center text-[#9D9993]">No automated SOS routing rules configured yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- 3. INCIDENT CATEGORIES --}}
        <section class="eoc-card p-6 space-y-5">
            <h2 class="text-sm font-black uppercase tracking-wider text-[#E87524]">Emergency Assessment Categories</h2>

            <form method="POST" action="{{ route('super-admin.categories.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 p-4 rounded-xl bg-black/15 border border-white/10">
                @csrf
                <input type="text" name="name" required placeholder="Category Name (e.g. Medical)" class="sm:col-span-2 min-h-[40px] px-3 py-2 rounded-xl border">
                <select name="default_priority" required class="min-h-[40px] px-3 py-2 rounded-xl border">
                    @foreach(['critical', 'high', 'medium', 'low'] as $p)
                        <option value="{{ $p }}">{{ strtoupper($p) }}</option>
                    @endforeach
                </select>
                <input type="text" name="color_code" value="#E87524" placeholder="#E87524" class="min-h-[40px] px-3 py-2 rounded-xl border font-mono">
                <input type="text" name="description" placeholder="Short assessment note..." class="min-h-[40px] px-3 py-2 rounded-xl border">
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#E87524] hover:bg-[#B94F12] text-white font-black cursor-pointer btn-fixed">Add Category</button>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($categories as $cat)
                    <form method="POST" action="{{ route('super-admin.categories.update', $cat->id) }}" class="p-4 rounded-xl bg-white/5 border border-white/10 space-y-2.5">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input type="text" name="name" value="{{ $cat->name }}" required class="px-3 py-1.5 rounded-lg border font-bold">
                            <select name="default_priority" required class="px-3 py-1.5 rounded-lg border">
                                @foreach(['critical', 'high', 'medium', 'low'] as $p)
                                    <option value="{{ $p }}" {{ $cat->default_priority === $p ? 'selected' : '' }}>{{ strtoupper($p) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="color_code" value="{{ $cat->color_code }}" class="px-3 py-1.5 rounded-lg border font-mono">
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }} class="rounded text-[#E87524]">
                                Active
                            </label>
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#292D32] hover:bg-[#E87524] text-white font-bold cursor-pointer transition-colors btn-fixed">Update</button>
                        </div>
                    </form>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.super-admin>

<x-layouts.admin :title="'Edit Agency — ALERTO EOC'">
    <div class="space-y-6 max-w-4xl">

        {{-- Breadcrumb / Top Bar --}}
        <div>
            <x-portal.breadcrumbs :items="[
                ['label' => 'Office Agencies', 'url' => route('admin.offices.index')],
                ['label' => 'Edit ' . $office->name]
            ]" />
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight mt-2">
                Edit Agency Configuration
            </h1>
            <p class="text-xs sm:text-sm text-[#77736E] mt-1">
                Update agency details, contact telephone, location coordinates, and assigned incident responsibilities.
            </p>
        </div>

        @if($errors->any())
            <div class="p-4 rounded-xl bg-[#C83F43]/15 border border-[#C83F43]/30 text-[#C83F43] text-xs font-medium">
                <div class="font-bold mb-1 flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-[#C83F43]"></i>
                    Please resolve the following errors:
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.offices.update', $office->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- 1. Office Agency Identity --}}
            <div class="alerto-card p-6 space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#E6E3DE] dark:border-white/10">
                    <div class="w-8 h-8 rounded-lg bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] flex items-center justify-center font-bold text-sm">1</div>
                    <div>
                        <h2 class="text-sm font-bold">Agency Identification &amp; Category</h2>
                        <p class="text-xs text-[#77736E]">Official name, agency code, and municipal department category</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold mb-1">Agency Name <span class="text-[#C83F43]">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $office->name) }}" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Agency Code <span class="text-[#C83F43]">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $office->code) }}" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Department Category <span class="text-[#C83F43]">*</span></label>
                        <select name="department_type" required class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                            <option value="disaster" {{ old('department_type', $office->department_type) == 'disaster' ? 'selected' : '' }}>Disaster Management (MDRRMO)</option>
                            <option value="fire" {{ old('department_type', $office->department_type) == 'fire' ? 'selected' : '' }}>Fire Protection (BFP)</option>
                            <option value="police" {{ old('department_type', $office->department_type) == 'police' ? 'selected' : '' }}>Law Enforcement (PNP)</option>
                            <option value="medical" {{ old('department_type', $office->department_type) == 'medical' ? 'selected' : '' }}>Health &amp; Medical (RHU)</option>
                            <option value="traffic" {{ old('department_type', $office->department_type) == 'traffic' ? 'selected' : '' }}>Traffic &amp; Public Safety</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Hotline / Dispatch Phone <span class="text-[#C83F43]">*</span></label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $office->contact_phone) }}" required
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Official Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $office->contact_email) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold mb-1">Agency Description &amp; Mission Mandate</label>
                        <textarea name="description" rows="2"
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">{{ old('description', $office->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- 2. Physical Location & Jurisdiction --}}
            <div class="alerto-card p-6 space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#E6E3DE] dark:border-white/10">
                    <div class="w-8 h-8 rounded-lg bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] flex items-center justify-center font-bold text-sm">2</div>
                    <div>
                        <h2 class="text-sm font-bold">Physical Location &amp; Coordinates</h2>
                        <p class="text-xs text-[#77736E]">Station address, base barangay, and spatial coordinates</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold mb-1">Station Barangay <span class="text-[#C83F43]">*</span></label>
                        <select name="barangay_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] focus:outline-hidden focus:ring-2 focus:ring-[#E87524]">
                            @foreach($barangays as $bg)
                                <option value="{{ $bg->id }}" {{ old('barangay_id', $office->location?->barangay_id) == $bg->id ? 'selected' : '' }}>
                                    {{ $bg->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Street Address</label>
                        <input type="text" name="street_address" value="{{ old('street_address', $office->location?->street_address) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Station Latitude</label>
                        <input type="number" step="0.00000001" name="latitude" value="{{ old('latitude', $office->location?->latitude ?? 9.4975) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1">Station Longitude</label>
                        <input type="number" step="0.00000001" name="longitude" value="{{ old('longitude', $office->location?->longitude ?? 122.5850) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-[#E6E3DE] dark:border-white/15 bg-white dark:bg-[#17191C] font-mono">
                    </div>
                </div>
            </div>

            {{-- 3. Incident Category Responsibilities --}}
            @php
                $currentResponsibilities = $office->responsibilities->pluck('category_slug')->toArray();
            @endphp
            <div class="alerto-card p-6 space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-[#E6E3DE] dark:border-white/10">
                    <div class="w-8 h-8 rounded-lg bg-[#E87524]/15 text-[#B94F12] dark:text-[#F29A56] flex items-center justify-center font-bold text-sm">3</div>
                    <div>
                        <h2 class="text-sm font-bold">Incident Category Responsibilities</h2>
                        <p class="text-xs text-[#77736E]">Emergency categories where this agency has primary response coordination authority</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                    @foreach($categories as $category)
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-[#E6E3DE] dark:border-white/15 hover:border-[#E87524]/60 hover:bg-[#FFF0E4]/50 dark:hover:bg-white/5 cursor-pointer transition-colors">
                            <input type="checkbox" name="categories[]" value="{{ $category->slug }}"
                                   {{ in_array($category->slug, old('categories', $currentResponsibilities)) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#E87524] border-[#E6E3DE] focus:ring-[#E87524]">
                            <span class="text-xs font-medium">{{ $category->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.offices.index') }}"
                   class="px-5 py-2.5 rounded-xl text-xs font-bold text-[#77736E] hover:bg-[#F5F3EF] dark:hover:bg-white/10 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="alerto-btn-press px-6 py-2.5 rounded-xl text-xs font-bold bg-[#E87524] hover:bg-[#B94F12] text-white shadow-md transition-all flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>

    </div>
</x-layouts.admin>

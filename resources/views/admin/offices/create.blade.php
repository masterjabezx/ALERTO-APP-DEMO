<x-layouts.admin>
    <div class="space-y-6 max-w-4xl">

        {{-- Breadcrumb / Top Bar --}}
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                <a href="{{ route('admin.offices.index') }}" class="hover:text-[#173957] font-medium">Office Agencies</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-800 font-bold">Register Office Agency</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                Register Municipal Response Agency
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Configure official department credentials, physical location, contact channels, and emergency responsibilities.
            </p>
        </div>

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                <div class="font-bold mb-1 flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600"></i>
                    Please resolve the following errors:
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.offices.store') }}" class="space-y-6">
            @csrf

            {{-- 1. Office Agency Identity --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-[#173957]/10 text-[#173957] flex items-center justify-center font-bold text-sm">1</div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Agency Identification &amp; Category</h2>
                        <p class="text-xs text-slate-500">Official name, agency code, and municipal department category</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Agency Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required 
                               placeholder="e.g., Bureau of Fire Protection — Hinoba-an Station" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Agency Code <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" required 
                               placeholder="e.g., BFP-HNB" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Department Category <span class="text-rose-500">*</span></label>
                        <select name="department_type" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                            <option value="">Select Category</option>
                            <option value="disaster" {{ old('department_type') == 'disaster' ? 'selected' : '' }}>Disaster Management (MDRRMO)</option>
                            <option value="fire" {{ old('department_type') == 'fire' ? 'selected' : '' }}>Fire Protection (BFP)</option>
                            <option value="police" {{ old('department_type') == 'police' ? 'selected' : '' }}>Law Enforcement (PNP)</option>
                            <option value="medical" {{ old('department_type') == 'medical' ? 'selected' : '' }}>Medical Emergency</option>
                            <option value="health" {{ old('department_type') == 'health' ? 'selected' : '' }}>Rural Health Unit / Municipal Health (RHU)</option>
                            <option value="engineering" {{ old('department_type') == 'engineering' ? 'selected' : '' }}>Municipal Engineering / Rescue Support</option>
                            <option value="social_welfare" {{ old('department_type') == 'social_welfare' ? 'selected' : '' }}>MSWDO / Relief &amp; Welfare</option>
                            <option value="traffic" {{ old('department_type') == 'traffic' ? 'selected' : '' }}>Traffic &amp; Public Safety</option>
                            <option value="other" {{ old('department_type') == 'other' ? 'selected' : '' }}>Other Municipal Support Agency</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Hotline / Dispatch Phone <span class="text-rose-500">*</span></label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" required 
                               placeholder="e.g., 0917-000-0000" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Official Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email') }}" 
                               placeholder="e.g., bfp.hinobaan@dilg.gov.ph" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Agency Description &amp; Mission Mandate</label>
                        <textarea name="description" rows="2" placeholder="Brief mandate or operational scope" 
                                  class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- 2. Physical Location & Jurisdiction --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-[#173957]/10 text-[#173957] flex items-center justify-center font-bold text-sm">2</div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Physical Location &amp; Coordinates</h2>
                        <p class="text-xs text-slate-500">Station address, base barangay, and spatial coordinates</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Station Barangay <span class="text-rose-500">*</span></label>
                        <select name="barangay_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                            <option value="">Select Barangay</option>
                            @foreach($barangays as $bg)
                                <option value="{{ $bg->id }}" {{ old('barangay_id') == $bg->id ? 'selected' : '' }}>
                                    {{ $bg->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Street Address</label>
                        <input type="text" name="street_address" value="{{ old('street_address', 'Poblacion, Hinoba-an') }}" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-[#3187D8]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Station Latitude</label>
                        <input type="number" step="0.00000001" name="latitude" value="{{ old('latitude', '9.4975') }}" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Station Longitude</label>
                        <input type="number" step="0.00000001" name="longitude" value="{{ old('longitude', '122.5850') }}" 
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 font-mono">
                    </div>
                </div>
            </div>

            {{-- 3. Incident Category Responsibilities --}}
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-[#173957]/10 text-[#173957] flex items-center justify-center font-bold text-sm">3</div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Incident Category Responsibilities</h2>
                        <p class="text-xs text-slate-500">Emergency categories where this agency has primary response coordination authority</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                    @foreach($categories as $category)
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:border-[#3187D8]/50 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="categories[]" value="{{ $category->slug }}" 
                                   {{ in_array($category->slug, old('categories', [])) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#173957] border-slate-300 focus:ring-[#3187D8]">
                            <span class="text-xs font-medium text-slate-800">{{ $category->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.offices.index') }}" 
                   class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl text-xs font-bold bg-[#173957] hover:bg-[#1f4b72] text-white shadow-md transition-all flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Register Agency</span>
                </button>
            </div>
        </form>

    </div>
</x-layouts.admin>

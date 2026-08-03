@extends('layouts.layout')

@section('content')

<section class="features-section mt-1">

    <div class="container mx-auto py-12 px-4 min-h-screen" x-data="{ 
            isEditOpen: false, 
            editAction: '', 
            editForm: { bill_type_id: '', charge_key: '', label: '', default_amount: '', is_building_wide: false } 
        }">

        <div class="mb-8 flex justify-between items-center bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 tracking-tight">🏢 Multi-Utility Configuration Templates</h1>
                <p class="text-sm text-gray-500 mt-1">Define fluid baseline configurations for fixed charges, old dues, or structural penalties across Unity Rose Garden.</p>
            </div>
            <div>
                <a href="{{ route('meter-readings-and-charges.index') }}" class="btn btn-outline-secondary px-4 py-2 text-xs font-semibold rounded shadow-sm">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to Meter Readings
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center mb-6 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check me-2 text-lg"></i>
                <div class="font-medium text-sm">{{ session('success') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-6 shadow-sm" role="alert">
                <div class="font-bold text-sm mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Please correct the following:</div>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div>
                <x-mobile-panel-toggles :add-open="$errors->any()">
                    <x-slot:add>
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fa-solid fa-circle-plus text-emerald-500 me-2"></i> Create New Cost Heading
                            </h3>
                            
                            <form action="{{ route('charge-templates.store') }}" method="POST" class="space-y-4">
                                @csrf
                                
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1" for="bill_type_id">Bill type</label>
                                    <select id="bill_type_id" name="bill_type_id" class="form-select text-sm">
                                        <option value="">Select bill type…</option>
                                        @foreach($billTypes as $type)
                                            <option value="{{ $type->id }}" @selected(old('bill_type_id') == $type->id)>{{ $type->label }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-[10px] text-gray-400 block mt-1">Required when applying building-wide (otherwise the charge is skipped on generate).</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1" for="charge_key">Charge Key / Slug</label>
                                    <input type="text" id="charge_key" name="charge_key" class="form-control text-sm" placeholder="e.g. service_charge" required value="{{ old('charge_key') }}">
                                    <span class="text-[10px] text-gray-400 block mt-1">Unique system identifier. No spaces (use underscores).</span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1" for="label">Display Label</label>
                                    <input type="text" id="label" name="label" class="form-control text-sm" placeholder="e.g. Building Service Charge" required value="{{ old('label') }}">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1" for="default_amount">Default Amount (BDT)</label>
                                    <div class="input-group">
                                        <span class="input-group-text text-sm bg-gray-50 text-gray-500">৳</span>
                                        <input type="number" step="0.01" id="default_amount" name="default_amount" class="form-control text-sm" placeholder="0.00" required value="{{ old('default_amount') }}">
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_building_wide" name="is_building_wide" value="1" {{ old('is_building_wide') ? 'checked' : '' }}>
                                        <label class="form-check-label text-xs font-semibold text-gray-700 cursor-pointer" for="is_building_wide">Apply building-wide to all active flats</label>
                                    </div>
                                </div>

                                <button type="submit" class="w-100 btn btn-primary py-2 font-semibold text-xs tracking-wide shadow-sm mt-4">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Template Heading
                                </button>
                            </form>
                        </div>
                    </x-slot:add>
                </x-mobile-panel-toggles>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fa-solid fa-gears text-indigo-500 me-2"></i> Baseline Config Master List
                </h3>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-b border-gray-100">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-[10px] tracking-wider border-0">
                            <tr>
                                <th class="py-3 px-4">System Key</th>
                                <th class="py-3 px-4">Bill type</th>
                                <th class="py-3 px-4">Label Name</th>
                                <th class="py-3 px-4">Default Rate</th>
                                <th class="py-3 px-4 text-center">Scope</th>
                                <th class="py-3 px-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($templates as $template)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded text-pink-600 font-mono">{{ $template->charge_key }}</code></td>
                                    <td class="px-4 text-gray-600">{{ $template->billType?->label ?? '—' }}</td>
                                    <td class="px-4 font-semibold text-gray-700">{{ $template->label }}</td>
                                    <td class="px-4 font-medium text-gray-900">৳{{ number_format($template->default_amount, 2) }}</td>
                                    <td class="px-4 text-center">
                                        @if($template->is_building_wide)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="fa-solid fa-building me-1"></i> Building-Wide
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                <i class="fa-solid fa-user me-1"></i> Ad-Hoc / Manual
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 text-end">
                                        <div class="inline-flex gap-2">
                                            <button class="btn btn-sm btn-light text-xs font-semibold px-3 border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-100" 
                                                @click="
                                                    editAction = '{{ route('charge-templates.update', $template->id) }}';
                                                    editForm.bill_type_id = '{{ $template->bill_type_id ?? '' }}';
                                                    editForm.charge_key = '{{ $template->charge_key }}';
                                                    editForm.label = '{{ $template->label }}';
                                                    editForm.default_amount = '{{ $template->default_amount }}';
                                                    editForm.is_building_wide = {{ $template->is_building_wide ? 'true' : 'false' }};
                                                    isEditOpen = true;
                                                ">
                                                <i class="fa-solid fa-pen-to-square text-indigo-500"></i> Edit
                                            </button>

                                            <form action="{{ route('charge-templates.destroy', $template->id) }}" method="POST" class="m-0" onsubmit="return confirm('Delete this charge template permanently?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-xs font-semibold px-3 shadow-sm">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-400 text-xs italic">No templates defined. Use the input form to seed values.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all" 
            x-show="isEditOpen" 
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>
            
            <div class="bg-white rounded-xl shadow-xl border border-gray-100 max-w-md w-full overflow-hidden" @click.away="isEditOpen = false">
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider"><i class="fa-solid fa-file-pen me-2 text-indigo-500"></i> Modify Charge Template</h4>
                    <button @click="isEditOpen = false" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form x-bind:action="editAction" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Bill type / Charge type</label>
                        <select name="bill_type_id" x-model="editForm.bill_type_id" class="form-select text-sm" :required="editForm.is_building_wide">
                            <option value="">Select bill type…</option>
                            @foreach($billTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->label }}</option>
                            @endforeach
                        </select>
                        <span class="text-[10px] text-gray-400 block mt-1">Required for building-wide templates to appear on generated bills.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">System Slug (Locked)</label>
                        <input type="text" name="charge_key" x-model="editForm.charge_key" readonly class="form-control text-sm bg-gray-100 cursor-not-allowed text-gray-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Display Label</label>
                        <input type="text" name="label" x-model="editForm.label" required class="form-control text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Default Amount (BDT)</label>
                        <div class="input-group">
                            <span class="input-group-text text-sm bg-gray-50 text-gray-500">৳</span>
                            <input type="number" step="0.01" name="default_amount" x-model="editForm.default_amount" required class="form-control text-sm">
                        </div>
                    </div>

                    <div class="pt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="edit_is_building_wide" name="is_building_wide" value="1" x-model="editForm.is_building_wide">
                            <label class="form-check-label text-xs font-semibold text-gray-700 cursor-pointer" for="edit_is_building_wide">Apply building-wide to all active flats</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 mt-6">
                        <button type="button" class="btn btn-sm btn-light border px-4 py-2 font-medium text-xs text-gray-600" @click="isEditOpen = false">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary px-4 py-2 font-semibold text-xs tracking-wide shadow-sm">Update Modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</section>

@endsection

@section('js')
<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
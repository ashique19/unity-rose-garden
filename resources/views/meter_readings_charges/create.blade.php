@extends('layouts.layout')
@section('content')

<section class="features-section" x-data="{
    templates: {{ $chargeTemplates->keyBy('id')->toJson() }},
    // Pre-populate the array instantly using PHP for all building-wide configurations
    customCharges: [
        @foreach($chargeTemplates->where('is_building_wide', true) as $template)
        { 
            template_id: '{{ $template->id }}', 
            label: '{{ addslashes($template->label) }}', 
            amount: '{{ $template->default_amount }}', 
            notes: '' 
        },
        @endforeach
    ]
}">
  <div class="container pt-3">
    <div class="features-header mb-0">
      <div class="section-label reveal">Add <em>Single Reading & Monthly Ledger</em></div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4 shadow-sm" role="alert">
            <div class="font-bold text-sm mb-1">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> 
                Validation Failed. Please check the inputs below:
            </div>
            <ul class="list-disc list-inside text-xs space-y-1 mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="reveal reveal-delay-1">
    
        <form action="{{ route('meter-readings-and-charges.store') }}" method="POST">
            @csrf
            
            @if ($errors->any())
                <div class="alert alert-danger mb-4 shadow-sm" role="alert">
                    <div class="font-bold text-sm mb-1">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> 
                        Validation Failed. Please check the inputs below:
                    </div>
                    <ul class="list-disc list-inside text-xs space-y-1 mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <div class="mb-3">
                <label for="flat_id" class="form-label font-weight-bold">Select Flat</label>
                <select class="form-select @error('flat_id') is-invalid @enderror" id="flat_id" name="flat_id" required>
                    <option value="" selected disabled>Choose a flat...</option>
                    @foreach($flats as $flat)
                        <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>
                            Flat {{ $flat->name }}
                        </option>
                    @endforeach
                </select>
                @error('flat_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="reading_date" class="form-label">Reading Date</label>
                <input type="date" class="form-control @error('reading_date') is-invalid @enderror" id="reading_date" name="reading_date" value="{{ old('reading_date', date('Y-m-d')) }}" required>
                @error('reading_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="reading_unit" class="form-label">Reading Unit</label>
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control @error('reading_unit') is-invalid @enderror" id="reading_unit" name="reading_unit" placeholder="0.00" value="{{ old('reading_unit') }}" required>
                    <span class="input-group-text">m³</span>
                </div>
                @error('reading_unit')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="border-t border-gray-100 pt-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-sm font-bold uppercase tracking-wider text-gray-700">
                        <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Monthly Bill Line Items
                    </h4>
                    <button type="button" class="btn btn-sm btn-outline-success px-3" @click="customCharges.push({ template_id: '', label: '', amount: '0.00', notes: '' })">
                        <i class="fa-solid fa-plus me-1"></i> Add Custom Charge
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(charge, index) in customCharges" :key="index">
                        <div class="row g-2 align-items-center bg-light p-3 rounded border border-gray-200">
                            
                            <div class="col-md-3">
                                <select class="form-select text-sm" 
                                        :name="'custom_charges[' + index + '][template_id]'" 
                                        x-model="charge.template_id" 
                                        @change="
                                            if(templates[charge.template_id]) {
                                                charge.label = templates[charge.template_id].label;
                                                charge.amount = templates[charge.template_id].default_amount;
                                            }
                                        ">
                                    <option value="">-- Ad-Hoc / Custom Freehand --</option>
                                    @foreach($chargeTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <input type="text" class="form-control text-sm" 
                                        :name="'custom_charges[' + index + '][label]'" 
                                        placeholder="Line Item Heading" 
                                        x-model="charge.label" required>
                            </div>

                            <div class="col-md-2">
                                <div class="input-group">
                                    <span class="input-group-text text-sm">৳</span>
                                    <input type="number" step="0.01" class="form-control text-sm" 
                                        :name="'custom_charges[' + index + '][amount]'" 
                                        placeholder="0.00" 
                                        x-model="charge.amount" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <input type="text" class="form-control text-sm" 
                                    :name="'custom_charges[' + index + '][notes]'" 
                                    placeholder="Context/Internal notes" 
                                    x-model="charge.notes">
                            </div>

                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" @click="customCharges.splice(index, 1)" title="Remove this charge row">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>

                        </div>
                    </template>
                </div>
            </div>
            
            <div class="cta-actions pt-2">
                <button type="submit" class="btn-cta-primary">
                    Save Statement
                    <span>→</span>
                </button>
            </div>
        </form>


    </div>

  </div>
</section>

@stop
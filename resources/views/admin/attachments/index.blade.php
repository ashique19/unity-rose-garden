@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20" x-data="attachmentGallery()">
    <div class="container" style="max-width: 1100px;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Attachments</h1>
                <p class="text-muted mb-0">
                    Media gallery for bill photos. Images are resized in your browser (max 1200×1200) and compressed before upload.
                </p>
            </div>
            <form method="get" class="d-flex align-items-center gap-2">
                <label for="month" class="form-label mb-0">Billing month</label>
                <input type="month" name="month" id="month" class="form-control"
                       value="{{ $selectedMonth?->format('Y-m') }}" onchange="this.form.submit()">
                @if($selectedMonth)
                    <a href="{{ route('admin.attachments.index') }}" class="btn btn-outline-secondary btn-sm">All</a>
                @endif
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="mobile-panel-group">
            <div class="mobile-panel-toolbar d-md-none mb-3" role="toolbar" aria-label="Add">
                <button
                    type="button"
                    class="mobile-panel-toggle"
                    @click="addOpen = !addOpen"
                    :class="{ 'is-active': addOpen }"
                    :aria-expanded="addOpen.toString()"
                    aria-controls="mobile-panel-add"
                    aria-label="Toggle upload form"
                    title="Add"
                >
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                </button>
            </div>
            <div id="mobile-panel-add" class="mobile-panel mobile-panel-add" :class="{ 'is-open': addOpen }">
                <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
                    <h2 class="h6 fw-bold mb-3">Upload bill photo</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" x-model="title" placeholder="e.g. WASA July 2026">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Billing month</label>
                            <input type="month" class="form-control" x-model="billMonth">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Note</label>
                            <input type="text" class="form-control" x-model="note" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Photo</label>
                            <input type="file" class="form-control" accept="image/*" @change="onFileSelected($event)" :disabled="uploading">
                            <div class="form-text">
                                Browser resize to max 1200×1200 + JPEG compress, then server confirms size.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 mt-3" x-show="previewUrl || uploading || status">
                        <template x-if="previewUrl">
                            <img :src="previewUrl" alt="Preview" class="rounded border" style="max-width: 160px; max-height: 160px; object-fit: contain;">
                        </template>
                        <div class="small">
                            <div x-show="status" x-text="status" class="text-muted"></div>
                            <div x-show="error" class="text-danger" x-text="error"></div>
                        </div>
                        <button type="button" class="btn btn-primary" @click="upload" :disabled="!fileBlob || uploading">
                            <span x-show="!uploading">Upload</span>
                            <span x-show="uploading">Uploading…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            @forelse($attachments as $attachment)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="border rounded-3 bg-white shadow-sm h-100 overflow-hidden d-flex flex-column">
                        <a href="{{ $attachment->url() }}" target="_blank" rel="noopener" class="ratio ratio-1x1 bg-light d-block">
                            <img src="{{ $attachment->url() }}" alt="{{ $attachment->title }}"
                                 class="w-100 h-100" style="object-fit: cover;">
                        </a>
                        <div class="p-3 flex-grow-1 d-flex flex-column">
                            <div class="fw-semibold text-dark small mb-1 text-truncate" title="{{ $attachment->title }}">
                                {{ $attachment->title }}
                            </div>
                            <div class="text-muted" style="font-size: 11px;">
                                @if($attachment->bill_month)
                                    {{ $attachment->bill_month->format('M Y') }} ·
                                @endif
                                {{ $attachment->width }}×{{ $attachment->height }}
                                @if($attachment->size_bytes)
                                    · {{ number_format($attachment->size_bytes / 1024, 0) }} KB
                                @endif
                            </div>
                            @if($attachment->note)
                                <div class="text-muted small mt-1 text-truncate" title="{{ $attachment->note }}">{{ $attachment->note }}</div>
                            @endif
                            <div class="mt-auto pt-3 d-flex flex-wrap gap-2">
                                <button type="button"
                                        class="btn btn-sm btn-outline-dark"
                                        data-share-url="{{ $attachment->absoluteUrl() }}"
                                        @click="copyLink($event.currentTarget.dataset.shareUrl)">
                                    Copy link
                                </button>
                                <a href="{{ $attachment->url() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Open</a>
                                <form method="post" action="{{ route('admin.attachments.destroy', $attachment) }}" class="m-0"
                                      onsubmit="return confirm('Delete this photo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="border rounded-3 bg-white p-5 text-center text-muted">
                        No attachments yet. Upload a bill photo to start the gallery.
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $attachments->links() }}
        </div>

        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;" x-show="toast" x-transition>
            <div class="toast show align-items-center text-bg-dark border-0" role="status">
                <div class="d-flex">
                    <div class="toast-body" x-text="toast"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
function attachmentGallery() {
    return {
        addOpen: @json($errors->any()),
        title: '',
        note: '',
        billMonth: @json($selectedMonth?->format('Y-m') ?? now()->format('Y-m')),
        fileBlob: null,
        previewUrl: null,
        uploading: false,
        status: '',
        error: '',
        toast: '',
        maxEdge: 1200,
        quality: 0.82,

        async onFileSelected(event) {
            this.error = '';
            this.status = '';
            this.fileBlob = null;
            if (this.previewUrl) {
                URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
            }

            const file = event.target.files?.[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                this.error = 'Please choose an image file.';
                return;
            }

            try {
                this.status = 'Resizing & compressing in browser…';
                this.fileBlob = await this.resizeAndCompress(file);
                this.previewUrl = URL.createObjectURL(this.fileBlob);
                const kb = Math.round(this.fileBlob.size / 1024);
                this.status = `Ready to upload (~${kb} KB, max ${this.maxEdge}×${this.maxEdge}).`;
                if (!this.title) {
                    this.title = file.name.replace(/\.[^.]+$/, '').slice(0, 80);
                }
            } catch (e) {
                this.error = e.message || 'Could not process image in browser.';
                this.status = '';
            }
        },

        resizeAndCompress(file) {
            const maxEdge = this.maxEdge;
            const quality = this.quality;

            return new Promise((resolve, reject) => {
                const img = new Image();
                const objectUrl = URL.createObjectURL(file);

                img.onload = () => {
                    URL.revokeObjectURL(objectUrl);
                    let { width, height } = img;
                    const scale = Math.min(1, maxEdge / Math.max(width, height));
                    width = Math.max(1, Math.round(width * scale));
                    height = Math.max(1, Math.round(height * scale));

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#fff';
                    ctx.fillRect(0, 0, width, height);
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Browser could not compress this image.'));
                            return;
                        }
                        resolve(blob);
                    }, 'image/jpeg', quality);
                };

                img.onerror = () => {
                    URL.revokeObjectURL(objectUrl);
                    reject(new Error('Could not read image.'));
                };

                img.src = objectUrl;
            });
        },

        async upload() {
            if (!this.fileBlob || this.uploading) return;
            this.uploading = true;
            this.error = '';
            this.status = 'Uploading…';

            const form = new FormData();
            form.append('photo', this.fileBlob, (this.title || 'bill-photo').replace(/[^\w\-]+/g, '_') + '.jpg');
            form.append('title', this.title || '');
            form.append('note', this.note || '');
            if (this.billMonth) form.append('bill_month', this.billMonth);

            try {
                const res = await fetch(@json(route('admin.attachments.store')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: form,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || (data.errors && Object.values(data.errors).flat()[0]) || 'Upload failed.');
                }
                window.location.reload();
            } catch (e) {
                this.error = e.message || 'Upload failed.';
                this.status = '';
                this.uploading = false;
            }
        },

        async copyLink(url) {
            try {
                await navigator.clipboard.writeText(url);
                this.toast = 'Link copied';
            } catch (e) {
                window.prompt('Copy this link:', url);
                this.toast = 'Link ready to copy';
            }
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toast = ''; }, 2000);
        },
    };
}
</script>
@endsection

@extends('admin.layouts.app')

@section('title', 'Dispatched Post')
@section('page-title', 'Dispatched Post')

@section('content')
<section
    class="section-card"
    x-data='postPage()'
>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Posts ({{ $posts->total() }})</h3>
        <button type="button" class="btn-brand !px-4 !py-2 text-sm" @click="openCreate()">
            <i class="ri-add-line"></i>
            <span>New post</span>
        </button>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="mb-1 font-semibold">Please fix the following errors:</p>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <form method="GET" action="{{ route('admin.posts') }}" class="flex flex-wrap items-center gap-2">
            <label for="per_page" class="text-sm font-semibold text-slate-700">Show</label>
            <select id="per_page" name="per_page" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm" onchange="this.form.submit()">
                @foreach($allowedPerPage as $option)
                    <option value="{{ $option }}" {{ (int) $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <span class="text-sm text-slate-600">entries</span>

            @if(request()->filled('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
        </form>

        <form method="GET" action="{{ route('admin.posts') }}" class="flex items-center gap-2">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <label for="search" class="text-sm font-semibold text-slate-700">Search:</label>
            <input
                id="search"
                type="search"
                name="search"
                value="{{ $search }}"
                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm"
                placeholder="doctor, consignment, post by, remark"
            >
            <button type="submit" class="btn btn-primary !px-3 !py-1.5 text-xs">Go</button>
            @if(request()->filled('search'))
                <a href="{{ route('admin.posts', ['per_page' => $perPage]) }}" class="btn btn-default !px-3 !py-1.5 text-xs">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table min-w-[1200px]">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>Date of post</th>
                    <th>Doctor name</th>
                    <th>Document name</th>
                    <th>Consignment no.</th>
                    <th>Post by</th>
                    <th>Recieved by</th>
                    <th>Recieved date</th>
                    <th>Created by</th>
                    <th>Remark</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    @php
                        $doctorUrl = $post->enrollment
                            ? route('admin.doctors.show', $post->enrollment->id)
                            : null;

                        $createdByName = trim((string) ($post->creator?->name ?? ''));
                        if ($createdByName === '') {
                            $first = trim((string) ($post->creator?->first_name ?? ''));
                            $last = trim((string) ($post->creator?->last_name ?? ''));
                            $createdByName = trim($first . ' ' . $last);
                        }
                        if ($createdByName === '') {
                            $createdByName = 'System';
                        }
                    @endphp
                    <tr>
                        <td><b>{{ $posts->firstItem() + $loop->index }}</b></td>
                        <td><b>{{ optional($post->post_doc_date)->format('d/m/Y') ?? '—' }}</b></td>
                        <td>
                            <b>
                                @if($doctorUrl)
                                    <a href="{{ $doctorUrl }}" target="_blank" class="text-blue-700 hover:underline">{{ $post->doctor_name ?? '—' }}</a>
                                @else
                                    {{ $post->doctor_name ?? '—' }}
                                @endif
                            </b>
                        </td>
                        <td>
                            <b>
                                @if($post->post_doc_file)
                                    {{ basename($post->post_doc_file) }}
                                @else
                                    —
                                @endif
                            </b>
                        </td>
                        <td><b>{{ $post->post_doc_consignment_no ?? '—' }}</b></td>
                        <td><b>{{ $post->post_doc_by ?? '—' }}</b></td>
                        <td><b>{{ $post->post_doc_recieved_by ?? '—' }}</b></td>
                        <td><b>{{ optional($post->post_doc_recieved_date)->format('d/m/Y') ?? '—' }}</b></td>
                        <td><b>{{ $createdByName }}</b></td>
                        <td><b>{{ $post->post_doc_remark ?? '—' }}</b></td>
                        <td>
                            <div class="flex flex-wrap items-center gap-1">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-200"
                                    title="Edit"
                                    @click="openEdit({{ $post->id }})"
                                >
                                    <i class="ri-pencil-line"></i>
                                </button>

                                @if($post->post_doc_file)
                                    <a
                                        href="{{ asset('storage/' . $post->post_doc_file) }}"
                                        class="inline-flex items-center gap-1 rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-200"
                                        title="Download"
                                        download
                                    >
                                        <i class="ri-download-2-line"></i>
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-1 rounded bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-200"
                                        title="Delete"
                                    >
                                        <i class="ri-delete-bin-5-line"></i>
                                    </button>
                                </form>

                                <a
                                    target="_blank"
                                    href="{{ $post->tracking_link ?: '#' }}"
                                    class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold {{ $post->tracking_link ? 'bg-cyan-100 text-cyan-700 hover:bg-cyan-200' : 'pointer-events-none bg-slate-100 text-slate-400' }}"
                                    title="Track"
                                    @if(empty($post->tracking_link)) aria-disabled="true" tabindex="-1" @endif
                                >
                                    <i class="ri-external-link-line"></i>
                                    <span>Track</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-slate-500">No data available in table</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            Showing {{ $posts->firstItem() ?? 0 }} to {{ $posts->lastItem() ?? 0 }} of {{ $posts->total() }} entries
        </p>
        <div>
            {{ $posts->links() }}
        </div>
    </div>

    <div
        x-show="modalOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4"
        style="display: none;"
        @keydown.escape.window="modalOpen = false"
    >
        <div class="w-full max-w-2xl rounded-xl bg-white shadow-2xl" @click.away="closeModal()">
            <form id="postForm" :action="formAction" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="post_form_method" value="POST" disabled>
                <input type="hidden" name="post_id" id="post_id" value="">

                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 class="text-lg font-semibold" x-text="modalTitle">New post</h3>
                    <button type="button" class="text-2xl leading-none text-slate-500 hover:text-slate-700" @click="closeModal()">&times;</button>
                </div>

                <div class="max-h-[75vh] space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <label for="doctor" class="mb-1 block text-sm font-semibold">Doctor <span class="text-red-600">*</span></label>
                        <select id="doctor" name="doctor" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                            <option value="">--Select doctor--</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" {{ old('doctor') == $doctor->id ? 'selected' : '' }}>{{ $doctor->doctor_name }}{{ $doctor->money_rc_no ? ' (' . $doctor->money_rc_no . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="post_doc_date" class="mb-1 block text-sm font-semibold">Post date <span class="text-red-600">*</span></label>
                            <input type="text" id="post_doc_date" class="datepicker w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="post_doc_date" value="{{ old('post_doc_date') }}" autocomplete="off" required>
                        </div>
                        <div>
                            <label for="post_doc_recieved_date" class="mb-1 block text-sm font-semibold">Recieved date <span class="text-red-600">*</span></label>
                            <input type="text" id="post_doc_recieved_date" class="datepicker w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="post_doc_recieved_date" value="{{ old('post_doc_recieved_date') }}" autocomplete="off" required>
                        </div>
                    </div>

                    <div>
                        <label for="post_doc_consignment_no" class="mb-1 block text-sm font-semibold">Consignment number <span class="text-red-600">*</span></label>
                        <input type="text" id="post_doc_consignment_no" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="post_doc_consignment_no" value="{{ old('post_doc_consignment_no') }}" required>
                    </div>

                    <div>
                        <label for="post_doc_by" class="mb-1 block text-sm font-semibold">Post by <span class="text-red-600">*</span></label>
                        <input type="text" id="post_doc_by" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="post_doc_by" value="{{ old('post_doc_by') }}" required>
                    </div>

                    <div>
                        <label for="post_doc_recieved_by" class="mb-1 block text-sm font-semibold">Recieved by</label>
                        <input type="text" id="post_doc_recieved_by" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="post_doc_recieved_by" value="{{ old('post_doc_recieved_by') }}">
                    </div>

                    <div>
                        <label for="post_doc_remark" class="mb-1 block text-sm font-semibold">Remark <span class="text-red-600">*</span></label>
                        <input type="text" id="post_doc_remark" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="post_doc_remark" value="{{ old('post_doc_remark') }}" required>
                    </div>

                    <div>
                        <label for="tracking_link" class="mb-1 block text-sm font-semibold">Tracking link</label>
                        <input type="url" id="tracking_link" name="tracking_link" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ old('tracking_link') }}" placeholder="https://example.com/track/123">
                    </div>

                    <div>
                        <label for="post_doc_file" class="mb-1 block text-sm font-semibold">Document file</label>
                        <input type="file" id="post_doc_file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="post_doc_file">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" class="btn btn-default" @click="closeModal()">Close</button>
                    <button type="submit" class="btn btn-primary" x-text="submitLabel">Submit</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        function postPage() {
            return {
                modalOpen: false,
                modalMode: 'create',
                formAction: @json(route('admin.posts.store')),
                modalTitle: 'New post',
                submitLabel: 'Submit',
                openCreate() {
                    this.modalMode = 'create';
                    this.formAction = @json(route('admin.posts.store'));
                    this.modalTitle = 'New post';
                    this.submitLabel = 'Submit';
                    this.modalOpen = true;
                    this.resetForm();
                },
                closeModal() {
                    this.modalOpen = false;
                },
                resetForm() {
                    const form = document.getElementById('postForm');
                    if (!form) return;

                    form.reset();
                    const postIdField = document.getElementById('post_id');
                    if (postIdField) postIdField.value = '';
                    const methodField = document.getElementById('post_form_method');
                    if (methodField) {
                        methodField.value = 'POST';
                        methodField.disabled = true;
                    }
                },
                async openEdit(postId) {
                    try {
                        const response = await fetch(@json(route('admin.posts.edit', ['post' => '__POST_ID__'])).replace('__POST_ID__', postId), {
                            headers: { 'Accept': 'application/json' }
                        });
                        const payload = await response.json();
                        if (!payload.success) {
                            throw new Error('Unable to load post data.');
                        }

                        const post = payload.post || {};
                        this.modalMode = 'edit';
                        this.formAction = @json(route('admin.posts.update', ['post' => '__POST_ID__'])).replace('__POST_ID__', postId);
                        this.modalTitle = 'Edit post';
                        this.submitLabel = 'Update';
                        this.modalOpen = true;

                        const postIdField = document.getElementById('post_id');
                        const methodField = document.getElementById('post_form_method');
                        const form = document.getElementById('postForm');

                        if (postIdField) postIdField.value = postId;
                        if (methodField) {
                            methodField.value = 'PUT';
                            methodField.disabled = false;
                        }
                        if (form) {
                            form.action = this.formAction;
                            document.getElementById('doctor').value = post.enrollment_id || '';
                            document.getElementById('post_doc_date').value = post.post_doc_date || '';
                            document.getElementById('post_doc_consignment_no').value = post.post_doc_consignment_no || '';
                            document.getElementById('post_doc_by').value = post.post_doc_by || '';
                            document.getElementById('post_doc_recieved_date').value = post.post_doc_recieved_date || '';
                            document.getElementById('post_doc_recieved_by').value = post.post_doc_recieved_by || '';
                            document.getElementById('post_doc_remark').value = post.post_doc_remark || '';
                            document.getElementById('tracking_link').value = post.tracking_link || '';
                        }

                        if (typeof flatpickr !== 'undefined') {
                            document.querySelectorAll('.datepicker').forEach(function (input) {
                                if (input._flatpickr) {
                                    input._flatpickr.setDate(input.value, true, 'd/m/Y');
                                }
                            });
                        }
                    } catch (error) {
                        alert(error.message || 'Unable to load post data.');
                    }
                }
            };
        }

        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.datepicker').forEach(function (input) {
                flatpickr(input, { dateFormat: 'd/m/Y' });
            });
        }
    </script>
@endpush

@extends('admin.layouts.app')

@section('title', 'Dispatched Post')
@section('page-title', 'Dispatched Post')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Posts ({{ $posts->total() }})</h3>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.posts') }}" class="flex items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search doctor, consignment, remark"
                    class="master-search-input"
                >
                <button type="submit" class="btn btn-primary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.posts') }}" class="btn btn-default">Clear</a>
                @endif
            </form>

            <button type="button" class="btn-brand !px-4 !py-2 text-sm" onclick="openPostModal()">
                <i class="ri-add-line"></i>
                <span>New post</span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
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
                    <tr>
                        <td>{{ $posts->firstItem() + $loop->index }}</td>
                        <td>{{ optional($post->post_doc_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $post->doctor_name ?? '—' }}</td>
                        <td>
                            @if($post->post_doc_file)
                                <a href="{{ asset('storage/' . $post->post_doc_file) }}" target="_blank" class="text-blue-600 underline">View</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $post->post_doc_consignment_no ?? '—' }}</td>
                        <td>{{ $post->post_doc_by ?? '—' }}</td>
                        <td>{{ $post->post_doc_recieved_by ?? '—' }}</td>
                        <td>{{ optional($post->post_doc_recieved_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $post->creator?->name ?? '—' }}</td>
                        <td>{{ $post->post_doc_remark ?? '—' }}</td>
                        <td>
                            <div class="flex gap-2">
                                @if($post->tracking_link)
                                    <a href="{{ $post->tracking_link }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-sky-100 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-200">Track</a>
                                @endif
                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-slate-500">No data available in table</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($posts->hasPages())
        <div class="mt-4">{{ $posts->links() }}</div>
    @endif
</section>

<div id="postModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4">
    <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-semibold">New post</h3>
            <button type="button" class="text-2xl leading-none" onclick="closePostModal()">&times;</button>
        </div>
        <div class="max-h-[75vh] overflow-y-auto px-5 py-4">
            <form action="{{ route('admin.posts.store') }}" method="post" class="form-horizontal" id="post_upload_form" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label class="control-label" for="doctor">Doctor: <span style="color: red;">*</span></label>
                    <select id="doctor" name="doctor" class="form-control select2" required>
                        <option value="0">--Select doctor--</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}{{ $doctor->money_rc_no ? ' (' . $doctor->money_rc_no . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label class="control-label" for="post_doc_date">Post date: <span style="color: red;">*</span></label>
                    <input type="text" id="post_doc_date" class="form-control datepicker" name="post_doc_date" autocomplete="off" required>
                </div>

                <div class="form-group mb-3">
                    <label class="control-label" for="post_doc_consignment_no">Consignment number: <span style="color: red;">*</span></label>
                    <input type="text" id="post_doc_consignment_no" class="form-control" name="post_doc_consignment_no" required>
                </div>

                <div class="form-group mb-3">
                    <label class="control-label" for="post_doc_by">Post by: <span style="color: red;">*</span></label>
                    <input type="text" id="post_doc_by" class="form-control" name="post_doc_by" required>
                </div>

                <div class="form-group mb-3">
                    <label class="control-label" for="post_doc_recieved_date">Recieved date: <span style="color: red;">*</span></label>
                    <input type="text" id="post_doc_recieved_date" class="form-control datepicker" name="post_doc_recieved_date" autocomplete="off" required>
                </div>

                <div class="form-group mb-3">
                    <label class="control-label" for="post_doc_recieved_by">Recieved by:</label>
                    <input type="text" id="post_doc_recieved_by" class="form-control" name="post_doc_recieved_by">
                </div>

                <div class="form-group mb-3">
                    <label class="control-label" for="post_doc_remark">Remark: <span style="color: red;">*</span></label>
                    <input type="text" id="post_doc_remark" class="form-control" name="post_doc_remark" required>
                </div>

                <div class="form-group mb-3">
                    <label class="control-label" for="tracking_link">Tracking link:</label>
                    <p class="text-xs text-slate-500">Insert valid link (Example: http://www.example.com, https://www.example.com)</p>
                    <input type="text" id="tracking_link" name="tracking_link" class="form-control">
                </div>

                <div class="form-group mb-4">
                    <label class="control-label" for="post_doc_file">Document file:</label>
                    <input type="file" id="post_doc_file" class="form-control" name="post_doc_file">
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                    <a href="javascript:void(0)" class="btn btn-default" onclick="closePostModal()">Close</a>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function openPostModal() {
            const modal = document.getElementById('postModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePostModal() {
            const modal = document.getElementById('postModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.datepicker').forEach(input => {
                flatpickr(input, { dateFormat: 'd/m/Y' });
            });
        }

        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    </script>
@endpush
@endsection

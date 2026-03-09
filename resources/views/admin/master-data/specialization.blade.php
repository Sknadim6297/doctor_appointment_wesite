@extends('admin.layouts.app')

@section('title', 'Specialization')
@section('page-title', 'Specialization Management')

@section('content')
<section class="section-card" x-data="{
    addModalOpen: @json($errors->has('specialization') && !old('edit_id')),
    editModalOpen: @json($errors->has('specialization') && old('edit_id')),
    editId: @json(old('edit_id')),
    editName: @json(old('specialization')) || '',
    openEdit(id, name) {
        this.editId = id;
        this.editName = name;
        this.editModalOpen = true;
    }
}">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Specialization ({{ $totalSpecializations ?? 0 }})</h3>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.specialization') }}" class="flex items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search specialization"
                    class="master-search-input"
                >
                <button type="submit" class="btn btn-primary">Search</button>
                @if(!empty($search))
                    <a href="{{ route('admin.specialization') }}" class="btn btn-default">Clear</a>
                @endif
            </form>

            <button type="button" @click="addModalOpen = true" class="btn-brand !px-4 !py-2 text-sm">
                <i class="ri-pencil-line"></i>
                <span>Add Specialization</span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table" id="specializationTable">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Specialization</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="specializationTableBody">
                @forelse($specializations as $item)
                    <tr>
                        <td>{{ $specializations->firstItem() + $loop->index }}</td>
                        <td>{{ $item->name }}</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button"
                                        @click="openEdit({{ $item->id }}, @js($item->name))"
                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200"
                                        title="Edit">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </button>

                                <form method="POST" action="{{ route('admin.specialization.destroy', $item) }}" onsubmit="return confirm('Delete specialization?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200" title="Delete">
                                        <i class="ri-delete-bin-line"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-slate-500">No specialization records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($specializations->hasPages())
        <div class="mt-4">
            {{ $specializations->links() }}
        </div>
    @endif

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="addModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="addModalOpen = false">
            <form action="{{ route('admin.specialization.store') }}" method="POST" class="form-horizontal" id="adduserrole_frm" onsubmit="return validateSpecializationForm('specialization', 'specialization_message', true)">
                @csrf
                <div class="modal-header">
                    <h3>Add Specialization</h3>
                    <button type="button" class="close" @click="addModalOpen = false">x</button>
                </div>

                <div class="modal-body">
                    <div class="control-group" id="price_control">
                        <label class="control-label" for="specialization">specialization</label>
                        <div class="controls">
                            <input class="form-control" type="text" id="specialization" name="specialization" value="{{ old('specialization') }}" onblur="validateSpecializationForm('specialization', 'specialization_message', false)">
                            <span class="help-inline" id="specialization_message" style="display:{{ $errors->has('specialization') && !old('edit_id') ? 'inline' : 'none' }}; color:red">{{ $errors->has('specialization') && !old('edit_id') ? $errors->first('specialization') : '' }}</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" @click="addModalOpen = false">Close</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="editModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="editModalOpen = false">
            <form :action="`{{ url('/admin/specialization') }}/${editId}`" method="POST" class="form-horizontal" onsubmit="return validateSpecializationForm('edit_specialization', 'edit_specialization_message', true)">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_id" :value="editId">
                <div class="modal-header">
                    <h3>Edit Specialization</h3>
                    <button type="button" class="close" @click="editModalOpen = false">x</button>
                </div>

                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label" for="edit_specialization">specialization</label>
                        <div class="controls">
                            <input class="form-control" type="text" id="edit_specialization" name="specialization" x-model="editName" onblur="validateSpecializationForm('edit_specialization', 'edit_specialization_message', false)">
                            <span class="help-inline" id="edit_specialization_message" style="display:{{ $errors->has('specialization') && old('edit_id') ? 'inline' : 'none' }}; color:red">{{ $errors->has('specialization') && old('edit_id') ? $errors->first('specialization') : '' }}</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" @click="editModalOpen = false">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>
</section>

<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;
        toast.innerHTML = `<span>${message}</span><button type="button" class="toast-close" aria-label="Close">x</button>`;
        toast.querySelector('.toast-close').addEventListener('click', function () {
            toast.remove();
        });

        container.appendChild(toast);
        setTimeout(function () { toast.classList.add('show'); }, 10);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 220);
        }, 3200);
    }

    function validateSpecializationForm(inputId, messageId, showToastOnError) {
        const input = document.getElementById(inputId);
        const msg = document.getElementById(messageId);
        const value = (input?.value || '').trim();

        if (!value) {
            if (msg) {
                msg.textContent = 'Specialization is required.';
                msg.style.display = 'inline';
            }
            if (showToastOnError) showToast('Please enter specialization name.', 'error');
            return false;
        }

        if (msg) msg.style.display = 'none';
        return true;
    }

    @if(session('success'))
        showToast(@json(session('success')), 'success');
    @endif
</script>
@endsection

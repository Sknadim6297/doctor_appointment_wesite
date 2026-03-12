@extends('admin.layouts.app')

@section('title', 'Role Management')
@section('page-title', 'Role Management')

@section('content')
<section class="section-card" x-data="{
    addModalOpen: @json($errors->has('role_title') && !old('edit_id')),
    editModalOpen: @json($errors->has('role_title') && old('edit_id')),
    editId: @json(old('edit_id')),
    editRoleTitle: @json(old('role_title')) || '',
    openEdit(id, roleTitle) {
        this.editId = id;
        this.editRoleTitle = roleTitle;
        this.editModalOpen = true;
    }
}">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Role Management ({{ count($roleRows) }})</h3>

        <button type="button" @click="addModalOpen = true" class="btn-brand !px-4 !py-2 text-sm">
            <i class="ri-add-line"></i>
            <span>Add New Role</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table" id="roleManagementTable">
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Role Title</th>
                    <th>Number of Users</th>
                    <th>Created On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roleRows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row->role_title }}</td>
                        <td>{{ $row->users_count }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') }}</td>
                        <td>
                            <button type="button"
                                    @click="openEdit({{ $row->id }}, @js($row->role_title))"
                                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200"
                                    title="Edit">
                                <i class="ri-pencil-line"></i>
                                <span>Edit</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="addModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="addModalOpen = false">
            <form action="{{ route('admin.admin-management.roles.store') }}" method="POST" class="form-horizontal" id="add_role_form" onsubmit="return validateRoleForm('role_title', 'role_title_message', true)">
                @csrf
                <div class="modal-header">
                    <h3>Submit New Role</h3>
                    <button type="button" class="close" @click="addModalOpen = false">x</button>
                </div>

                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label" for="role_title">Role Name</label>
                        <div class="controls">
                            <input type="text" name="role_title" id="role_title" class="form-control" value="{{ old('role_title') }}" autocomplete="off" onblur="validateRoleForm('role_title', 'role_title_message', false)">
                            <span class="help-inline" id="role_title_message" style="display:{{ $errors->has('role_title') && !old('edit_id') ? 'inline' : 'none' }}; color:red">{{ $errors->has('role_title') && !old('edit_id') ? $errors->first('role_title') : '' }}</span>
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
            <form :action="`{{ url('/admin/admin-management/roles') }}/${editId}`" method="POST" class="form-horizontal" onsubmit="return validateRoleForm('edit_role_title', 'edit_role_title_message', true)">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_id" :value="editId">

                <div class="modal-header">
                    <h3>Edit Role</h3>
                    <button type="button" class="close" @click="editModalOpen = false">x</button>
                </div>

                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label" for="edit_role_title">Role Name</label>
                        <div class="controls">
                            <input type="text" name="role_title" id="edit_role_title" class="form-control" x-model="editRoleTitle" autocomplete="off" onblur="validateRoleForm('edit_role_title', 'edit_role_title_message', false)">
                            <span class="help-inline" id="edit_role_title_message" style="display:{{ $errors->has('role_title') && old('edit_id') ? 'inline' : 'none' }}; color:red">{{ $errors->has('role_title') && old('edit_id') ? $errors->first('role_title') : '' }}</span>
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

    function validateRoleForm(inputId, messageId, showToastOnError) {
        const input = document.getElementById(inputId);
        const msg = document.getElementById(messageId);
        const value = (input?.value || '').trim();

        if (!value) {
            if (msg) {
                msg.textContent = 'Role name is required.';
                msg.style.display = 'inline';
            }
            if (showToastOnError) showToast('Please enter role name.', 'error');
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

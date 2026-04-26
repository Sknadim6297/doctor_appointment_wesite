@extends('admin.layouts.app')

@section('title', 'Manage Expense Category')
@section('page-title', 'Account Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Expensive categories ({{ $categories->total() }})</h3>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.expense-categories.index') }}" class="flex items-center gap-2">
                <input
                    type="search"
                    name="search"
                    class="master-search-input"
                    placeholder="Search category"
                    value="{{ $search }}"
                >
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search !== '')
                    <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-default">Clear</a>
                @endif
            </form>

            <button type="button" class="btn-brand !px-4 !py-2 text-sm" onclick="add_new_modal();">
                <i class="ri-add-line"></i>
                <span>Add new</span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="example1" class="data-table">
            <thead>
                <tr>
                    <th style="width: 100px;">SL No</th>
                    <th>Category name</th>
                    <th style="width: 180px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td><b>{{ $categories->firstItem() + $loop->index }}</b></td>
                        <td>{{ $category->name }}</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="javascript:void(0);" class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200" onclick='edit_modal({{ $category->id }}, @json($category->name));' title="Edit">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </a>
                                <a href="javascript:void(0);" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200" onclick="delete_expense_cat({{ $category->id }});" title="Delete">
                                    <i class="ri-delete-bin-line"></i>
                                    <span>Delete</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-slate-500">No expense category found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($categories->hasPages())
        <div class="mt-4">
            {{ $categories->links() }}
        </div>
    @endif
</section>

<div id="expense_category_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4">
    <div class="modal-content w-full max-w-2xl" onclick="event.stopPropagation();">
        <form action="{{ route('admin.expense-categories.legacy-store') }}" method="post" accept-charset="utf-8" class="form-horizontal" id="add_new_form" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h3 id="expense_modal_title">Add new expensive category</h3>
                <button type="button" class="close" onclick="close_modal();">x</button>
            </div>
            <div class="modal-body">
                <fieldset>
                    <div class="control-group" id="edit_discount_control">
                        <label class="control-label" for="expensive_cat">Category name</label>
                        <div class="controls">
                            <input type="text" class="form-control" id="expensive_cat" name="expensive_cat">
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" onclick="close_modal();">Close</button>
                <button type="submit" class="btn btn-primary" id="expense_submit_btn" onclick="return add_new_validation()">Add</button>
            </div>
        </form>
    </div>
</div>

<form id="delete_expense_form" method="POST" style="display:none;">
    @csrf
</form>

<script>
function modalElement() {
    return document.getElementById('expense_category_modal');
}

function add_new_modal() {
    var form = document.getElementById('add_new_form');
    form.action = @json(route('admin.expense-categories.legacy-store'));
    document.getElementById('expense_modal_title').textContent = 'Add new expensive category';
    document.getElementById('expense_submit_btn').textContent = 'Add';
    document.getElementById('expensive_cat').value = '';
    modalElement().classList.remove('hidden');
    modalElement().classList.add('flex');
}

function edit_modal(id, name) {
    var form = document.getElementById('add_new_form');
    form.action = @json(route('admin.expense-categories.legacy-update', ['expenseCategory' => '__ID__'])).replace('__ID__', String(id));
    document.getElementById('expense_modal_title').textContent = 'Edit expensive category';
    document.getElementById('expense_submit_btn').textContent = 'Update';
    document.getElementById('expensive_cat').value = name || '';
    modalElement().classList.remove('hidden');
    modalElement().classList.add('flex');
}

function close_modal() {
    modalElement().classList.add('hidden');
    modalElement().classList.remove('flex');
}

function add_new_validation() {
    var value = document.getElementById('expensive_cat').value;
    if (!value || !value.trim()) {
        alert('Please enter category name.');
        document.getElementById('expensive_cat').focus();
        return false;
    }
    return true;
}

function delete_expense_cat(id) {
    if (!confirm('Are you sure you want to delete this category?')) {
        return;
    }

    var form = document.getElementById('delete_expense_form');
    form.action = @json(route('admin.expense-categories.legacy-destroy', ['expenseCategory' => '__ID__'])).replace('__ID__', String(id));
    form.submit();
}

document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'expense_category_modal') {
        close_modal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        close_modal();
    }
});
</script>
@endsection

@extends('admin.layouts.app')

@section('title', 'User Privileges')
@section('page-title', 'User Privileges')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">User Privileges ({{ $totalPrivileges }}) - {{ strtoupper($admin->name) }}</h3>
        <a href="{{ route('admin.admin-management.index') }}" class="btn btn-default">Back To Sub-Admin List</a>
    </div>

    <form method="POST" action="{{ route('admin.admin-management.privileges.update', $admin) }}" id="privilegeForm">
        @csrf

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" id="parent_check_id" class="rounded border-slate-300">
                <span>Select all from this page</span>
            </label>

            <div class="flex items-center gap-2">
                <button type="submit" name="action" value="allow" class="btn btn-primary">Allow</button>
                <button type="submit" name="action" value="disallow" class="btn btn-default">Dis-Allow</button>
            </div>
        </div>

        @error('selected_ids')
            <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="overflow-x-auto">
            <table class="data-table min-w-[920px]" id="example44">
                <thead>
                    <tr>
                        <th></th>
                        <th>SL No.</th>
                        <th>Status</th>
                        <th>Page Title</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sl = 1; @endphp
                    @foreach($groupedPrivileges as $group)
                        <tr>
                            <td colspan="4" class="bg-slate-200 px-3 py-2 text-left font-semibold text-slate-800">{{ $group['group_title'] }}</td>
                        </tr>

                        @foreach($group['items'] as $item)
                            <tr>
                                <td style="width:1px">
                                    <input
                                        type="checkbox"
                                        name="selected_ids[]"
                                        value="{{ $item->id }}"
                                        class="chtest_test rounded border-slate-300"
                                    >
                                </td>
                                <td><b>{{ $sl }}</b></td>
                                <td style="width:1px">
                                    @if($item->is_allowed)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Allowed</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Disallowed</span>
                                    @endif
                                </td>
                                <td>{{ $item->page_title }}</td>
                            </tr>
                            @php $sl++; @endphp
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const parentCheckbox = document.getElementById('parent_check_id');
        const children = document.querySelectorAll('.chtest_test');

        if (!parentCheckbox || !children.length) return;

        parentCheckbox.addEventListener('change', function () {
            children.forEach((checkbox) => {
                checkbox.checked = parentCheckbox.checked;
            });
        });
    });
</script>
@endsection

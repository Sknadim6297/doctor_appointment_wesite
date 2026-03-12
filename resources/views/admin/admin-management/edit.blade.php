@extends('admin.layouts.app')

@section('title', 'Edit Sub-Admin')
@section('page-title', 'Edit Sub-Admin')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <h3 class="section-title mb-0">Edit Sub-Admin</h3>
    <a href="{{ route('admin.admin-management.index') }}" class="btn btn-default">Back To Sub-Admin List</a>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="section-card lg:col-span-2">
        <form method="POST" action="{{ route('admin.admin-management.update', $admin) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="first_name" class="mb-2 block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $admin->first_name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('first_name') border-red-500 @enderror">
                    @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="last_name" class="mb-2 block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $admin->last_name) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('last_name') border-red-500 @enderror">
                    @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $admin->email) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror" autocomplete="off">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="salary" class="mb-2 block text-sm font-medium text-gray-700">Monthly Salary</label>
                    <input type="text" id="salary" name="salary" value="{{ old('salary', $admin->salary) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('salary') border-red-500 @enderror">
                    @error('salary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="employee_no" class="mb-2 block text-sm font-medium text-gray-700">Employee Number</label>
                    <input type="text" id="employee_no" name="employee_no" value="{{ old('employee_no', $admin->employee_no) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('employee_no') border-red-500 @enderror">
                    @error('employee_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $admin->phone) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('phone') border-red-500 @enderror">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="aadhaar_no" class="mb-2 block text-sm font-medium text-gray-700">Aadhaar Number</label>
                    <input type="text" id="aadhaar_no" name="aadhaar_no" value="{{ old('aadhaar_no', $admin->aadhaar_no) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('aadhaar_no') border-red-500 @enderror">
                    @error('aadhaar_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="pan_no" class="mb-2 block text-sm font-medium text-gray-700">PAN Card Number</label>
                    <input type="text" id="pan_no" name="pan_no" value="{{ old('pan_no', $admin->pan_no) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('pan_no') border-red-500 @enderror">
                    @error('pan_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="dob" class="mb-2 block text-sm font-medium text-gray-700">Date Of Birth</label>
                    <input type="date" id="dob" name="dob" value="{{ old('dob', optional($admin->dob)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('dob') border-red-500 @enderror">
                    @error('dob')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="role" class="mb-2 block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('role') border-red-500 @enderror">
                        <option value="">--Select role--</option>
                        @foreach($roleOptions as $role)
                            <option value="{{ $role->role_key }}" {{ old('role', $admin->role) === $role->role_key ? 'selected' : '' }}>{{ $role->role_title }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', $admin->is_active ? 'active' : 'inactive') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $admin->is_active ? 'active' : 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="profile_pic" class="mb-2 block text-sm font-medium text-gray-700">Profile Picture</label>
                    <input type="file" id="profile_pic" name="profile_pic" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('profile_pic') border-red-500 @enderror">
                    @error('profile_pic')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.admin-management.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2 font-medium text-white transition-colors hover:bg-indigo-700">Update</button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-1">
        <div class="section-card">
            <h3 class="mb-6 text-lg font-semibold text-gray-900">Reset Password</h3>

            <form method="POST" action="{{ route('admin.admin-management.reset-password', $admin) }}">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="new_password" class="mb-2 block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="new_password" name="password" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500" placeholder="********">
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" id="new_password_confirmation" name="password_confirmation" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500" placeholder="********">
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-yellow-600 px-4 py-2 font-medium text-white transition-colors hover:bg-yellow-700">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

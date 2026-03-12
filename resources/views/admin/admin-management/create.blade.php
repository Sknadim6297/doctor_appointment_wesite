@extends('admin.layouts.app')

@section('title', 'Add Sub-Admin')
@section('page-title', 'Add Sub-Admin')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Add Sub-Admin</h3>
        <a href="{{ route('admin.admin-management.index') }}" class="btn btn-default">Back To Sub-Admin List</a>
    </div>

    <form method="POST" action="{{ route('admin.admin-management.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="first_name" class="mb-2 block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('first_name') border-red-500 @enderror">
                @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="last_name" class="mb-2 block text-sm font-medium text-gray-700">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('last_name') border-red-500 @enderror">
                @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('email') border-red-500 @enderror" autocomplete="off">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="salary" class="mb-2 block text-sm font-medium text-gray-700">Monthly Salary</label>
                <input type="text" id="salary" name="salary" value="{{ old('salary') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('salary') border-red-500 @enderror" autocomplete="off">
                @error('salary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="employee_no" class="mb-2 block text-sm font-medium text-gray-700">Employee Number</label>
                <input type="text" id="employee_no" name="employee_no" value="{{ old('employee_no') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('employee_no') border-red-500 @enderror">
                @error('employee_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="mb-2 block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('phone') border-red-500 @enderror">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="aadhaar_no" class="mb-2 block text-sm font-medium text-gray-700">Aadhaar Number</label>
                <input type="text" id="aadhaar_no" name="aadhaar_no" value="{{ old('aadhaar_no') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('aadhaar_no') border-red-500 @enderror">
                @error('aadhaar_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="pan_no" class="mb-2 block text-sm font-medium text-gray-700">PAN Card Number</label>
                <input type="text" id="pan_no" name="pan_no" value="{{ old('pan_no') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('pan_no') border-red-500 @enderror">
                @error('pan_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="dob" class="mb-2 block text-sm font-medium text-gray-700">Date Of Birth</label>
                <input type="date" id="dob" name="dob" value="{{ old('dob') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('dob') border-red-500 @enderror">
                @error('dob')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="mb-2 block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                <select id="role" name="role" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('role') border-red-500 @enderror">
                    <option value="">--Select role--</option>
                    @foreach($roleOptions as $role)
                        <option value="{{ $role->role_key }}" {{ old('role') === $role->role_key ? 'selected' : '' }}>{{ $role->role_title }}</option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="mb-2 block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                <select id="status" name="status" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('status') border-red-500 @enderror">
                    <option value="">--Select status--</option>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="profile_pic" class="mb-2 block text-sm font-medium text-gray-700">Profile Picture</label>
                <input type="file" id="profile_pic" name="profile_pic" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('profile_pic') border-red-500 @enderror">
                @error('profile_pic')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500 @error('password') border-red-500 @enderror">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="mt-8 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.admin-management.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2 font-medium text-white transition-colors hover:bg-indigo-700">Submit</button>
        </div>
    </form>
</section>
@endsection

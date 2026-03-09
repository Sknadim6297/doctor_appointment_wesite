@extends('admin.layouts.app')

@section('title', 'Lapse List')
@section('page-title', 'Lapse List')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Lapse List</h2>
    <p class="text-gray-600 mt-1">Doctors with expired memberships</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6">
        <div class="flex items-center justify-center py-20">
            <div class="text-center">
                <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Lapse List</h3>
                <p class="text-gray-500">This module is ready for development</p>
            </div>
        </div>
    </div>
</div>
@endsection

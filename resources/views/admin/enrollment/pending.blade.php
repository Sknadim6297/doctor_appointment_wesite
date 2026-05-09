@extends('admin.layouts.app')

@section('title', 'Pending Approvals')
@section('page-title', 'Pending Enrollment Approvals')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.enrollment') }}" class="btn">Back to Enrollments</a>
</div>

<table class="w-full">
    <thead>
        <tr>
            <th>Customer ID</th>
            <th>Proposer Name</th>
            <th>Agent Name</th>
            <th>Mobile No</th>
            <th>Date of Submission</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($enrollments as $enr)
            <tr>
                <td>{{ $enr->customer_id_no }}</td>
                <td>{{ $enr->doctor_name }}</td>
                <td>{{ $enr->agent_name }}</td>
                <td>{{ $enr->mobile1 }}</td>
                <td>{{ optional($enr->created_at)->format('d M Y H:i') }}</td>
                <td>{{ ucfirst($enr->status) }}</td>
                <td>
                    <a href="{{ route('admin.enrollment.details', $enr->id) }}" class="btn">View</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4">{{ $enrollments->links() }}</div>
@endsection

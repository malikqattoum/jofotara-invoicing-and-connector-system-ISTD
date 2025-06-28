@extends('welcome')
@section('content')
<div class="container mt-4">
    <h2>Vendors Management</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Organization</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendors as $vendor)
            <tr>
                <td>{{ $vendor->name }}</td>
                <td>{{ $vendor->email }}</td>
                <td>{{ $vendor->organization_name }}</td>
                <td>
                    @if($vendor->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Disabled</span>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.toggleVendor', $vendor->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">
                            {{ $vendor->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@extends('welcome')
@section('content')
<div class="container mt-4">
    <h2>Vendor Profile</h2>
    <form method="POST" action="{{ route('vendor.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Organization Name</label>
            <input type="text" name="organization_name" class="form-control" value="{{ old('organization_name', $user->organization_name) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Organization Address</label>
            <input type="text" name="organization_address" class="form-control" value="{{ old('organization_address', $user->organization_address) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Organization Phone</label>
            <input type="text" name="organization_phone" class="form-control" value="{{ old('organization_phone', $user->organization_phone) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Tax Number</label>
            <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $user->tax_number) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Private Key (.pem)</label>
            <input type="file" name="private_key" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Public Certificate (.pem)</label>
            <input type="file" name="public_cert" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>
@endsection

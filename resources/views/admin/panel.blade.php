@extends('welcome')
@section('content')
<div class="container mt-4">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="{{ route('admin.vendors') }}">Manage Vendors</a></li>
        <!-- Add more admin links here as needed -->
    </ul>
</div>
@endsection

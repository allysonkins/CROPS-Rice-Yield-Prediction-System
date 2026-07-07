@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">✏️ Edit User</h4>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card-custom">
    <form action="{{ route('admin.users.update', $userToEdit->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $userToEdit->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $userToEdit->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                    @if(in_array('admin', $allowedRoles))
                        <option value="admin" {{ old('role', $userToEdit->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                    @endif
                    @if(in_array('staff', $allowedRoles))
                        <option value="staff" {{ old('role', $userToEdit->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                    @endif
                    @if(in_array('farmer', $allowedRoles))
                        <option value="farmer" {{ old('role', $userToEdit->role) == 'farmer' ? 'selected' : '' }}>Farmer</option>
                    @endif
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-warning px-4"><i class="bi bi-save"></i> Update User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary px-4">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
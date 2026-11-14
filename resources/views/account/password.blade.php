@extends('main.index')

@section('page-title', 'Change Password')

@section('content')
    <div class="row g-3 justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-key me-2"></i>Change Password</h3>
                </div>
                <form method="POST" action="{{ route('account.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="alert alert-info mb-3" role="alert">
                            <div class="d-flex gap-2">
                                <i class="ti ti-info-circle fs-2"></i>
                                <div>
                                    If you sign in with <strong>Microsoft</strong> or <strong>Google</strong> SSO you do
                                    not need a password for this application.
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label required">Current Password</label>
                                <input type="password" id="current_password" name="current_password"
                                    class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                    autocomplete="current-password" required>
                                @error('current_password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label required">New Password</label>
                                <input type="password" id="password" name="password"
                                    class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                    autocomplete="new-password" required>
                                @error('password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-hint">Minimum 8 characters, containing letters and numbers.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label required">Confirm New Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-control" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <x-global.btn-detail label="Update Password" color="blue" size="sm" type="submit">
                            <x-slot:icon><i class="ti ti-device-floppy"></i></x-slot:icon>
                        </x-global.btn-detail>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

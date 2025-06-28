@extends('layouts.auth-ar')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h3 class="text-center mb-4 fw-bold">تسجيل الدخول</h3>
        <p class="text-center text-muted mb-4">أدخل بياناتك للوصول إلى حسابك</p>

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label fw-bold">
                    <i class="fas fa-envelope me-2"></i>
                    البريد الإلكتروني
                </label>
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="أدخل بريدك الإلكتروني"
                       required
                       autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-bold">
                    <i class="fas fa-lock me-2"></i>
                    كلمة المرور
                </label>
                <div class="position-relative">
                    <input type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           placeholder="أدخل كلمة المرور"
                           required>
                    <button type="button" class="btn position-absolute start-0 top-50 translate-middle-y"
                            onclick="togglePassword()" style="border: none; background: none; z-index: 5;">
                        <i class="fas fa-eye text-muted" id="toggleIcon"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    تذكرني
                </label>
            </div>

            <button type="submit" class="btn btn-primary-custom mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>
                تسجيل الدخول
            </button>

            <div class="text-center">
                @if (Route::has('password.request'))
                    <a class="link-custom" href="{{ route('password.request') }}">
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>
        </form>

        <hr class="my-4">

        <div class="text-center">
            <p class="mb-2">ليس لديك حساب؟</p>
            <a href="{{ route('register.ar') }}" class="btn btn-outline-primary w-100">
                <i class="fas fa-user-plus me-2"></i>
                إنشاء حساب جديد
            </a>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>
@endsection

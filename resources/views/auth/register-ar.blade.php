@extends('layouts.auth-ar')

@section('title', 'إنشاء حساب جديد')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h3 class="text-center mb-4 fw-bold">إنشاء حساب جديد</h3>
        <p class="text-center text-muted mb-4">انضم إلينا وابدأ رحلتك في الفوترة الإلكترونية</p>

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">
                            <i class="fas fa-user me-2"></i>
                            الاسم الكامل
                        </label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="أدخل اسمك الكامل"
                               required
                               autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
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
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="company_name" class="form-label fw-bold">
                            <i class="fas fa-building me-2"></i>
                            اسم الشركة
                        </label>
                        <input type="text"
                               class="form-control @error('company_name') is-invalid @enderror"
                               id="company_name"
                               name="company_name"
                               value="{{ old('company_name') }}"
                               placeholder="أدخل اسم شركتك"
                               required>
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">
                            <i class="fas fa-phone me-2"></i>
                            رقم الهاتف
                        </label>
                        <input type="tel"
                               class="form-control @error('phone') is-invalid @enderror"
                               id="phone"
                               name="phone"
                               value="{{ old('phone') }}"
                               placeholder="أدخل رقم هاتفك"
                               required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
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
                                    onclick="togglePassword('password', 'toggleIcon1')"
                                    style="border: none; background: none; z-index: 5;">
                                <i class="fas fa-eye text-muted" id="toggleIcon1"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-bold">
                            <i class="fas fa-lock me-2"></i>
                            تأكيد كلمة المرور
                        </label>
                        <div class="position-relative">
                            <input type="password"
                                   class="form-control @error('password_confirmation') is-invalid @enderror"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   placeholder="أعد إدخال كلمة المرور"
                                   required>
                            <button type="button" class="btn position-absolute start-0 top-50 translate-middle-y"
                                    onclick="togglePassword('password_confirmation', 'toggleIcon2')"
                                    style="border: none; background: none; z-index: 5;">
                                <i class="fas fa-eye text-muted" id="toggleIcon2"></i>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="tax_number" class="form-label fw-bold">
                    <i class="fas fa-hashtag me-2"></i>
                    الرقم الضريبي (اختياري)
                </label>
                <input type="text"
                       class="form-control @error('tax_number') is-invalid @enderror"
                       id="tax_number"
                       name="tax_number"
                       value="{{ old('tax_number') }}"
                       placeholder="أدخل الرقم الضريبي لشركتك">
                @error('tax_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    أوافق على
                    <a href="#" class="link-custom">شروط الاستخدام</a>
                    و
                    <a href="#" class="link-custom">سياسة الخصوصية</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary-custom mb-3">
                <i class="fas fa-user-plus me-2"></i>
                إنشاء الحساب
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center">
            <p class="mb-2">هل لديك حساب بالفعل؟</p>
            <a href="{{ route('login') }}" class="btn btn-outline-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>
                تسجيل الدخول
            </a>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId, iconId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(iconId);

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

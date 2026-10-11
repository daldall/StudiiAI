@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card toolkit-card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <div class="section-kicker">Configuration</div>
                <h1 class="h3 fw-semibold mb-2">Pengaturan API Key Gemini</h1>
                <p class="text-muted mb-4">Masukkan API key pribadi jika Anda ingin memakai akun Gemini sendiri. Nilai ini disimpan hanya untuk sesi browser yang aktif.</p>

                <div class="alert alert-light border mb-4">
                    <div class="fw-semibold mb-1">Sumber aktif saat ini</div>
                    <div class="text-muted small">{{ $apiKeySource === 'session' ? 'Key tersimpan di sesi browser ini.' : ($apiKeySource === 'env' ? 'Key diambil dari file .env.' : 'Belum ada key yang disiapkan.') }}</div>
                </div>

                <form method="POST" action="{{ route('settings.update') }}" class="mb-3">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="gemini_api_key">Gemini API Key</label>
                        <input type="password" class="form-control clean-input" id="gemini_api_key" name="gemini_api_key" value="{{ $currentKey }}" placeholder="Masukkan API key Anda">
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary-soft">Simpan API Key</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('settings.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-secondary">Hapus key dari sesi</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
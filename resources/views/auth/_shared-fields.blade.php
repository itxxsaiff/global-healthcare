{{-- Fields shared by both registration forms. --}}
<div class="mb-3">
    <label for="name" class="form-label">Full name</label>
    <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autocomplete="name" placeholder="{{ $namePlaceholder ?? 'e.g. Ayesha Khan' }}">
    <x-field-error name="name" />
</div>
<div class="mb-3">
    <label for="email" class="form-label">Email address</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="email">
    <div class="form-hint">We'll send booking confirmations and payment alerts here.</div>
    <x-field-error name="email" />
</div>
<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
        <x-field-error name="password" />
        <div class="form-hint" data-password-hint>At least 8 characters, with letters and numbers.</div>
    </div>
    <div class="col-sm-6">
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password" data-confirms="password">
        <div class="form-hint" data-confirm-hint hidden></div>
    </div>
</div>
<div class="mb-3">
    <span class="form-label d-block">Gender</span>
    <div class="segmented" role="radiogroup">
        @foreach ($genders as $g)
            <input type="radio" name="gender" id="gender-{{ $g->value }}" value="{{ $g->value }}" @checked(old('gender') === $g->value) required>
            <label for="gender-{{ $g->value }}">{{ $g->label() }}</label>
        @endforeach
    </div>
    <x-field-error name="gender" />
</div>
<div class="mb-3">
    <label for="country_id" class="form-label">{{ $countryLabel ?? 'Country you live in' }}</label>
    <select id="country_id" name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
        <option value="">Choose…</option>
        @foreach ($countries as $c)
            <option value="{{ $c->id }}" @selected(old('country_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    @isset($countryHint)<div class="form-hint">{{ $countryHint }}</div>@endisset
    <x-field-error name="country_id" />
</div>
<input type="hidden" name="timezone" value="{{ old('timezone') }}" data-detect-timezone>

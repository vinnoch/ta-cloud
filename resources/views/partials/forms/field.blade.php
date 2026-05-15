<label class="form-field">
    <span>{{ $label }}</span>
    @if (($type ?? 'text') === 'textarea')
        <textarea rows="{{ $rows ?? 5 }}" placeholder="{{ $placeholder ?? '' }}">{{ $value ?? '' }}</textarea>
    @else
        <input type="{{ $type ?? 'text' }}" value="{{ $value ?? '' }}" placeholder="{{ $placeholder ?? '' }}" />
    @endif
</label>

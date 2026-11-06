<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-secondary disabled:opacity-25']) }}>
    {{ $slot }}
</button>

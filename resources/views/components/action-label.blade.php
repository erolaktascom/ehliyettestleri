@props(['icon' => 'arrow', 'iconRight' => false])
<span class="site-action-label">
    @unless($iconRight)<x-student-icon :name="$icon" />@endunless
    <span>{{ $slot }}</span>
    @if($iconRight)<x-student-icon :name="$icon" />@endif
</span>

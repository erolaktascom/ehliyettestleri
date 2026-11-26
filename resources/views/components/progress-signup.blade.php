<a href="{{ route('register') }}" {{ $attributes->class(['site-button-primary min-h-11 justify-center']) }}
   @if($slot->isEmpty()) aria-label="İlerlemeni kaydetmek için üye ol" @endif>
    <x-action-label icon="arrow" :icon-right="true">{{ $slot->isEmpty() ? 'Üye ol' : $slot }}</x-action-label>
</a>

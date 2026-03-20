@props([
    'heading' => '',
    'subheading' => '',
    'srTitle' => '',
])

<section class="container mx-auto px-2 sm:px-4 lg:px-0">
    @if (filled($srTitle))
        <flux:heading class="sr-only">{{ $srTitle }}</flux:heading>
    @endif

    @if (filled($heading) || filled($subheading))
        <div class="hidden sm:mb-6 sm:block">
            <x-heading :heading="$heading" :subheading="$subheading" />
        </div>
    @endif

    <div class="grid gap-3 sm:gap-4 lg:gap-6 xl:grid-cols-12 xl:items-start">
        <div class="@isset($aside) xl:col-span-8 @else xl:col-span-12 @endisset">
            {{ $slot }}
        </div>

        @isset($aside)
            <aside class="xl:col-span-4 xl:sticky xl:top-6">
                {{ $aside }}
            </aside>
        @endisset
    </div>

    @isset($after)
        {{ $after }}
    @endisset
</section>

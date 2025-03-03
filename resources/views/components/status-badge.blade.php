@props([
    'condition' => '',
    'successBg' => 'green',
    'errorBg' => 'red'
])

<span
    {{ $attributes->merge([
        'class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
    ]) }}
    :class="{
        'bg-{{ $errorBg }}-100 text-{{ $errorBg }}-800 dark:bg-{{ $errorBg }}-800/30 dark:text-{{ $errorBg }}-400': {{ $condition }},
        'bg-{{ $successBg }}-100 text-{{ $successBg }}-800 dark:bg-{{ $successBg }}-800/30 dark:text-{{ $successBg }}-400': !({{ $condition }})
    }"
>
    {{ $slot }}
</span>

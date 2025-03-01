<div {{ $attributes->merge(['class' => 'flex items-center space-x-3 p-2 rounded-lg bg-gray-50 dark:bg-gray-700 mb-3']) }}>
    <div class="flex-shrink-0">
        <span class="inline-block h-8 w-8 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-600">
            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/>
        </span>
    </div>
    <div>
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $slot }}</div>
    </div>
</div>

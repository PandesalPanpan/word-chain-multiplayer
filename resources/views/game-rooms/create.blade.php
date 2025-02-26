<x-app-layout>
    <x-header-slot>
        Create a Room
    </x-header-slot>

    <x-content-card>
        <div class="flex justify-between mb-6">
            <x-content-card-name>
                Room Setup

            </x-content-card-name>
            <a href="{{ route('game-rooms.index') }}">
                <x-button color="red">
                    Back
                </x-button>
            </a>
        </div>
        <form action="{{ route('game-rooms.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <x-form-label for="name">
                        Room Name
                    </x-form-label>
                    <x-form-input id="name" name="name" type="text" class="w-full" required autofocus />
                    <x-form-error name="name"/>
                </div>
{{--                <div>--}}
{{--                    <x-label for="max_players">--}}
{{--                        Max Players--}}
{{--                    </x-label>--}}
{{--                    <x-input id="max_players" name="max_players" type="number" class="w-full" required />--}}
{{--                </div>--}}
                <div>
                    <x-button type="submit" class="mt-6">
                        Create Room
                    </x-button>
                </div>
            </div>
        </form>
    </x-content-card>
</x-app-layout>

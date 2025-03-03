<x-app-layout>
    <x-header-slot> Game Rooms </x-header-slot>

    <x-content-card>
        <div class="flex justify-between mb-6">
            <x-content-card-name> Join a Room to Play </x-content-card-name>
            <a href="{{ route('game-rooms.create') }}">
                <x-button> Create Room </x-button>
            </a>
        </div>

        <div
            class="grid gap-6 md:grid-cols-3"
            x-data="{
                                gameRooms: [],

                            }"
            x-init="() => {
                                fetch('/api/game-rooms')
                                    .then(response => response.json())
                                    .then(data => gameRooms = data)

                                }"
        >
            <template x-if="gameRooms.length > 0">
                <template x-for="room in gameRooms" :key="room.id">
                    <div
                        class="overflow-hidden transition-shadow bg-white border rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 hover:shadow-md"
                    >
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                    x-text="room.name"
                                ></h3>
                                <div class="flex items-center space-x-2">
                                    <x-status-badge condition="room.users_count >= 2">
                                        <span x-text="room.users_count >= 2 ? 'Full' : 'Available'"></span>
                                    </x-status-badge>

                                    <x-status-badge condition="room.in_progress" error-bg="yellow">
                                        <span x-text="room.in_progress ? 'In Progress' : 'Open'"></span>
                                    </x-status-badge>
                                </div>
                            </div>
                            <p
                                class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                            >
                                Players: <span x-text="room.users_count"></span>/2
                            </p>
                            <div class="mt-4">
                                <a
                                    :href="`/game-rooms/${room.id}`"
                                    class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium transition-colors border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                    :class="{
                                        'bg-indigo-600 text-white hover:bg-indigo-500': room.users_count < 2,
                                        'bg-gray-300 text-gray-500 cursor-not-allowed': room.users_count >= 2
                                    }"
                                    :disabled="room.users_count >= 2"
                                >
                                    <span x-text="room.users_count >= 2 ? 'Room Full' : 'Join Room'"></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
            <template x-if="!gameRooms.length">
                <div
                    class="col-span-3 p-4 text-center rounded-lg bg-gray-50 dark:bg-gray-700"
                >
                    <p class="text-gray-500 dark:text-gray-400">
                        No active game rooms available.
                    </p>
                    <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">
                        Create a new room to start playing!
                    </p>
                </div>
            </template>
        </div>
    </x-content-card>
</x-app-layout>

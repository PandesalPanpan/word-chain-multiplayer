<x-app-layout>
    <x-header-slot>
        Game Rooms
    </x-header-slot>

    <x-content-card>
        <div class="flex justify-between mb-6">
            <x-content-card-name>
                Join a Room to Play
            </x-content-card-name>
            <a href="{{ route('game-rooms.create') }}">
                <x-button>
                    Create Room
                </x-button>
            </a>
        </div>

        <div class="grid gap-6 md:grid-cols-3"
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
                        class="overflow-hidden transition-shadow bg-white border rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 hover:shadow-md">
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                    x-text="room.name"></h3>
                                <span
                                    :class="{
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400': room.in_progress,
                            'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400': !room.in_progress
                        }"
                                    x-text="room.in_progress ? 'In Progress' : 'Open'"
                                ></span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Players: <span x-text="room.users_count"></span>
                            </p>
                            <div class="mt-4">
                                <a :href="`/game-rooms/${room.id}`"
                                   class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white transition-colors bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                    Join Room
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
            <template x-if="!gameRooms.length">
                <div class="col-span-3 p-4 text-center rounded-lg bg-gray-50 dark:bg-gray-700">
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

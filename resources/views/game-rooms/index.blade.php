<x-app-layout>
    <x-header-slot>
        Game Rooms
    </x-header-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Available Rooms
                        </h3>
                        <button class="px-4 py-2 text-sm font-medium text-white transition-colors bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Create Room
                        </button>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        @forelse ($gameRooms as $room)
                            <div class="overflow-hidden transition-shadow bg-white border rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 hover:shadow-md">
                                <div class="p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            {{ $room->name }}
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                                            Active
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        Words played: {{ $room->word_moves_count }}
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('game-rooms.show', $room) }}"
                                           class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white transition-colors bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                            Join Room
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 p-4 text-center rounded-lg bg-gray-50 dark:bg-gray-700">
                                <p class="text-gray-500 dark:text-gray-400">
                                    No active game rooms available.
                                </p>
                                <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">
                                    Create a new room to start playing!
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

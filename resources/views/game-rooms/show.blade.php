<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Game Room: {{ $gameRoom->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-4 lg:gap-8"
            x-data="{
                gameRoom: [{{ $gameRoom->toJson() }}],
                usersHere: [],
                userTyping: [],
                typingStates: {},
                userInputs: {},

                updateTypingState(userId) {
                    this.typingStates[userId] = true;
                    setTimeout(() => {
                        this.typingStates[userId] = false;
                    }, 1000);
                },

                init() {
                    // Store channel reference
                    const roomChannel = `game-rooms.${this.gameRoom[0].id}`;

                    // Initialize presence channel and store reference
                    const presenceChannel = Echo.join(roomChannel)
                        .here((users) => {
                            this.usersHere = users;
                        })
                        .joining((user) => {
                            this.usersHere.push(user);
                        })
                        .leaving((user) => {
                            this.usersHere = this.usersHere.filter(u => u.id !== user.id);
                            delete this.userInputs[user.id];
                            delete this.typingStates[user.id];
                        })
                        .listenForWhisper('typing', (event) => {
                            if (event.user.id !== {{ auth()->id() }}) {
                                this.userInputs[event.user.id] = event.text;
                                this.updateTypingState(event.user.id);
                            }
                        })
                        .listen('GameRoomClosedEvent', (event) => {
                            console.log('Room closed by host', event);
                            usersHere = [];
                            Echo.leaveChannel(roomChannel);
                            window.location.href = '{{ route("game-rooms.index") }}';
                        })
                        .listen('DisconnectEvent', (event) => {
                            console.log('User disconnected', event);
                            this.usersHere = this.usersHere.filter(u => u.id !== event.user.id);
                            delete this.userInputs[event.user.id];
                            delete this.typingStates[event.user.id];
                        });
                        ;
                    // Add beforeunload handler
                    const handleBeforeUnload = () => {
                        axios.post(`/game-rooms/${this.gameRoom[0].id}/leave`, {
                            user_id: {{ auth()->id() }}
                        }).catch(error => console.error('Error leaving room:', error));
                    };

                    window.addEventListener('beforeunload', handleBeforeUnload);

                }
            }"
            >
                <!-- Players Sidebar -->
                <div class="lg:col-span-1">
                    <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Players
                                </h3>
                                <button
                                    @click="window.location.href = '{{ route('game-rooms.index') }}'"
                                    class="px-3 py-1 text-sm text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                                    Leave Room
                                </button>
                            </div>
                            <div class="mt-4 space-y-3" id="players-list">
                                <!-- Players will be listed here -->
                                <div x-show="usersHere.length === 0">
                                    <p class="text-gray-500 dark:text-gray-400">
                                        Connecting.
                                    </p>
                                </div>
                                <div x-show="usersHere.length > 0">
                                    <template x-for="user in usersHere" :key="user.id">
                                        <x-user-online-card>
                                            <span x-text="user.name"></span>
                                        </x-user-online-card>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Game Area -->
                <div class="mt-5 lg:col-span-3 lg:mt-0">
                    <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="space-y-4">
                                <div x-data="{ words_used: [] }" x-show="usersHere.length > 0">
                                    <template x-for="user in usersHere" :key="user.id">
                                        <div class="space-y-2">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                      x-text="`${user.name}'s input:`">
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400"
                                                      x-show="typingStates[user.id]"
                                                      x-text="'typing...'">
                                                </span>
                                            </div>
                                            <input type="text"
                                                class="w-full px-3 py-2 text-gray-700 bg-gray-100 border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                :disabled="user.id !== {{ auth()->id() }}"
                                                :placeholder="user.id === {{ auth()->id() }} ? 'Type your word...' : 'Waiting for input...'"
                                                :autofocus="user.id === {{ auth()->id() }}"
                                                x-model="userInputs[user.id]"
                                                @keyup="channel.whisper('typing', {
                                                    user: {{ Js::from(auth()->user()->only('id', 'name')) }},
                                                    key: $event.key,
                                                    text: $event.target.value,
                                                    timestamp: Date.now()
                                                })"
                                            >
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

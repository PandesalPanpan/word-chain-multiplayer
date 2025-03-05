<x-app-layout>
    <x-game-notification />
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
                presenceChannel: null,
                currentPlayerId: {{ $gameRoom->current_player_id ?? 'null' }},
                lastWord: '{{ $gameRoom->last_word ?? '' }}',
                words_used: [],

                updateTypingState(userId) {
                    this.typingStates[userId] = true;
                    setTimeout(() => {
                        this.typingStates[userId] = false;
                    }, 1000);
                },

                startGame() {
                    if (this.usersHere.length >= 2) {
                        axios.post(`/game-rooms/${this.gameRoom[0].id}/start`)
                            .then(response => {
                                console.log('Game started successfully');
                            })
                            .catch(error => {
                                console.error('Error starting game:', error);
                            });
                    }
                },

                isMyTurn() {
                    return this.currentPlayerId === {{ auth()->id() }};
                },

                submitWord() {
                    if (!this.isMyTurn()) return;

                    const myInput = this.userInputs[{{ auth()->id() }}];
                    if (!myInput) return;

                    axios.post(`/game-rooms/${this.gameRoom[0].id}/submit-word`, {
                        word: myInput
                    })
                    .then(response => {
                        this.userInputs[{{ auth()->id() }}] = '';
                    })
                    .catch(error => {
                        alert(error.response.data.message);
                    });
                },

                init() {
                    // Store channel reference
                    const roomChannel = `game-rooms.${this.gameRoom[0].id}`;

                    // Initialize presence channel and store reference
                    this.presenceChannel = Echo.join(roomChannel)
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
                        })
                        .listen('GameRoomStartEvent', (event) => {
                            console.log('Game started', event);
                            this.currentPlayerId = event.firstPlayer.id;
                            console.log('Game started, first player:', event.firstPlayer.name);
                        })
                        .listen('GameRoomTurnValidatedEvent', (event) => {
                            if (event.isValid) {
                                // Update current player
                                this.currentPlayerId = event.nextPlayer.id;

                                // Clear input of the player who just played
                                this.userInputs[event.user.id] = '';

                                // Add word to used words list (if you're tracking that)
                                this.words_used.push(event.word);

                                // Show success message
                                this.$dispatch('notify', {
                                    type: 'success',
                                    message: `${event.user.name}: ${event.word} - ${event.message}`
                                });
                            } else {
                                // Show error message only
                                this.$dispatch('notify', {
                                    type: 'error',
                                    message: `${event.user.name}: ${event.word} - ${event.message}`
                                });
                            }
                        });
                    // Add beforeunload handler
{{--                    const handleBeforeUnload = () => {--}}
{{--                        axios.post(`/game-rooms/${this.gameRoom[0].id}/leave`, {--}}
{{--                            user_id: {{ auth()->id() }}--}}
{{--                        }).catch(error => console.error('Error leaving room:', error));--}}
{{--                    };--}}

{{--                    window.addEventListener('beforeunload', handleBeforeUnload);--}}

                }
            }"
            >
                <!-- Players Sidebar -->
                <div class="lg:col-span-1">
                    <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="mb-4 text-center">
                                <span x-show="usersHere.length === 0"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                    Connecting...
                                </span>
                                <span x-show="usersHere.length === 1"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                    Waiting for Players
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Players
                                </h3>
                                <x-button
                                    @click="window.location.href = '{{ route('game-rooms.index') }}'"
                                    color="red"
                                >
                                    Leave Room
                                </x-button>
                            </div>
                            <div class="mt-4 mb-4">
                                <x-button
                                    x-show="{{ auth()->id() }} === {{ $gameRoom->host_id }}"
                                    @click="startGame()"
                                    x-bind:disabled="usersHere.length < 2"
                                    x-bind:class="{
                                        'opacity-50 cursor-not-allowed': usersHere.length < 2
                                    }"
                                    class="w-full"
                                >
                                    <span x-text="usersHere.length < 2 ? 'Need More Players' : 'Start Game'"></span>
                                </x-button>
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
                                                   class="w-full px-3 py-2 border-gray-300 rounded-md transition-all duration-200 ease-in-out"
                                                   :class="{
                                                   'bg-emerald-100 dark:bg-emerald-900 border-emerald-500 dark:border-emerald-500 font-semibold text-emerald-900 dark:text-emerald-100 ring-2 ring-emerald-500': isMyTurn() && user.id === {{ auth()->id() }},
                                                   'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700 text-blue-900 dark:text-blue-100': user.id === {{ auth()->id() }} && !isMyTurn(),
                                                   'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400': user.id !== {{ auth()->id() }}
                                               }"
                                                   :disabled="user.id !== {{ auth()->id() }}"
                                                   :placeholder="isMyTurn() && user.id === {{ auth()->id() }} ? 'Your turn - Enter a word...' : 'Waiting for other player...'"
                                                   x-model="userInputs[user.id]"
                                                   @keydown.enter.prevent="isMyTurn() && user.id === {{ auth()->id() }} ? submitWord() : null"
                                                   @keyup="presenceChannel.whisper('typing', {
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

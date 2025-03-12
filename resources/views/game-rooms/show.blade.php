<x-app-layout>
    <x-game-notification/>
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
                lastWord: '{{ $gameRoom->last_word ?? '' }}', // TODO - Add last word to game room model
                words_used: [],
                turnDeadline: '{{ $gameRoom->turn_deadline ?? '' }}',
                timeRemaining: 10,
                countdownInterval: null,

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

                startCountdown() {
                    console.log('Starting countdown');
                    if (this.countdownInterval) {
                        clearInterval(this.countdownInterval);
                    }

                    if (!this.turnDeadline) {
                        this.timeRemaining = 10;
                        return;
                    }

                    // Ensure we have a valid Date object
                    const deadlineDate = new Date(this.turnDeadline);
                    
                    // Log for debugging
                    console.log('Deadline String:', this.turnDeadline);
                    console.log('Deadline Date Object:', deadlineDate);
                    console.log('Deadline Timestamp:', deadlineDate.getTime());
                    
                    // Check if date is valid before proceeding
                    if (isNaN(deadlineDate.getTime())) {
                        console.error('Invalid date format:', this.turnDeadline);
                        this.timeRemaining = 10; // Fallback to default
                        return;
                    }

                    const deadline = deadlineDate.getTime();

                    // Update time remaining immediately
                    this.updateTimeRemaining(deadline);

                    // Then update every second
                    this.countdownInterval = setInterval(() => {
                        if (!this.updateTimeRemaining(deadline)) {
                            clearInterval(this.countdownInterval);
                        }
                    }, 1000);
                },

                updateTimeRemaining(deadline) {
                    const now = new Date().getTime();
                    const diff = deadline - now;
                    
                    // Debug information
                    console.log('Now:', now, new Date(now).toISOString());
                    console.log('Deadline:', deadline, new Date(deadline).toISOString());
                    console.log('Time difference (ms):', diff);

                    if (diff <= 0) {
                        this.timeRemaining = 0;
                        return false;
                    }

                    this.timeRemaining = Math.ceil(diff / 1000);
                    return true;
                },

                getTimeRemainingColor() {
                    if (this.timeRemaining > 10) return 'text-green-600 dark:text-green-400';
                    if (this.timeRemaining > 5) return 'text-yellow-600 dark:text-yellow-400';
                    return 'text-red-600 dark:text-red-400 animate-pulse font-bold';
                },

                getCurrentPlayerName() {
                    if (!this.currentPlayerId) return 'Waiting...';
                    const player = this.usersHere.find(u => u.id === this.currentPlayerId);
                    return player ? player.name : 'Unknown';
                },

                isInvalidInput(word) {
                    // console.log('Validating word:', word);
                    // console.log('Last word is:', this.lastWord);
                    // console.log('Words used:', this.words_used);
                    if (!word || word.trim() === '') return false;

                    // Check if the word is least 3 characters long
                    if (word.trim().length < 4) {
                        return true;
                    }

                    if (!/^[a-zA-Z]+$/.test(word)) {
                        return true;
                    }

                    // Must start with the last letter of previous word (if there is one)
                    if (this.lastWord && this.lastWord.length > 0) {
                        const lastLetter = this.lastWord.slice(-1).toLowerCase();
                        const firstLetter = word.trim().slice(0, 1).toLowerCase();
                        if (firstLetter !== lastLetter) {
                            return true;
                        }
                    }

                    // Cannot be a previously used word
                    if (this.words_used.map(w => w.toLowerCase()).includes(word.trim().toLowerCase())) {
                        return true;
                    }

                    return false;
                },

                getInputErrorMessage(word) {
                    // console.log('Error Validating input:', word);
                    if (!word || word.trim() === '') return '';

                    if (!/^[a-zA-Z]+$/.test(word)) {
                        return 'Word must contain only letters (a-z, A-Z)';
                    }

                    // Check first letter
                    if (this.lastWord && this.lastWord.length > 0) {
                        const lastLetter = this.lastWord.slice(-1).toLowerCase();
                        const firstLetter = word.trim().slice(0, 1).toLowerCase();
                        if (firstLetter !== lastLetter) {
                            return `Word must start with '${lastLetter.toUpperCase()}'`;
                        }
                    }

                    // Check if word was used
                    if (this.words_used.map(w => w.toLowerCase()).includes(word.trim().toLowerCase())) {
                        return 'This word has already been used';
                    }

                    return '';
                },

                validateAndSubmit() {
                    if (!this.isMyTurn()) return;
                    if (this.timeRemaining <= 0) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: 'Time ran out! Your turn has ended.'
                        });
                        return;
                    }

                    const myInput = this.userInputs[{{ auth()->id() }}];
                    if (!myInput) return;

                    // Isolated validation for length
                    if (myInput.trim().length < 4) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: 'Word must be at least 4 characters long'
                        });
                        return;
                    }

                    // Don't submit if validation fails
                    if (this.isInvalidInput(myInput)) {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: this.getInputErrorMessage(myInput)
                        });
                        return;
                    }

                    // Proceed with submission
                    axios.post(`/game-rooms/${this.gameRoom[0].id}/submit-word`, {
                        word: myInput
                    })
                    .then(response => {
                        this.userInputs[{{ auth()->id() }}] = '';
                    })
                    .catch(error => {
                        this.$dispatch('notify', {
                            type: 'error',
                            message: error.response?.data?.message || 'An error occurred'
                        });
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
                            this.gameRoom[0] = event.gameRoom;
                            this.words_used = [];
                            this.lastWord = '';
                            this.currentPlayerId = event.firstPlayer.id;
                            this.turnDeadline = event.gameRoom.turn_deadline;
                            this.startCountdown();
                            console.log('Game started, first player:', event.firstPlayer.name);
                        })
                        .listen('GameRoomTurnValidatedEvent', (event) => {
                            if (event.isValid) {
                                // Update current player
                                this.currentPlayerId = event.nextPlayer.id;

                                // Update turn deadline and start countdown
                                this.turnDeadline = event.gameRoom.turn_deadline;
                                this.startCountdown();

                                // Clear input of the player who just played
                                this.userInputs[event.user.id] = '';

                                // Add word to used words list (if you're tracking that)
                                this.words_used.push(event.word);

                                // Update lastWord property
                                this.lastWord = event.word;

                                // Show success message
                                this.$dispatch('notify', {
                                    type: 'success',
                                    message: `${event.user.name}: ${event.word} - ${event.message}`
                                });
                            } else {
                                this.$dispatch('notify', {
                                    type: 'error',
                                    message: `${event.user.name}: ${event.word} - ${event.message}`
                                });
                            }
                        })
                        .listen('GameRoomTimeoutEvent', (event) => {
                            console.log('Game timed out', event);
                            // update gameRoom
                            this.gameRoom[0] = event.gameRoom;
                            this.timeRemaining = 0;
                            clearInterval(this.countdownInterval);

                            this.currentPlayerId = null;



                            // Declare winner
                            this.$dispatch('notify', {
                                type: 'success',
                                message: `${event.winner.name} wins the game!`
                            });

                        });

                        if (this.gameRoom[0].word_moves && this.gameRoom[0].word_moves.length > 0) {
                            const usedWords = this.gameRoom[0].word_moves.map(move => move.word);

                            // Also populate the words_used array with these words
                            this.words_used = usedWords;

                            // Set the last word to the most recent one
                            this.lastWord = usedWords[usedWords.length - 1];

                        }

                    // Start the countdown if game is already in progress
                    if (this.gameRoom[0].in_progress && this.gameRoom[0].turn_deadline) {
                        this.turnDeadline = this.gameRoom[0].turn_deadline;
                        this.startCountdown();
                    }
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
                                <span x-show="usersHere.length >= 2 && !gameRoom[0].in_progress"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-100">
                                    Ready to Start
                                </span>
                                <span x-show="usersHere.length >= 2 && gameRoom[0].in_progress"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100">
                                    Game in Progress
                                </span>
                            </div>

                            <!-- Current Player & Countdown Timer (new) -->
                            <div class="mt-6 mb-4" x-show="gameRoom[0].in_progress">
                                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 text-center">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        Current Turn
                                    </h4>
                                    <p class="font-bold text-lg text-indigo-600 dark:text-indigo-400" x-text="getCurrentPlayerName()"></p>

                                    <div class="mt-3 flex justify-center items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-xl font-mono" x-text="timeRemaining" x-bind:class="getTimeRemainingColor()"></span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">seconds</span>
                                    </div>
                                </div>
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
                                    x-bind:disabled="usersHere.length < 2 || gameRoom[0].in_progress"
                                    x-bind:class="{
                                        'opacity-50 cursor-not-allowed': usersHere.length < 2 || gameRoom[0].in_progress
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
                                <div x-show="usersHere.length > 0">
                                    <template x-for="user in usersHere" :key="user.id">
                                        <div class="space-y-2">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium"
                                                      x-bind:class="{
                                                        'text-emerald-600 dark:text-emerald-400 font-bold': user.id === currentPlayerId,
                                                        'text-gray-700 dark:text-gray-300': user.id !== currentPlayerId
                                                      }"
                                                      x-text="`${user.name}'s input:${user.id === currentPlayerId ? ' (Current Turn)' : ''}`">
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
                                                       'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400': user.id !== {{ auth()->id() }},
                                                       'border-red-500 dark:border-red-500 ring-2 ring-red-500': isInvalidInput(userInputs[user.id])
                                                   }"
                                                   :disabled="user.id !== {{ auth()->id() }}"
                                                   :placeholder="isMyTurn() && user.id === {{ auth()->id() }} ? `Start with '${lastWord ? lastWord.slice(-1).toUpperCase() : ''}' - Enter a word...` : 'Waiting for other player...'"
                                                   x-model="userInputs[user.id]"
                                                   @keydown.enter.prevent="isMyTurn() && user.id === {{ auth()->id() }} ? validateAndSubmit() : null"
                                                   @keyup="presenceChannel.whisper('typing', {
                                                       user: {{ Js::from(auth()->user()->only('id', 'name')) }},
                                                       key: $event.key,
                                                       text: $event.target.value,
                                                       timestamp: Date.now()
                                                   })"
                                            >
                                            <div
                                                x-show="isMyTurn() && user.id === {{ auth()->id() }} && isInvalidInput(userInputs[user.id])"
                                                class="mt-1 text-sm text-red-600 dark:text-red-400">
                                                <span x-text="getInputErrorMessage(userInputs[user.id])"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-6 border-t pt-4 dark:border-gray-700">
                                    <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
                                        Words Used
                                    </h3>

                                    <!-- Last Word Highlight -->
                                    <div x-show="lastWord" class="mb-4">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Last Word:</p>
                                        <span
                                            class="inline-flex items-center px-4 py-2 rounded-md text-lg font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 border border-purple-300 dark:border-purple-700">
                                            <!-- Apply the same highlighting style to the last word badge -->
                                            <template x-if="lastWord && lastWord.length > 0">
                                                <span>
                                                    <span x-text="lastWord.slice(0, -1).toUpperCase()"></span>
                                                    <span class="font-bold text-red-600 dark:text-red-400 underline"
                                                          x-text="lastWord.slice(-1).toUpperCase()"></span>
                                                </span>
                                            </template>
                                        </span>
                                    </div>

                                    <!-- Words Used Badges -->
                                    <div class="flex flex-wrap gap-3">
                                        <template x-for="(word, index) in [...words_used].reverse()" :key="index">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium"
                                                  :class="{
                                                      'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': index !== 0,
                                                      'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500': index === 0
                                                  }">
                                                <!-- Regular capitalized display for all words in the list -->
                                                <span x-text="word.toUpperCase()"></span>
                                            </span>
                                        </template>

                                        <span x-show="words_used.length === 0"
                                              class="text-sm text-gray-500 dark:text-gray-400 italic">
                                            No words used yet
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

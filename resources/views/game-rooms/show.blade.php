<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Game Room: {{ $gameRoom->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-4 lg:gap-8">
                <!-- Players Sidebar -->
                <div class="lg:col-span-1">
                    <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Players
                            </h3>
                            <div class="mt-4 space-y-3" id="players-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Game Area -->
                <div class="mt-5 lg:col-span-3 lg:mt-0">
                    <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="space-y-4" id="player-inputs"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function () {
            const playersList = document.getElementById('players-list');
            const playerInputs = document.getElementById('player-inputs');
            let usersHere = [];
            const currentUserId = {{ auth()->id() }};
            let userTyping = [];

            function updatePlayersList() {
                console.log(channel.members);
                playersList.innerHTML = usersHere.map(user => `
                <x-user-online-card>
                    ${user.name}
                </x-user-online-card>
            `).join('');

                playerInputs.innerHTML = usersHere.map(user => `
                <div class="space-y-2">
                    <div class="flex items-center space-x-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">${user.name}'s input:</span>
                        <span id="typing-status-${user.id}" class="text-xs text-gray-500 dark:text-gray-400"></span>
                    </div>
                    <input type="text"
                        id="input-${user.id}"
                        class="w-full px-3 py-2 text-gray-700 bg-gray-100 border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        ${user.id !== currentUserId ? 'disabled' : ''}
                        placeholder="${user.id === currentUserId ? 'Type your word...' : 'Waiting for input...'}"
                    >
                </div>
            `).join('');

                // Add event listeners for the current user's input
                const currentUserInput = document.getElementById(`input-${currentUserId}`);
                if (currentUserInput) {
                    currentUserInput.addEventListener('keyup', (e) => {
                        channel.whisper('typing', {
                            user: {{ Js::from(auth()->user()->only('id', 'name')) }},
                            key: e.key,
                            text: e.target.value,
                            timestamp: Date.now()
                        });
                    });
                }
            }

            const gameRoom = {
                id: {{ $gameRoom->id }}
            };

            const channel = Echo.join(`game-rooms.${gameRoom.id}`)
                .here((users) => {
                    usersHere = users;
                    updatePlayersList();
                })
                .joining((user) => {
                    if (!usersHere.find(u => u.id === user.id)) {
                        usersHere.push(user);
                        updatePlayersList();
                    }
                })
                .leaving((user) => {
                    usersHere = usersHere.filter(u => u.id !== user.id);
                    userTyping = userTyping.filter(u => u.user.id !== user.id);
                    updatePlayersList();

                    // Send request to remove user from game room
                    // axios.post(`/game-rooms/${gameRoom.id}/leave`, {
                    //     user_id: user.id
                    // }).catch(error => console.error('Error updating user:', error));

                    if (Object.keys(channel.members.users).length === 0) {
                        axios.post('/log')
                    }
                })
                .listenForWhisper('typing', (event) => {
                    const inputField = document.getElementById(`input-${event.user.id}`);
                    const typingStatus = document.getElementById(`typing-status-${event.user.id}`);

                    if (event.user.id !== currentUserId) {
                        const user = userTyping.find(u => u.user.id === event.user.id);

                        if (typeof user === 'undefined') {
                            userTyping.push(event);
                        } else {
                            user.text = event.text;
                            user.timestamp = event.timestamp;
                        }

                        if (inputField) {
                            inputField.value = event.text;
                        }

                        if (typingStatus) {
                            typingStatus.textContent = 'typing...';
                            setTimeout(() => {
                                typingStatus.textContent = '';
                            }, 1000);
                        }
                    }
                });
            window.addEventListener('beforeunload', () => {
                console.log('unload is triggered');
                axios.post(`/game-rooms/${gameRoom.id}/leave`, {
                    user_id: currentUserId
                }).catch(error => console.error('Error updating user:', error));
            });
        }
    </script>
</x-app-layout>

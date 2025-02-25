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
                                gameRooms: {{ $gameRooms }}
                          }"
            x-init="
                                const channel = Echo.channel('game-rooms');
                                console.log('Room: ', gameRooms);

                                channel.listen('GameRoomUpdatedEvent', (event) => {
                                    console.log('event received');
                                    console.log(event);
                                    console.log(event.gameRoom);


                                    // Check event message if either created or update
                                    if (event.action === 'created') {
                                        // Insert the new room into the gameRooms array
                                        gameRooms.push(event.gameRoom);
                                    }

                                    if (event.action == 'updated') {
                                        console.log('Updating room');

                                        // Find the index of the room in the gameRooms array
                                        const index = gameRooms.findIndex(room => room.id === event.gameRoom.id);

                                        // Check if the room was not found
                                        if (index === -1) {
                                            // Add the new game room
                                            gameRooms.push(event.gameRoom);
                                            return;
                                        }

                                        // Read if the gameRoom.is_active is false
                                        if (!event.gameRoom.is_active) {
                                            console.log(index);
                                            // Remove the room from the gameRooms array
                                            gameRooms.splice(index, 1);

                                            // Exit the function
                                            return;
                                        }




                                        // Replace the room with the updated room
                                        console.log('Replacing: ', gameRooms[index]);
                                        console.log('With: ', event.gameRoom);
                                        gameRooms[index] = event.gameRoom;
                                    }
                                    // Loop through the rooms and console log their names
                                    gameRooms.forEach(room => {
                                        console.log(room.name);
                                        console.log(room.users_count);
                                    });
                                });


                            "
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

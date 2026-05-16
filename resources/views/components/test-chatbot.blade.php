<!-- Simple Test Chatbot -->
<div x-data="{ isOpen: false }" class="fixed bottom-4 right-4 z-50">
    <!-- Chat Toggle Button -->
    <button 
        @click="isOpen = !isOpen"
        class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg transition-all duration-300"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
    </button>

    <!-- Chat Window -->
    <div x-show="isOpen" 
         x-transition
         class="absolute bottom-16 right-0 w-80 h-96 bg-white rounded-lg shadow-2xl border border-gray-200 p-4">
        <h3 class="font-semibold text-gray-900 mb-2">PageTurner AI</h3>
        <p class="text-sm text-gray-600">Test chatbot - if you see this, Alpine.js is working!</p>
        <button @click="isOpen = false" class="mt-4 text-sm bg-blue-600 text-white px-3 py-1 rounded">
            Close
        </button>
    </div>
</div>

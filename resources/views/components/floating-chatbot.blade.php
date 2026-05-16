@php
    $categories = \App\Models\Category::all();
@endphp

<!-- Floating Chatbot -->
<div x-data="chatbot()" x-init="init()" class="fixed bottom-4 right-4 z-50">
    <!-- Chat Toggle Button -->
    <button 
        @click="toggleChat()"
        class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg transition-all duration-300 transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        :class="{ 'rotate-180': isOpen }"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
        
        <!-- Notification Badge -->
        <span x-show="!isOpen && unreadCount > 0" 
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
              x-text="unreadCount">
        </span>
    </button>

    <!-- Chat Window -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90 translate-y-4"
         x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 transform scale-90 translate-y-4"
         class="absolute bottom-16 right-0 w-96 h-[600px] bg-white rounded-lg shadow-2xl border border-gray-200 flex flex-col overflow-hidden">
        
        <!-- Chat Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">PageTurner AI</h3>
                    <p class="text-xs text-blue-100">Your book recommendation assistant</p>
                </div>
            </div>
            <button @click="toggleChat()" class="text-white hover:text-blue-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Chat Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50" x-ref="messagesContainer">
            <!-- Welcome Message -->
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div class="bg-white rounded-lg p-3 shadow-sm max-w-[80%]">
                    <p class="text-sm text-gray-800">
                        Hello! I'm PageTurner AI, your personal book recommendation assistant. I can help you discover the perfect books based on your interests. What kind of books are you looking for today?
                    </p>
                </div>
            </div>

            <!-- Messages will be dynamically added here -->
            <template x-for="message in messages" :key="message.id">
                <div class="flex items-start space-x-3" :class="message.sender === 'user' ? 'flex-row-reverse space-x-reverse' : ''">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                         :class="message.sender === 'user' ? 'bg-green-100' : 'bg-blue-100'">
                        <svg x-show="message.sender === 'user'" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <svg x-show="message.sender === 'bot'" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div class="rounded-lg p-3 shadow-sm max-w-[80%]"
                         :class="message.sender === 'user' ? 'bg-blue-600 text-white' : 'bg-white'">
                        <p class="text-sm whitespace-pre-line" x-text="message.content"></p>
                        
                        <!-- Recommendations Display -->
                        <div x-show="message.recommendations && message.recommendations.length > 0" class="mt-3 space-y-2">
                            <template x-for="rec in message.recommendations" :key="rec.title">
                                <div class="border border-gray-200 rounded p-2 bg-gray-50">
                                    <h4 class="font-semibold text-sm text-gray-900" x-text="rec.title"></h4>
                                    <p class="text-xs text-gray-600 mt-1" x-text="rec.reason"></p>
                                    <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                                        <span x-text="rec.category"></span>
                                        <span x-text="rec.price_range"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Typing Indicator -->
            <div x-show="isTyping" class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div class="bg-white rounded-lg p-3 shadow-sm">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div x-show="messages.length === 1" class="border-t border-gray-200 p-3 bg-white">
            <p class="text-xs text-gray-600 mb-2">Quick suggestions:</p>
            <div class="flex flex-wrap gap-2">
                <button @click="sendQuickMessage('Show me popular fiction books')" 
                        class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full transition-colors">
                    Popular Fiction
                </button>
                <button @click="sendQuickMessage('Recommend programming books for beginners')" 
                        class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full transition-colors">
                    Programming Books
                </button>
                <button @click="sendQuickMessage('What are the best mystery novels?')" 
                        class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full transition-colors">
                    Mystery Novels
                </button>
                <button @click="sendQuickMessage('Books about self-improvement')" 
                        class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full transition-colors">
                    Self-Help
                </button>
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 p-4 bg-white">
            <form @submit.prevent="sendMessage()" class="flex items-center space-x-2">
                <input 
                    x-model="inputMessage"
                    x-ref="messageInput"
                    type="text" 
                    placeholder="Ask me about books..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    :disabled="isTyping"
                >
                <button 
                    type="submit"
                    :disabled="!inputMessage.trim() || isTyping"
                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white rounded-full p-2 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function chatbot() {
    return {
        isOpen: false,
        inputMessage: '',
        isTyping: false,
        unreadCount: 0,
        messages: [
            {
                id: 1,
                sender: 'bot',
                content: 'Hello! I\'m PageTurner AI, your personal book recommendation assistant. I can help you discover the perfect books based on your interests. What kind of books are you looking for today?',
                timestamp: new Date()
            }
        ],
        messageIdCounter: 2,

        init() {
            // Load chat history from localStorage if exists
            const savedMessages = localStorage.getItem('pageturner_chat_history');
            if (savedMessages) {
                this.messages = JSON.parse(savedMessages);
                this.messageIdCounter = Math.max(...this.messages.map(m => m.id)) + 1;
            }
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.unreadCount = 0;
                this.$nextTick(() => {
                    this.$refs.messageInput?.focus();
                    this.scrollToBottom();
                });
            }
        },

        async sendMessage() {
            if (!this.inputMessage.trim() || this.isTyping) return;

            const userMessage = {
                id: this.messageIdCounter++,
                sender: 'user',
                content: this.inputMessage.trim(),
                timestamp: new Date()
            };

            this.messages.push(userMessage);
            this.inputMessage = '';
            this.isTyping = true;
            this.scrollToBottom();
            this.saveChatHistory();

            try {
                // Get AI response
                const response = await fetch('/api/v1/ai/recommendations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        query: userMessage.content
                    })
                });

                const data = await response.json();

                const botMessage = {
                    id: this.messageIdCounter++,
                    sender: 'bot',
                    content: data.message || 'Here are some book recommendations for you:',
                    recommendations: data.recommendations || [],
                    timestamp: new Date()
                };

                this.messages.push(botMessage);

            } catch (error) {
                console.error('Chat error:', error);
                const errorMessage = {
                    id: this.messageIdCounter++,
                    sender: 'bot',
                    content: 'Sorry, I\'m having trouble connecting right now. Please try again later or browse our book categories directly.',
                    timestamp: new Date()
                };
                this.messages.push(errorMessage);
            } finally {
                this.isTyping = false;
                this.scrollToBottom();
                this.saveChatHistory();

                if (!this.isOpen) {
                    this.unreadCount++;
                }
            }
        },

        sendQuickMessage(message) {
            this.inputMessage = message;
            this.sendMessage();
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        saveChatHistory() {
            try {
                localStorage.setItem('pageturner_chat_history', JSON.stringify(this.messages));
            } catch (error) {
                console.error('Error saving chat history:', error);
            }
        }
    }
}
</script>

<style>
/* Custom animations for typing indicator */
@keyframes bounce {
    0%, 80%, 100% {
        transform: scale(0);
    }
    40% {
        transform: scale(1);
    }
}

.animate-bounce {
    animation: bounce 1.4s infinite;
}
</style>

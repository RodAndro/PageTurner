<!-- Simple HTML Chatbot - No Alpine.js -->
<div id="simple-chatbot" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <!-- Chat Toggle Button -->
    <button 
        onclick="toggleSimpleChat()"
        style="background-color: #16a34a; color: white; border-radius: 50%; padding: 16px; border: none; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s;"
        onmouseover="this.style.backgroundColor='#15803d'"
        onmouseout="this.style.backgroundColor='#16a34a'"
    >
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
    </button>

    <!-- Chat Window -->
    <div id="simple-chat-window" style="display: none; position: absolute; bottom: 80px; right: 0; width: 384px; height: 600px; background: white; border-radius: 8px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e5e7eb; flex-direction: column;">
        
        <!-- Chat Header -->
        <div style="background: linear-gradient(to right, #16a34a, #15803d); color: white; padding: 16px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div>
                    <h3 style="font-weight: 600; margin: 0;">PageTurner AI</h3>
                    <p style="font-size: 12px; opacity: 0.8; margin: 0;">Your book recommendation assistant</p>
                </div>
            </div>
            <button onclick="toggleSimpleChat()" style="background: none; border: none; color: white; cursor: pointer; padding: 4px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Chat Messages -->
        <div style="flex: 1; padding: 16px; overflow-y: auto; background: #f9fafb;">
            <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;">
                <div style="width: 32px; height: 32px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div style="background: white; border-radius: 8px; padding: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); max-width: 80%;">
                    <p style="font-size: 14px; color: #1f2937; margin: 0;">
                        Hi! I'm PageTurner AI 📚 Tell me a book, genre, movie, or vibe you love, and I'll find your next great read.
                    </p>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div style="border-top: 1px solid #e5e7eb; padding: 16px; background: white;">
            <form onsubmit="sendSimpleMessage(event)" style="display: flex; align-items: center; gap: 8px;">
                <input 
                    id="simple-chat-input"
                    type="text" 
                    placeholder="Ask me about books..."
                    style="flex: 1; padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 9999px; outline: none; font-size: 14px;"
                >
                <button 
                    type="submit"
                    style="background: #16a34a; color: white; border: none; border-radius: 50%; padding: 8px; cursor: pointer; transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#15803d'"
                    onmouseout="this.style.backgroundColor='#16a34a'"
                >
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSimpleChat() {
    const chatWindow = document.getElementById('simple-chat-window');
    if (chatWindow.style.display === 'none' || chatWindow.style.display === '') {
        chatWindow.style.display = 'flex';
        document.getElementById('simple-chat-input').focus();
    } else {
        chatWindow.style.display = 'none';
    }
}

function sendSimpleMessage(event) {
    event.preventDefault();
    const input = document.getElementById('simple-chat-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    const messagesContainer = document.querySelector('#simple-chat-window > div[style*="overflow-y: auto"]');
    const userMessageDiv = document.createElement('div');
    userMessageDiv.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; justify-content: flex-end;';
    userMessageDiv.innerHTML = `
        <div style="background: #16a34a; color: white; border-radius: 8px; padding: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); max-width: 80%;">
            <p style="font-size: 14px; margin: 0;">${message}</p>
        </div>
    `;
    messagesContainer.appendChild(userMessageDiv);
    
    input.value = '';
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Show typing indicator
    const typingDiv = document.createElement('div');
    typingDiv.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;';
    typingDiv.innerHTML = `
        <div style="width: 32px; height: 32px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
        </div>
        <div style="background: white; border-radius: 8px; padding: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="display: flex; gap: 4px;">
                <div style="width: 8px; height: 8px; background: #9ca3af; border-radius: 50%; animation: bounce 1.4s infinite;"></div>
                <div style="width: 8px; height: 8px; background: #9ca3af; border-radius: 50%; animation: bounce 1.4s infinite; animation-delay: 0.15s;"></div>
                <div style="width: 8px; height: 8px; background: #9ca3af; border-radius: 50%; animation: bounce 1.4s infinite; animation-delay: 0.3s;"></div>
            </div>
        </div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Get AI response from API - FIXED ENDPOINT
    fetch('/api/v1/ai/recommendations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
            query: message
        })
    })
    .then(response => response.json())
    .then(data => {
        messagesContainer.removeChild(typingDiv);
        
        const botResponseDiv = document.createElement('div');
        botResponseDiv.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;';
        
        let responseContent = '';
        
        if (data.recommendations && data.recommendations.length > 0) {
            responseContent = `<p style="font-size: 14px; color: #1f2937; margin: 0 0 12px 0;">Here are ${data.recommendations.length} books you might love:</p>`;
            
            data.recommendations.forEach((book, index) => {
                responseContent += `
                    <div style="margin-bottom: 12px; padding: 8px; background: #f9fafb; border-left: 3px solid #16a34a; border-radius: 4px;">
                        <p style="font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">${index + 1}. "${book.title}"</p>
                        <p style="font-size: 13px; color: #6b7280; font-style: italic; margin: 0 0 8px 0;">${book.reason}</p>
                        <p style="font-size: 12px; color: #059669; margin: 0;"><strong>Why you'd like it:</strong> ${book.description}</p>
                    </div>
                `;
            });
        } else {
            responseContent = `<p style="font-size: 14px; color: #1f2937; margin: 0;">${data.message || "I'd love to help you find the perfect book! Could you tell me more about what you're in the mood for? For example, are you looking for something fast-paced and exciting, or something more thoughtful and character-driven?"}</p>`;
        }
        
        botResponseDiv.innerHTML = `
            <div style="width: 32px; height: 32px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
            <div style="background: white; border-radius: 8px; padding: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); max-width: 80%;">
                ${responseContent}
            </div>
        `;
        messagesContainer.appendChild(botResponseDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    })
    .catch(error => {
        messagesContainer.removeChild(typingDiv);
        
        const botResponseDiv = document.createElement('div');
        botResponseDiv.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;';
        botResponseDiv.innerHTML = `
            <div style="width: 32px; height: 32px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="16" height="16" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
            <div style="background: white; border-radius: 8px; padding: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); max-width: 80%;">
                <p style="font-size: 14px; color: #1f2937; margin: 0;">I'm having trouble connecting right now, but I'd still love to help! What genre or mood are you thinking about? I can suggest some great books once I know more about what you're looking for.</p>
            </div>
        `;
        messagesContainer.appendChild(botResponseDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    });
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes bounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
</script>

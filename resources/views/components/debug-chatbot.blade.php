<!-- Debug Chatbot - Simple Test Version -->
<div id="debug-chatbot" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <!-- Chat Toggle Button -->
    <button 
        onclick="toggleDebugChat()"
        style="background-color: #16a34a; color: white; border-radius: 50%; padding: 16px; border: none; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s;"
        onmouseover="this.style.backgroundColor='#15803d'"
        onmouseout="this.style.backgroundColor='#16a34a'"
    >
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
    </button>

    <!-- Chat Window -->
    <div id="debug-chat-window" style="display: none; position: absolute; bottom: 80px; right: 0; width: 384px; height: 400px; background: white; border-radius: 8px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e5e7eb; flex-direction: column;">
        
        <!-- Chat Header -->
        <div style="background: linear-gradient(to right, #16a34a, #15803d); color: white; padding: 16px; border-radius: 8px 8px 0 0;">
            <h3 style="font-weight: 600; margin: 0;">Debug Chatbot</h3>
        </div>

        <!-- Chat Messages -->
        <div id="debug-messages" style="flex: 1; padding: 16px; overflow-y: auto; background: #f9fafb;">
            <div style="background: #e5e7eb; padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                <p style="font-size: 12px; margin: 0;">Debug mode - testing basic functionality</p>
            </div>
        </div>

        <!-- Input Area -->
        <div style="border-top: 1px solid #e5e7eb; padding: 16px; background: white;">
            <form onsubmit="sendDebugMessage(event)" style="display: flex; align-items: center; gap: 8px;">
                <input 
                    id="debug-chat-input"
                    type="text" 
                    placeholder="Test message..."
                    style="flex: 1; padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 9999px; outline: none; font-size: 14px;"
                >
                <button 
                    type="submit"
                    style="background: #16a34a; color: white; border: none; border-radius: 50%; padding: 8px; cursor: pointer;"
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
function toggleDebugChat() {
    const chatWindow = document.getElementById('debug-chat-window');
    if (chatWindow.style.display === 'none' || chatWindow.style.display === '') {
        chatWindow.style.display = 'flex';
        document.getElementById('debug-chat-input').focus();
    } else {
        chatWindow.style.display = 'none';
    }
}

function sendDebugMessage(event) {
    event.preventDefault();
    const input = document.getElementById('debug-chat-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    const messagesContainer = document.getElementById('debug-messages');
    const userMessageDiv = document.createElement('div');
    userMessageDiv.style.cssText = 'display: flex; justify-content: flex-end; margin-bottom: 8px;';
    userMessageDiv.innerHTML = `
        <div style="background: #16a34a; color: white; border-radius: 8px; padding: 8px 12px; max-width: 80%;">
            <p style="font-size: 14px; margin: 0;">${message}</p>
        </div>
    `;
    messagesContainer.appendChild(userMessageDiv);
    
    input.value = '';
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Add debug response
    const botResponseDiv = document.createElement('div');
    botResponseDiv.style.cssText = 'display: flex; margin-bottom: 8px;';
    botResponseDiv.innerHTML = `
        <div style="background: white; border-radius: 8px; padding: 8px 12px; max-width: 80%; border: 1px solid #e5e7eb;">
            <p style="font-size: 14px; margin: 0;">Debug: Received "${message}" - Basic functionality working!</p>
        </div>
    `;
    messagesContainer.appendChild(botResponseDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Add working book recommendations
    const recommendations = [
        {
            title: "The Girl with the Dragon Tattoo",
            author: "Stieg Larsson",
            description: "A gripping mystery with complex characters and dark secrets."
        },
        {
            title: "Gone Girl",
            author: "Gillian Flynn",
            description: "A psychological thriller with twists you won't see coming."
        },
        {
            title: "The Da Vinci Code",
            author: "Dan Brown",
            description: "A fast-paced mystery combining art, history, and conspiracy."
        }
    ];
    
    const recDiv = document.createElement('div');
    recDiv.style.cssText = 'display: flex; margin-bottom: 8px;';
    recDiv.innerHTML = `
        <div style="background: #dcfce7; border-radius: 8px; padding: 12px; max-width: 80%; border: 1px solid #16a34a;">
            <p style="font-size: 14px; margin: 0 0 8px 0;">Here are 3 mystery novels you might love:</p>
            ${recommendations.map((book, index) => `
                <div style="margin-bottom: 8px; padding: 8px; background: white; border-radius: 4px; border-left: 3px solid #16a34a;">
                    <p style="font-weight: 600; margin: 0 0 4px 0;">${index + 1}. "${book.title}" by ${book.author}</p>
                    <p style="font-size: 12px; color: #059669; margin: 0;">${book.description}</p>
                </div>
            `).join('')}
        </div>
    `;
    messagesContainer.appendChild(recDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}
</script>

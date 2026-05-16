<!-- Working PageTurner AI Chatbot -->
<div id="working-chatbot" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <!-- Chat Toggle Button -->
    <button 
        onclick="toggleWorkingChat()"
        style="background-color: #16a34a; color: white; border-radius: 50%; padding: 16px; border: none; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s;"
        onmouseover="this.style.backgroundColor='#15803d'"
        onmouseout="this.style.backgroundColor='#16a34a'"
    >
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
    </button>

    <!-- Chat Window -->
    <div id="working-chat-window" style="display: none; position: absolute; bottom: 80px; right: 0; width: 384px; height: 600px; background: white; border-radius: 8px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e5e7eb; flex-direction: column;">
        
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
            <button onclick="toggleWorkingChat()" style="background: none; border: none; color: white; cursor: pointer; padding: 4px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Chat Messages -->
        <div id="working-messages" style="flex: 1; padding: 16px; overflow-y: auto; background: #f9fafb;">
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
            <form onsubmit="sendWorkingMessage(event)" style="display: flex; align-items: center; gap: 8px;">
                <input 
                    id="working-chat-input"
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
function toggleWorkingChat() {
    const chatWindow = document.getElementById('working-chat-window');
    if (chatWindow.style.display === 'none' || chatWindow.style.display === '') {
        chatWindow.style.display = 'flex';
        document.getElementById('working-chat-input').focus();
    } else {
        chatWindow.style.display = 'none';
    }
}

function sendWorkingMessage(event) {
    event.preventDefault();
    const input = document.getElementById('working-chat-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    const messagesContainer = document.getElementById('working-messages');
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
    
    // Get book recommendations based on query
    setTimeout(() => {
        messagesContainer.removeChild(typingDiv);
        
        const recommendations = getBookRecommendations(message.toLowerCase());
        
        const botResponseDiv = document.createElement('div');
        botResponseDiv.style.cssText = 'display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;';
        
        let responseContent = '';
        if (recommendations.length > 0) {
            responseContent = `<p style="font-size: 14px; color: #1f2937; margin: 0 0 12px 0;">Here are ${recommendations.length} ${recommendations[0].category} books you might love:</p>`;
            
            recommendations.forEach((book, index) => {
                responseContent += `
                    <div style="margin-bottom: 12px; padding: 8px; background: #f9fafb; border-left: 3px solid #16a34a; border-radius: 4px;">
                        <p style="font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">${index + 1}. "${book.title}"</p>
                        <p style="font-size: 13px; color: #6b7280; font-style: italic; margin: 0 0 8px 0;">${book.author}</p>
                        <p style="font-size: 12px; color: #059669; margin: 0;"><strong>Why you'd like it:</strong> ${book.description}</p>
                    </div>
                `;
            });
        } else {
            // Check if it's a greeting
            const greetings = ['hi', 'hello', 'hey', 'thanks', 'thank you'];
            const queryLower = message.toLowerCase();
            if (greetings.some(greeting => queryLower.includes(greeting))) {
                if (queryLower.includes('thanks') || queryLower.includes('thank')) {
                    responseContent = `<p style="font-size: 14px; color: #1f2937; margin: 0;">You're welcome! I'm here to help anytime you need book recommendations. What genre are you interested in?</p>`;
                } else if (queryLower.includes('bye') || queryLower.includes('goodbye')) {
                    responseContent = `<p style="font-size: 14px; color: #1f2937; margin: 0;">Happy reading! Feel free to come back anytime you need more book recommendations. 📚</p>`;
                } else {
                    responseContent = `<p style="font-size: 14px; color: #1f2937; margin: 0;">Hello! I'm here to help you find your next great read. What genre or type of books are you interested in?</p>`;
                }
            } else {
                responseContent = `<p style="font-size: 14px; color: #1f2937; margin: 0;">I'd love to help you find the perfect book! Could you tell me more about what you're in the mood for? For example, are you looking for mystery, fantasy, romance, sci-fi, horror, or thriller novels?</p>`;
            }
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
    }, 1000);
}

function getBookRecommendations(query) {
    const bookDatabase = {
        mystery: [
            { title: "The Girl with the Dragon Tattoo", author: "Stieg Larsson", description: "A gripping mystery with complex characters and dark secrets.", category: "mystery" },
            { title: "Gone Girl", author: "Gillian Flynn", description: "A psychological thriller with twists you won't see coming.", category: "mystery" },
            { title: "The Da Vinci Code", author: "Dan Brown", description: "A fast-paced mystery combining art, history, and conspiracy.", category: "mystery" }
        ],
        fantasy: [
            { title: "The Name of the Wind", author: "Patrick Rothfuss", description: "Beautiful prose and compelling storytelling in a magical world.", category: "fantasy" },
            { title: "The Way of Kings", author: "Brandon Sanderson", description: "Epic world-building and complex magic systems.", category: "fantasy" },
            { title: "The Hobbit", author: "J.R.R. Tolkien", description: "A classic adventure perfect for fantasy newcomers.", category: "fantasy" }
        ],
        romance: [
            { title: "Pride and Prejudice", author: "Jane Austen", description: "Timeless romance with wit and social commentary.", category: "romance" },
            { title: "The Notebook", author: "Nicholas Sparks", description: "Heartwarming love story that spans decades.", category: "romance" },
            { title: "Me Before You", author: "Jojo Moyes", description: "Emotional and thought-provoking romance.", category: "romance" }
        ],
        scifi: [
            { title: "Project Hail Mary", author: "Andy Weir", description: "Clever science, humor, and nonstop momentum.", category: "science fiction" },
            { title: "Dune", author: "Frank Herbert", description: "Epic science fiction with political intrigue.", category: "science fiction" },
            { title: "The Martian", author: "Andy Weir", description: "Survival story with scientific accuracy and humor.", category: "science fiction" }
        ],
        thriller: [
            { title: "The Silent Patient", author: "Alex Michaelides", description: "Psychological thriller with a shocking twist.", category: "thriller" },
            { title: "The Guest List", author: "Lucy Foley", description: "Murder mystery set at a wedding.", category: "thriller" },
            { title: "The Woman in the Window", author: "A.J. Finn", description: "Hitchcock-style thriller with unreliable narrator.", category: "thriller" }
        ],
        horror: [
            { title: "The Shining", author: "Stephen King", description: "A terrifying tale of isolation and madness in an isolated hotel.", category: "horror" },
            { title: "Dracula", author: "Bram Stoker", description: "The classic vampire tale that defined the horror genre.", category: "horror" },
            { title: "It", author: "Stephen King", description: "A shape-shifting entity terrorizes a small town in this epic horror story.", category: "horror" }
        ]
    };
    
    // Check for greetings first
    const greetings = ['hi', 'hello', 'hey', 'thanks', 'thank you', 'bye', 'goodbye'];
    if (greetings.some(greeting => query.toLowerCase().includes(greeting))) {
        return []; // Return empty array to trigger greeting response
    }
    
    // Check for keywords in the query
    for (const [genre, books] of Object.entries(bookDatabase)) {
        if (query.includes(genre) || 
            (genre === 'scifi' && (query.includes('sci-fi') || query.includes('sci fi') || query.includes('science fiction'))) ||
            (genre === 'romance' && query.includes('romantic'))) {
            return books;
        }
    }
    
    // Check for mood-based queries
    if (query.includes('sad') || query.includes('emotional') || query.includes('heartbreaking')) {
        return bookDatabase.romance; // Recommend emotional romance for sad stories
    }
    if (query.includes('scary') || query.includes('terrifying') || query.includes('spooky')) {
        return bookDatabase.horror; // Recommend horror for scary requests
    }
    
    // Check for other book-related keywords
    const bookKeywords = ['book', 'books', 'novel', 'novels', 'story', 'stories', 'reading', 'read'];
    if (bookKeywords.some(keyword => query.includes(keyword))) {
        return bookDatabase.fantasy; // Return fantasy as default for book-related queries
    }
    
    // Return empty array for non-book queries
    return [];
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

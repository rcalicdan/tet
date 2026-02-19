<!DOCTYPE html>
<html lang="en">

<head>
    <title>Message Module Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen" x-data="app()">

    <div class="max-w-6xl mx-auto p-6 space-y-6">

        <h1 class="text-3xl font-bold text-gray-800">📱 Messaging Module Test</h1>

        <!-- Step 1: Authentication -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-gray-700 border-b-2 border-blue-500 pb-2 mb-4">Step 1: Authentication</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- User 1 -->
                <div>
                    <h3 class="font-semibold text-gray-600 mb-3">User 1 (Client)</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <input type="email" x-model="users[1].email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Password</label>
                            <input type="password" x-model="users[1].password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <button @click="login(1)" :disabled="users[1].loading"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            <span x-text="users[1].loading ? 'Logging in...' : 'Login User 1'"></span>
                        </button>
                        <div x-show="users[1].status" x-text="users[1].status"
                            :class="users[1].token ? 'bg-green-50 text-green-700 border border-green-200' :
                                'bg-red-50 text-red-700 border border-red-200'"
                            class="text-sm px-3 py-2 rounded-lg mt-2">
                        </div>
                        <div x-show="users[1].token" class="text-xs text-gray-400 break-all">
                            <strong>Token:</strong> <span
                                x-text="users[1].token ? users[1].token.substring(0, 50) + '...' : ''"></span>
                        </div>
                    </div>
                </div>

                <!-- User 2 -->
                <div>
                    <h3 class="font-semibold text-gray-600 mb-3">User 2 (Contractor)</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <input type="email" x-model="users[2].email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Password</label>
                            <input type="password" x-model="users[2].password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <button @click="login(2)" :disabled="users[2].loading"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            <span x-text="users[2].loading ? 'Logging in...' : 'Login User 2'"></span>
                        </button>
                        <div x-show="users[2].status" x-text="users[2].status"
                            :class="users[2].token ? 'bg-green-50 text-green-700 border border-green-200' :
                                'bg-red-50 text-red-700 border border-red-200'"
                            class="text-sm px-3 py-2 rounded-lg mt-2">
                        </div>
                        <div x-show="users[2].token" class="text-xs text-gray-400 break-all">
                            <strong>Token:</strong> <span
                                x-text="users[2].token ? users[2].token.substring(0, 50) + '...' : ''"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Step 2: Conversations -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-gray-700 border-b-2 border-blue-500 pb-2 mb-4">Step 2: Conversations</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Active User</label>
                <select x-model="activeUser"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="1">User 1 (Client)</option>
                    <option value="2">User 2 (Contractor)</option>
                </select>
            </div>

            <div class="flex gap-3 mb-4">
                <button @click="getConversations()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Get Conversations
                </button>
                <button @click="showCreateForm = !showCreateForm"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    <span x-text="showCreateForm ? 'Cancel' : 'Create New Conversation'"></span>
                </button>
            </div>

            <!-- Create Conversation Form -->
            <div x-show="showCreateForm" x-transition class="bg-gray-50 rounded-lg p-4 mb-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Contractor ID</label>
                    <input type="text" x-model="newConversation.contractorId" placeholder="UUID of contractor"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Listing ID (optional)</label>
                    <input type="text" x-model="newConversation.listingId" placeholder="UUID of listing"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <button @click="createConversation()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Create
                </button>
            </div>

            <!-- Conversation Status -->
            <div x-show="conversationStatus" x-text="conversationStatus"
                class="text-sm px-3 py-2 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 mb-3">
            </div>

            <!-- Conversations List -->
            <div class="space-y-2">
                <template x-for="conv in conversations" :key="conv.id">
                    <div @click="selectConversation(conv.id)"
                        :class="currentConversationId === conv.id ? 'bg-blue-600 text-white border-blue-700' :
                            'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200'"
                        class="border rounded-lg px-4 py-3 cursor-pointer transition">
                        <div class="font-semibold text-sm"
                            x-text="conv.other_user?.full_name || conv.other_user?.email || 'Unknown'"></div>
                        <div class="text-xs mt-1 opacity-70"
                            x-text="conv.last_message?.message_text?.substring(0, 60) || 'No messages yet'"></div>
                        <div class="text-xs mt-1 opacity-60">Unread: <span x-text="conv.unread_count"></span></div>
                    </div>
                </template>
                <div x-show="conversations.length === 0 && conversationsLoaded"
                    class="text-sm text-gray-400 text-center py-4">No conversations found.</div>
            </div>
        </div>

        <!-- Step 3: WebSocket -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-gray-700 border-b-2 border-blue-500 pb-2 mb-4">Step 3: Real-Time
                Connection</h2>

            <div class="space-y-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Conversation ID</label>
                    <input type="text" x-model="currentConversationId" placeholder="Paste or select conversation ID"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">REVERB_APP_KEY (from .env)</label>
                    <input type="text" x-model="appKey" placeholder="your-app-key"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button @click="connectWebSocket()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Connect
                </button>
                <button @click="disconnectWebSocket()"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Disconnect
                </button>
                <span :class="wsConnected ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    class="text-xs font-bold px-3 py-1 rounded-full">
                    <span x-text="wsConnected ? '● Connected' : '● Disconnected'"></span>
                </span>
            </div>
        </div>

        <!-- Step 4: Chat -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-gray-700 border-b-2 border-blue-500 pb-2 mb-4">Step 4: Chat</h2>

            <div class="flex gap-3 mb-4">
                <button @click="loadMessages()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Load Messages
                </button>
                <button @click="messages = []"
                    class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Clear
                </button>
            </div>

            <!-- Messages -->
            <div class="border border-gray-200 rounded-lg h-96 overflow-y-auto p-4 bg-gray-50 mb-4 space-y-2"
                id="messageBox">
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.isSent ? 'justify-end' : 'justify-start'" class="flex">
                        <div :class="msg.isSent ? 'bg-green-100 border-green-300 text-right' : 'bg-blue-50 border-blue-200'"
                            class="border rounded-xl px-4 py-2 max-w-xs md:max-w-md">
                            <div :class="msg.isSent ? 'text-green-700' : 'text-blue-700'"
                                class="text-xs font-bold mb-1" x-text="msg.sender"></div>
                            <div class="text-sm text-gray-800" x-text="msg.text"></div>
                            <div class="text-xs text-gray-400 mt-1" x-text="msg.time"></div>
                        </div>
                    </div>
                </template>
                <div x-show="messages.length === 0" class="text-center text-gray-400 text-sm pt-10">
                    No messages yet. Load messages or send one!
                </div>
            </div>

            <!-- Input -->
            <div class="flex gap-3">
                <input type="text" x-model="messageInput" placeholder="Type a message..."
                    @keyup.enter="sendMessage()"
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <button @click="sendMessage()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition">
                    Send
                </button>
                <button @click="markAsRead()"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Mark Read
                </button>
            </div>
        </div>

        <!-- Debug Console -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center border-b-2 border-blue-500 pb-2 mb-4">
                <h2 class="text-xl font-bold text-gray-700">Debug Console</h2>
                <button @click="consoleLogs = []"
                    class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-600 px-3 py-1 rounded-lg transition">
                    Clear
                </button>
            </div>
            <div class="bg-gray-900 rounded-lg p-4 h-48 overflow-y-auto font-mono text-xs space-y-1" id="consoleBox">
                <template x-for="(entry, index) in consoleLogs" :key="index">
                    <div :class="{
                        'text-green-400': entry.type === 'success',
                        'text-red-400': entry.type === 'error',
                        'text-blue-300': entry.type === 'info'
                    }"
                        x-text="`[${entry.time}] ${entry.message}`"></div>
                </template>
                <div x-show="consoleLogs.length === 0" class="text-gray-500">No logs yet...</div>
            </div>
        </div>

    </div>

    <script>
        function app() {
            return {
                API_URL: 'http://127.0.0.1:8000/api',
                WS_HOST: '127.0.0.1',
                WS_PORT: 8080,

                activeUser: '1',
                appKey: '',
                currentConversationId: '',
                messageInput: '',
                wsConnected: false,
                showCreateForm: false,
                conversationsLoaded: false,
                conversationStatus: '',
                echo: null,

                users: {
                    1: {
                        email: 'client@example.com',
                        password: '12345678',
                        token: null,
                        data: null,
                        status: '',
                        loading: false
                    },
                    2: {
                        email: 'contractor@example.com',
                        password: '12345678',
                        token: null,
                        data: null,
                        status: '',
                        loading: false
                    },
                },

                newConversation: {
                    contractorId: '',
                    listingId: '',
                },

                conversations: [],
                messages: [],
                consoleLogs: [],

                getHeaders(token = null, includeContentType = true) {
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    if (includeContentType) headers['Content-Type'] = 'application/json';
                    if (token) headers['Authorization'] = `Bearer ${token}`;
                    return headers;
                },

                log(message, type = 'info') {
                    const time = new Date().toLocaleTimeString();
                    this.consoleLogs.push({
                        message,
                        type,
                        time
                    });
                    this.$nextTick(() => {
                        const box = document.getElementById('consoleBox');
                        if (box) box.scrollTop = box.scrollHeight;
                    });
                },

                scrollMessages() {
                    this.$nextTick(() => {
                        const box = document.getElementById('messageBox');
                        if (box) box.scrollTop = box.scrollHeight;
                    });
                },

                addMessage(sender, text, isSent = false, timestamp = null) {
                    const time = timestamp ?
                        new Date(timestamp).toLocaleTimeString() :
                        new Date().toLocaleTimeString();
                    this.messages.push({
                        sender,
                        text,
                        isSent,
                        time
                    });
                    this.scrollMessages();
                },

                async login(userNum) {
                    const user = this.users[userNum];
                    user.loading = true;
                    user.status = '';

                    try {
                        const response = await fetch(`${this.API_URL}/auth/login`, {
                            method: 'POST',
                            headers: this.getHeaders(),
                            body: JSON.stringify({
                                email: user.email,
                                password: user.password
                            }),
                        });

                        const data = await response.json();
                        this.log(`Login response: ${JSON.stringify(data)}`, 'info');

                        if (response.ok && data.data?.access_token) {
                            user.token = data.data.access_token;
                            user.data = data.data.user;
                            user.status = `✓ Logged in as ${data.data.user.full_name || data.data.user.email}`;
                            this.log(`User ${userNum} logged in: ${user.data.full_name || user.data.email}`, 'success');
                        } else {
                            throw new Error(data.message || `HTTP ${response.status}: Login failed`);
                        }
                    } catch (error) {
                        user.status = `✗ ${error.message}`;
                        this.log(`User ${userNum} login failed: ${error.message}`, 'error');
                    } finally {
                        user.loading = false;
                    }
                },

                async getConversations() {
                    const token = this.users[this.activeUser].token;
                    this.conversationStatus = '';

                    if (!token) {
                        this.conversationStatus = '⚠ Please login first';
                        return;
                    }

                    try {
                        const response = await fetch(`${this.API_URL}/conversations`, {
                            headers: this.getHeaders(token, false),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.conversations = data.data;
                            this.conversationsLoaded = true;
                            this.log(`Loaded ${data.data.length} conversations`, 'success');
                        } else {
                            throw new Error(data.message || 'Failed to load conversations');
                        }
                    } catch (error) {
                        this.conversationStatus = `✗ ${error.message}`;
                        this.log(`Error loading conversations: ${error.message}`, 'error');
                    }
                },

                selectConversation(id) {
                    this.currentConversationId = id;
                    this.log(`Selected conversation: ${id}`, 'success');
                },

                async createConversation() {
                    const token = this.users[this.activeUser].token;

                    if (!token) {
                        this.conversationStatus = '⚠ Please login first';
                        return;
                    }

                    try {
                        const body = {
                            contractor_id: this.newConversation.contractorId
                        };
                        if (this.newConversation.listingId) body.listing_id = this.newConversation.listingId;

                        const response = await fetch(`${this.API_URL}/conversations`, {
                            method: 'POST',
                            headers: this.getHeaders(token),
                            body: JSON.stringify(body),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.currentConversationId = data.data.id;
                            this.showCreateForm = false;
                            this.conversationStatus = '✓ Conversation created!';
                            this.log(`Created conversation: ${data.data.id}`, 'success');
                            await this.getConversations();
                        } else {
                            throw new Error(data.message || 'Failed to create conversation');
                        }
                    } catch (error) {
                        this.conversationStatus = `✗ ${error.message}`;
                        this.log(`Error creating conversation: ${error.message}`, 'error');
                    }
                },

                connectWebSocket() {
                    const token = this.users[this.activeUser].token;

                    if (!token) {
                        alert('Please login first');
                        return;
                    }
                    if (!this.currentConversationId) {
                        alert('Please enter or select a conversation ID');
                        return;
                    }
                    if (!this.appKey) {
                        alert('Please enter your REVERB_APP_KEY');
                        return;
                    }

                    try {
                        this.echo = new Echo({
                            broadcaster: 'reverb',
                            key: this.appKey,
                            wsHost: this.WS_HOST,
                            wsPort: this.WS_PORT,
                            wssPort: this.WS_PORT,
                            forceTLS: false,
                            enabledTransports: ['ws', 'wss'],
                            authEndpoint: 'http://127.0.0.1:8000/api/broadcasting/auth',
                            auth: {
                                headers: {
                                    'Authorization': `Bearer ${token}`,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            },
                        });

                        this.echo.private(`conversation.${this.currentConversationId}`)
                            .listen('.message.sent', (e) => {
                                this.log('MessageSent event received', 'success');
                                const senderName = e.sender?.full_name || e.sender?.email || 'Unknown';
                                this.addMessage(senderName, e.message_text, false);
                            })
                            .listen('.message.read', (e) => {
                                this.log('MessageRead event received', 'success');
                                this.addMessage('System', '✓ Messages marked as read', false);
                            });

                        this.wsConnected = true;
                        this.log(`WebSocket connected to conversation: ${this.currentConversationId}`, 'success');
                    } catch (error) {
                        this.wsConnected = false;
                        this.log(`WebSocket error: ${error.message}`, 'error');
                    }
                },

                disconnectWebSocket() {
                    if (this.echo) {
                        this.echo.disconnect();
                        this.echo = null;
                        this.wsConnected = false;
                        this.log('WebSocket disconnected', 'info');
                    }
                },

                async loadMessages() {
                    const token = this.users[this.activeUser].token;

                    if (!token || !this.currentConversationId) {
                        alert('Please login and select a conversation first');
                        return;
                    }

                    try {
                        const response = await fetch(
                            `${this.API_URL}/conversations/${this.currentConversationId}/messages`, {
                                headers: this.getHeaders(token, false),
                            });

                        const data = await response.json();

                        if (response.ok) {
                            this.messages = [];
                            const msgs = (data.data || []).reverse();
                            msgs.forEach(msg => {
                                const isSent = msg.sender_id === this.users[this.activeUser].data?.id;
                                const senderName = msg.sender.full_name || msg.sender.email;
                                this.addMessage(senderName, msg.message_text, isSent, msg.created_at);
                            });
                            this.log(`Loaded ${msgs.length} messages`, 'success');
                        } else {
                            throw new Error(data.message || 'Failed to load messages');
                        }
                    } catch (error) {
                        this.log(`Error loading messages: ${error.message}`, 'error');
                    }
                },

                async sendMessage() {
                    const token = this.users[this.activeUser].token;
                    const text = this.messageInput.trim();

                    if (!token || !this.currentConversationId) {
                        alert('Please login and select a conversation first');
                        return;
                    }

                    if (!text) {
                        alert('Please enter a message');
                        return;
                    }

                    try {
                        const response = await fetch(
                            `${this.API_URL}/conversations/${this.currentConversationId}/messages`, {
                                method: 'POST',
                                headers: {
                                    ...this.getHeaders(token),
                                    'X-Socket-ID': this.echo ? this.echo.socketId() : '',
                                },
                                body: JSON.stringify({
                                    message_text: text
                                }),
                            });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.addMessage('You', text, true);
                            this.messageInput = '';
                            this.log('Message sent successfully', 'success');
                        } else {
                            throw new Error(data.message || 'Failed to send message');
                        }
                    } catch (error) {
                        this.log(`Error sending message: ${error.message}`, 'error');
                        alert(`Error: ${error.message}`);
                    }
                },

                async markAsRead() {
                    const token = this.users[this.activeUser].token;

                    if (!token || !this.currentConversationId) {
                        alert('Please login and select a conversation first');
                        return;
                    }

                    try {
                        const response = await fetch(
                            `${this.API_URL}/conversations/${this.currentConversationId}/mark-read`, {
                                method: 'POST',
                                headers: this.getHeaders(token, false),
                            });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.log(`Marked ${data.data.marked_count} messages as read`, 'success');
                            alert(`${data.data.marked_count} messages marked as read`);
                        } else {
                            throw new Error(data.message || 'Failed to mark messages as read');
                        }
                    } catch (error) {
                        this.log(`Error marking messages as read: ${error.message}`, 'error');
                    }
                },
            }
        }
    </script>

</body>

</html>

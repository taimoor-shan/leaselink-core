<?php
/**
 * Messages — Two-panel messaging interface
 *
 * @package LeaseLink
 */

use StudentRentalPlatform\SRP_Template_Loader;

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$user = wp_get_current_user();
$role = in_array('landlord', $user->roles) ? 'landlord' : 'student';

global $wpdb;
$msg_table = $wpdb->prefix . 'rental_messages';
$nonce = wp_create_nonce('wp_rest');

// Get unique threads (grouped by thread_id)
$threads = $wpdb->get_results($wpdb->prepare(
    "SELECT m.thread_id,
            MAX(m.sent_at) as last_message_at,
            SUM(CASE WHEN m.recipient_id = %d AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count,
            (SELECT message_content FROM {$msg_table} WHERE thread_id = m.thread_id ORDER BY sent_at DESC LIMIT 1) as last_message
     FROM {$msg_table} m
     WHERE m.sender_id = %d OR m.recipient_id = %d
     GROUP BY m.thread_id
     ORDER BY last_message_at DESC",
    $user_id,
    $user_id,
    $user_id
));

// Parse thread IDs to get other user info
foreach ($threads as &$thread) {
    $parts = explode('_', $thread->thread_id);
    $student_id = intval($parts[0] ?? 0);
    $landlord_id = intval($parts[1] ?? 0);
    $listing_id = intval($parts[2] ?? 0);
    $other_id = ($user_id === $student_id) ? $landlord_id : $student_id;
    $other_user = get_userdata($other_id);
    $thread->other_name = $other_user ? $other_user->display_name : 'User';
    $thread->other_initials = strtoupper(substr($thread->other_name, 0, 2));
    $thread->listing_title = $listing_id ? get_the_title($listing_id) : '';
}
unset($thread);
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => $role]); ?>

    <div class="ll-dashboard-content" x-data="messaging()">
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-1">Messages</h1>
            <p class="text-gray">Communicate with
                <?php echo $role === 'student' ? 'landlords' : 'students'; ?> about listings.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden"
            style="height: calc(100vh - 250px); min-height: 500px;">
            <div class="flex h-full">
                <!-- Thread List -->
                <div class="w-80 border-r border-zinc-100 flex flex-col shrink-0"
                    :class="{ 'hidden md:flex': activeThread }">
                    <div class="p-4 border-b border-zinc-100">
                        <input type="text" x-model="searchQuery" placeholder="Search conversations..." class="ll-input">
                    </div>
                    <div class="flex-1 overflow-y-auto divide-y divide-zinc-50">
                        <?php if (!empty($threads)): ?>
                            <?php foreach ($threads as $idx => $thread): ?>
                                <button
                                    @click="selectThread('<?php echo esc_js($thread->thread_id); ?>', '<?php echo esc_js($thread->other_name); ?>')"
                                    class="w-full text-left p-4 hover:bg-zinc-50 transition-colors flex items-start gap-3"
                                    :class="{ 'bg-primary/5 border-l-2 border-primary': activeThread === '<?php echo esc_js($thread->thread_id); ?>' }">
                                    <div
                                        class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 text-sm font-semibold">
                                        <?php echo esc_html($thread->other_initials); ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <p class="font-semibold text-sm text-dark mb-0 truncate">
                                                <?php echo esc_html($thread->other_name); ?>
                                            </p>
                                            <?php if ($thread->unread_count > 0): ?>
                                                <span
                                                    class="w-5 h-5 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">
                                                    <?php echo esc_html($thread->unread_count); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($thread->listing_title): ?>
                                            <p class="text-xs text-primary mb-0.5 truncate">
                                                <?php echo esc_html($thread->listing_title); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray mb-0 truncate">
                                            <?php echo esc_html(wp_trim_words($thread->last_message, 8)); ?>
                                        </p>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-8 text-center">
                                <svg class="w-10 h-10 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-sm text-gray mb-0">No conversations yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="flex-1 flex flex-col" :class="{ 'hidden md:flex': !activeThread }">
                    <template x-if="!activeThread">
                        <div class="flex-1 flex items-center justify-center bg-zinc-50/50">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-zinc-200 mx-auto mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-gray text-sm">Select a conversation to start messaging</p>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeThread">
                        <div class="flex flex-col h-full">
                            <!-- Chat Header -->
                            <div class="p-4 border-b border-zinc-100 flex items-center gap-3">
                                <button @click="activeThread = null" class="md:hidden p-1 text-gray hover:text-dark">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold"
                                    x-text="activeName.substring(0,2).toUpperCase()"></div>
                                <p class="font-semibold text-sm text-dark mb-0" x-text="activeName"></p>
                            </div>

                            <!-- Messages -->
                            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container">
                                <template x-for="msg in messages" :key="msg.message_id">
                                    <div
                                        :class="msg.sender_id == <?php echo $user_id; ?> ? 'flex justify-end' : 'flex justify-start'">
                                        <div class="max-w-xs lg:max-w-md px-4 py-2.5 rounded-2xl text-sm"
                                            :class="msg.sender_id == <?php echo $user_id; ?> ? 'bg-primary text-white rounded-br-md' : 'bg-zinc-100 text-dark rounded-bl-md'">
                                            <p class="mb-0" x-text="msg.message_content"></p>
                                            <p class="text-xs mt-1 mb-0 opacity-70" x-text="formatTime(msg.sent_at)">
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="messages.length === 0" class="text-center text-gray text-sm py-8">
                                    No messages yet. Start the conversation!
                                </div>
                            </div>

                            <!-- Input -->
                            <div class="p-4 border-t border-zinc-100">
                                <form @submit.prevent="sendMessage()" class="flex gap-2">
                                    <input type="text" x-model="newMessage" class="ll-input flex-1"
                                        placeholder="Type a message..." autocomplete="off">
                                    <button type="submit" class="ll-btn ll-btn-primary"
                                        :disabled="!newMessage.trim() || sending">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function messaging() {
        return {
            activeThread: null,
            activeName: '',
            messages: [],
            newMessage: '',
            sending: false,
            searchQuery: '',

            async selectThread(threadId, name) {
                this.activeThread = threadId;
                this.activeName = name;
                await this.loadMessages();
            },

            async loadMessages() {
                try {
                    const res = await fetch(`<?php echo esc_url(rest_url('rental/v1/messages/')); ?>?thread_id=${this.activeThread}`, {
                        headers: { 'X-WP-Nonce': '<?php echo $nonce; ?>' }
                    });
                    if (res.ok) {
                        this.messages = await res.json();
                        this.$nextTick(() => {
                            const container = document.getElementById('messages-container');
                            if (container) container.scrollTop = container.scrollHeight;
                        });
                    }
                } catch (e) { console.error('Failed to load messages:', e); }
            },

            async sendMessage() {
                if (!this.newMessage.trim() || this.sending) return;
                this.sending = true;
                try {
                    const res = await fetch('<?php echo esc_url(rest_url('rental/v1/messages')); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                        body: JSON.stringify({ thread_id: this.activeThread, message: this.newMessage }),
                    });
                    if (res.ok) {
                        this.newMessage = '';
                        await this.loadMessages();
                    }
                } catch (e) { console.error('Failed to send message:', e); }
                this.sending = false;
            },

            formatTime(dateStr) {
                const d = new Date(dateStr);
                const now = new Date();
                if (d.toDateString() === now.toDateString()) {
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
        };
    }
</script>

<?php get_footer(); ?>
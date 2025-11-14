import { Controller } from "@hotwired/stimulus";

/**
 * Chat Controller - WhatsApp-like messaging interface
 *
 * Manages talk list, message thread, and real-time messaging
 */
export default class extends Controller {
    static targets = [
        "talkList",
        "messageThread",
        "messageInput",
        "sendButton",
        "emptyState",
        "chatHeader",
        "composer",
        "search",
        "talkSubject",
        "talkParticipants",
        "fileInput",
        "messagesLoading",
        "toast",
        "toastBody",
        "toastContainer",
        "notificationToggle",
        "sidebar"
    ];

    static values = {
        userId: String,
        currentTalk: String
    };

    connect() {
        console.log("Chat controller connected");
        this.currentTalkId = this.currentTalkValue || null;
        this.lastMessageId = null;
        this.eventSource = null;
        this.originalTitle = document.title;
        this.totalUnreadCount = 0;
        this.isVisible = true;
        this.isAtBottom = true;
        this.hasNewMessages = false;
        this.soundEnabled = true;
        this.selectedFiles = []; // Phase 3: Store selected files
        this.typingTimeout = null; // Phase 4: Typing indicator timeout
        this.isTyping = false; // Phase 4: Track typing state
        this.currentFilter = 'all'; // Talk filter: 'all' or 'user'

        // Load initial data
        this.loadTalks();

        // If a talk is already selected, load its messages
        if (this.currentTalkId) {
            this.loadMessages(this.currentTalkId);
            this.subscribeToMercure(this.currentTalkId);
        }

        // Auto-resize textarea
        if (this.hasMessageInputTarget) {
            this.messageInputTarget.addEventListener('input', () => this.autoResizeTextarea());
        }

        // Setup visibility change detection (Phase 2)
        this.setupVisibilityDetection();

        // Setup scroll detection for new messages banner (Phase 2)
        this.setupScrollDetection();

        // Setup connection status indicator (Phase 2)
        this.setupConnectionMonitor();

        // Check notification permission status (Phase 2)
        this.checkNotificationPermission();

        // Setup drag-and-drop (Phase 3)
        this.setupDragAndDrop();

        // Listen for modal success events (when Talk is created via modal)
        this.boundHandleModalSuccess = this.handleModalSuccess.bind(this);
        window.addEventListener('modal:success', this.boundHandleModalSuccess);
    }

    disconnect() {
        this.unsubscribeFromMercure();
        if (this.boundHandleModalSuccess) {
            window.removeEventListener('modal:success', this.boundHandleModalSuccess);
        }
    }

    /**
     * Load list of talks from API (Enhanced for Phase 2)
     */
    async loadTalks() {
        try {
            const url = `/chat/api/talks/list?filter=${this.currentFilter}`;
            const response = await fetch(url, {
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to load talks');

            const talks = await response.json();

            // Calculate total unread count (Phase 2)
            this.totalUnreadCount = talks.reduce((sum, talk) => sum + (talk.unreadCount || 0), 0);

            // Update browser title if tab is hidden
            if (!this.isVisible) {
                this.updateBrowserTitle(this.totalUnreadCount);
            }

            this.renderTalkList(talks);
        } catch (error) {
            console.error('Failed to load talks:', error);
            this.showError('Failed to load conversations');
        }
    }

    /**
     * Render talk list in sidebar
     */
    renderTalkList(talks) {
        if (!this.hasTalkListTarget) return;

        if (talks.length === 0) {
            this.talkListTarget.innerHTML = `
                <div class="text-center p-4">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No conversations yet</p>
                    <button type="button"
                            class="btn btn-sm btn-primary"
                            data-controller="modal-opener"
                            data-modal-opener-url-value="/talk/new"
                            data-action="click->modal-opener#open">
                        <i class="bi bi-plus-lg me-2"></i>Start a conversation
                    </button>
                </div>
            `;
            return;
        }

        this.talkListTarget.innerHTML = talks.map(talk => `
            <div class="talk-item ${talk.id === this.currentTalkId ? 'active' : ''}"
                 data-talk-id="${talk.id}"
                 data-action="click->chat#selectTalk">
                <div class="talk-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="talk-content">
                    <div class="talk-header">
                        <span class="talk-subject">${this.escapeHtml(talk.subject)}</span>
                        <span class="talk-time">${this.formatTime(talk.dateLastMessage)}</span>
                    </div>
                    <div class="talk-preview">
                        <span class="text-truncate">${this.escapeHtml(talk.lastMessagePreview || 'No messages yet')}</span>
                        ${talk.unreadCount > 0 ? `<span class="badge bg-primary rounded-pill">${talk.unreadCount}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Handle talk selection
     */
    async selectTalk(event) {
        const talkItem = event.currentTarget;
        const talkId = talkItem.dataset.talkId;

        // Update active state
        if (this.hasTalkListTarget) {
            this.talkListTarget.querySelectorAll('.talk-item').forEach(item => {
                item.classList.remove('active');
            });
            talkItem.classList.add('active');
        }

        this.currentTalkId = talkId;
        await this.loadMessages(talkId);

        // Close sidebar on mobile after selecting a talk
        if (this.hasSidebarTarget && window.innerWidth < 992) {
            this.sidebarTarget.classList.remove('show');
        }

        // Show chat interface
        if (this.hasEmptyStateTarget) {
            this.emptyStateTarget.classList.add('d-none');
        }
        if (this.hasChatHeaderTarget) {
            this.chatHeaderTarget.classList.remove('d-none');
        }
        if (this.hasMessageThreadTarget) {
            this.messageThreadTarget.classList.remove('d-none');
        }
        if (this.hasComposerTarget) {
            this.composerTarget.classList.remove('d-none');
        }

        // Subscribe to Mercure updates for this talk
        this.subscribeToMercure(talkId);

        // Update URL without reload
        window.history.pushState({}, '', `/chat/${talkId}`);
    }

    /**
     * Load messages for a talk
     */
    async loadMessages(talkId, beforeId = null) {
        if (!this.hasMessageThreadTarget) return;

        try {
            // Show loading state
            if (!beforeId && this.hasMessagesLoadingTarget) {
                this.messagesLoadingTarget.classList.remove('d-none');
            }

            const url = new URL(`/chat/api/talk/${talkId}/messages`, window.location.origin);
            if (beforeId) url.searchParams.set('before', beforeId);

            const response = await fetch(url, {
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to load messages');

            const data = await response.json();

            // Hide loading state
            if (this.hasMessagesLoadingTarget) {
                this.messagesLoadingTarget.classList.add('d-none');
            }

            // Update header
            if (this.hasTalkSubjectTarget && data.talk) {
                this.talkSubjectTarget.textContent = data.talk.subject;
            }
            if (this.hasTalkParticipantsTarget && data.talk) {
                this.talkParticipantsTarget.textContent = data.talk.participants;
            }

            // Render messages
            this.renderMessages(data.messages, beforeId ? 'prepend' : 'replace');

            // Scroll to bottom (for new load, not infinite scroll)
            if (!beforeId) {
                this.scrollToBottom();
            }

            // Mark messages as read
            await this.markMessagesAsRead(data.messages);

            // Store last message ID for polling
            if (data.messages.length > 0) {
                this.lastMessageId = data.messages[data.messages.length - 1].id;
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
            this.showError('Failed to load messages');
        }
    }

    /**
     * Render messages in thread
     */
    renderMessages(messages, mode = 'replace') {
        if (!this.hasMessageThreadTarget) return;

        if (messages.length === 0 && mode === 'replace') {
            this.messageThreadTarget.innerHTML = `
                <div class="text-center p-4">
                    <i class="bi bi-chat text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No messages yet</p>
                    <p class="text-muted small">Start the conversation by sending a message below</p>
                </div>
            `;
            return;
        }

        // Group messages by date
        const grouped = this.groupMessagesByDate(messages);

        let html = '';
        for (const [date, msgs] of Object.entries(grouped)) {
            html += `<div class="date-separator"><span>${date}</span></div>`;

            msgs.forEach((msg, index) => {
                const isOwn = msg.fromUser?.id === this.userIdValue;
                const showAvatar = index === 0 || msgs[index - 1].fromUser?.id !== msg.fromUser?.id;

                html += this.renderMessage(msg, isOwn, showAvatar);
            });
        }

        if (mode === 'replace') {
            this.messageThreadTarget.innerHTML = html;
        } else if (mode === 'prepend') {
            this.messageThreadTarget.insertAdjacentHTML('afterbegin', html);
        } else if (mode === 'append') {
            this.messageThreadTarget.insertAdjacentHTML('beforeend', html);
        }
    }

    /**
     * Render single message (Enhanced with attachments - Phase 3)
     */
    renderMessage(msg, isOwn, showAvatar) {
        // Render attachments
        let attachmentsHtml = '';
        if (msg.attachments && msg.attachments.length > 0) {
            attachmentsHtml = '<div class="message-attachments">' +
                msg.attachments.map(att => this.renderAttachment(att)).join('') +
                '</div>';
        }

        return `
            <div class="message ${isOwn ? 'message-own' : 'message-other'}"
                 data-message-id="${msg.id}">
                ${!isOwn && showAvatar ? `
                    <div class="message-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                ` : '<div class="message-avatar-spacer"></div>'}

                <div class="message-bubble">
                    ${!isOwn && showAvatar ? `
                        <div class="message-sender">${this.escapeHtml(msg.fromUser?.name || msg.fromContact?.name || 'Unknown')}</div>
                    ` : ''}
                    ${msg.body ? `<div class="message-body">${this.escapeHtml(msg.body)}</div>` : ''}
                    ${attachmentsHtml}
                    <div class="message-meta">
                        <span class="message-time">${this.formatTime(msg.sentAt)}</span>
                        ${isOwn ? this.renderReadReceipt(msg) : ''}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render single attachment (Phase 3)
     */
    renderAttachment(attachment) {
        if (attachment.isImage) {
            // Image attachment - show thumbnail with lightbox
            return `
                <div class="attachment attachment-image">
                    <a href="${attachment.url}" target="_blank" rel="noopener" class="attachment-image-link">
                        <img src="${attachment.url}" alt="${this.escapeHtml(attachment.filename)}" class="attachment-thumb">
                        <div class="attachment-overlay">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </div>
                    </a>
                </div>
            `;
        } else {
            // File attachment - show icon with download
            return `
                <div class="attachment attachment-file">
                    <i class="${attachment.icon} attachment-icon"></i>
                    <div class="attachment-details">
                        <a href="${attachment.url}" download class="attachment-name">${this.escapeHtml(attachment.filename)}</a>
                        <div class="attachment-size">${attachment.formattedSize}</div>
                    </div>
                    <a href="${attachment.url}" download class="btn btn-sm btn-link attachment-download">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            `;
        }
    }

    /**
     * Render read receipt icon
     */
    renderReadReceipt(message) {
        if (message.read) {
            return '<i class="bi bi-check2-all text-primary" title="Read"></i>';
        } else if (message.deliveredAt) {
            return '<i class="bi bi-check2-all text-muted" title="Delivered"></i>';
        } else {
            return '<i class="bi bi-check text-muted" title="Sent"></i>';
        }
    }

    /**
     * Send a message (Enhanced with file upload - Phase 3)
     */
    async sendMessage() {
        if (!this.hasMessageInputTarget || !this.currentTalkId) return;

        const body = this.messageInputTarget.value.trim();
        const hasFiles = this.selectedFiles.length > 0;

        // Must have either body or files
        if (!body && !hasFiles) return;

        try {
            this.sendButtonTarget.disabled = true;

            let response;

            if (hasFiles) {
                // Send as multipart/form-data with files
                const formData = new FormData();
                formData.append('body', body);

                this.selectedFiles.forEach(file => {
                    formData.append('attachments[]', file);
                });

                response = await fetch(`/chat/api/talk/${this.currentTalkId}/send`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
            } else {
                // Send as JSON (text only)
                response = await fetch(`/chat/api/talk/${this.currentTalkId}/send`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ body })
                });
            }

            if (!response.ok) throw new Error('Failed to send message');

            const message = await response.json();

            // Clear input and files
            this.messageInputTarget.value = '';
            this.autoResizeTextarea();
            this.clearSelectedFiles();

            // Don't render message here - let Mercure handle it to avoid duplicates
            // The message will appear via Mercure real-time update

            // Update last message ID
            this.lastMessageId = message.id;

            // Note: Scroll and talk list reload will happen when Mercure event arrives

        } catch (error) {
            console.error('Failed to send message:', error);
            this.showError('Failed to send message');
        } finally {
            // Re-enable button
            if (this.hasSendButtonTarget) {
                this.sendButtonTarget.disabled = false;
            }
            // Refocus input (ensure it still exists)
            if (this.hasMessageInputTarget) {
                this.messageInputTarget.focus();
            }
        }
    }

    /**
     * Handle keyboard events in message input
     */
    handleKeydown(event) {
        // Send on Enter (Shift+Enter for newline)
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
        }
    }

    /**
     * Handle input changes (Enhanced with typing indicators - Phase 4)
     */
    handleInput() {
        // Trigger typing indicator
        this.broadcastTypingStatus(true);
    }

    /**
     * Auto-resize textarea based on content
     */
    autoResizeTextarea() {
        if (!this.hasMessageInputTarget) return;

        const textarea = this.messageInputTarget;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 150) + 'px';
    }

    /**
     * Scroll message thread to bottom
     */
    scrollToBottom() {
        if (!this.hasMessageThreadTarget) return;

        this.messageThreadTarget.scrollTop = this.messageThreadTarget.scrollHeight;
    }

    /**
     * Mark messages as read
     */
    async markMessagesAsRead(messages) {
        const otherMessages = messages.filter(m =>
            !m.read && m.fromUser?.id !== this.userIdValue
        );

        for (const msg of otherMessages) {
            try {
                await fetch(`/chat/api/message/${msg.id}/read`, {
                    method: 'POST',
                    credentials: 'same-origin'
                });
            } catch (error) {
                console.error('Failed to mark message as read:', error);
            }
        }
    }

    /**
     * Start polling for new messages
     */
    /**
     * Subscribe to Mercure real-time updates for a talk
     */
    subscribeToMercure(talkId) {
        // Unsubscribe from previous talk if any
        this.unsubscribeFromMercure();

        const mercureUrl = 'http://localhost:3000/.well-known/mercure';
        const topic = `https://luminai.app/chat/talk/${talkId}`;

        const url = new URL(mercureUrl);
        url.searchParams.append('topic', topic);

        this.eventSource = new EventSource(url);

        this.eventSource.onmessage = (event) => {
            this.handleMercureMessage(event);
        };

        this.eventSource.onerror = (error) => {
            // EventSource will automatically reconnect, just update status
            this.updateConnectionStatus('poor');
        };

        this.eventSource.onopen = () => {
            // Connected - update status silently
            this.updateConnectionStatus('online');
        };

        // Still refresh talk list periodically for unread counts (every 10 seconds)
        this.talkListRefreshInterval = setInterval(() => {
            this.loadTalks();
        }, 10000);
    }

    /**
     * Unsubscribe from Mercure updates
     */
    unsubscribeFromMercure() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        if (this.talkListRefreshInterval) {
            clearInterval(this.talkListRefreshInterval);
            this.talkListRefreshInterval = null;
        }
    }

    /**
     * Handle Mercure message event
     */
    handleMercureMessage(event) {
        try {
            const data = JSON.parse(event.data);

            if (data.type === 'new_message') {
                const msg = data.message;
                const isOwn = msg.fromUser?.id === this.userIdValue;

                // Check if message is already displayed (avoid duplicates)
                const messageExists = this.messageThreadTarget.querySelector(`[data-message-id="${msg.id}"]`);
                if (messageExists) {
                    return;
                }

                // Remove "No messages yet" placeholder if it exists
                const emptyPlaceholder = this.messageThreadTarget.querySelector('.text-center.p-4');
                if (emptyPlaceholder) {
                    emptyPlaceholder.remove();
                }

                // Append new message
                const html = this.renderMessage(msg, isOwn, true);
                this.messageThreadTarget.insertAdjacentHTML('beforeend', html);

                // Update last message ID
                this.lastMessageId = msg.id;

                // Handle notifications for other users' messages
                if (!isOwn) {
                    // Show banner if not at bottom
                    if (!this.isAtBottom) {
                        this.showNewMessagesBanner(1);
                    } else {
                        // Scroll to bottom if at bottom
                        this.scrollToBottom();
                    }

                    // Update browser title if tab is hidden
                    if (!this.isVisible) {
                        this.totalUnreadCount += 1;
                        this.updateBrowserTitle(this.totalUnreadCount);
                    }

                    // Play sound for new messages
                    this.playNotificationSound();

                    // Show desktop notification
                    this.showDesktopNotification(msg);

                    // Show toast only if visible
                    if (this.isVisible) {
                        this.showToast('New message');
                    }

                    // Mark as read (only if visible and at bottom)
                    if (this.isVisible && this.isAtBottom) {
                        this.markMessageAsRead(msg.id);
                    }
                } else {
                    // Own messages - always scroll to bottom
                    this.scrollToBottom();
                }

                // Update talk list
                this.loadTalks();
            }
        } catch (error) {
            console.error('Error handling Mercure message:', error);
        }
    }

    /**
     * Mark a single message as read
     */
    async markMessageAsRead(messageId) {
        try {
            await fetch(`/chat/api/message/${messageId}/read`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
        } catch (error) {
            console.error('Failed to mark message as read:', error);
        }
    }

    /**
     * Group messages by date
     */
    groupMessagesByDate(messages) {
        const groups = {};
        messages.forEach(msg => {
            const date = this.formatDate(msg.sentAt);
            if (!groups[date]) groups[date] = [];
            groups[date].push(msg);
        });
        return groups;
    }

    /**
     * Format date for display
     */
    formatDate(timestamp) {
        const date = new Date(timestamp);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        if (date.toDateString() === today.toDateString()) {
            return 'Today';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        } else {
            return date.toLocaleDateString();
        }
    }

    /**
     * Format time for display
     */
    formatTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Search talks
     */
    searchTalks(event) {
        if (!this.hasTalkListTarget) return;

        const query = event.target.value.toLowerCase();
        this.talkListTarget.querySelectorAll('.talk-item').forEach(item => {
            const subject = item.querySelector('.talk-subject').textContent.toLowerCase();
            const preview = item.querySelector('.talk-preview').textContent.toLowerCase();
            const matches = subject.includes(query) || preview.includes(query);
            item.style.display = matches ? '' : 'none';
        });
    }

    /**
     * Filter talks by type (all organization or user only)
     */
    filterTalks(event) {
        const filterBtn = event.currentTarget;
        const filter = filterBtn.dataset.filter;

        // Update active button
        document.querySelectorAll('.chat-filter-bar .filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        filterBtn.classList.add('active');

        // Update filter and reload talks
        this.currentFilter = filter;
        this.loadTalks();
    }

    /**
     * Refresh messages manually
     */
    async refreshMessages() {
        if (this.currentTalkId) {
            await this.loadMessages(this.currentTalkId);
        }
    }

    /**
     * Toggle info panel (Phase 4)
     */
    async toggleInfo() {
        if (!this.currentTalkId) return;

        // Check if info panel exists
        const existingPanel = document.querySelector('.talk-info-panel');
        if (existingPanel) {
            existingPanel.remove();
            return;
        }

        // Fetch talk details
        try {
            const response = await fetch(`/chat/api/talk/${this.currentTalkId}/messages`, {
                credentials: 'same-origin'
            });
            const data = await response.json();
            const talk = data.talk;

            // Create info panel
            const panel = document.createElement('div');
            panel.className = 'talk-info-panel';
            panel.innerHTML = `
                <div class="talk-info-header">
                    <h6 class="mb-0">Talk Information</h6>
                    <button type="button" class="btn-close" onclick="this.closest('.talk-info-panel').remove()"></button>
                </div>
                <div class="talk-info-body">
                    <div class="talk-info-item">
                        <i class="bi bi-chat-dots me-2"></i>
                        <div>
                            <strong>Subject</strong>
                            <p>${this.escapeHtml(talk.subject)}</p>
                        </div>
                    </div>
                    <div class="talk-info-item">
                        <i class="bi bi-people me-2"></i>
                        <div>
                            <strong>Participants</strong>
                            <p>${this.escapeHtml(talk.participants)}</p>
                        </div>
                    </div>
                    <div class="talk-info-item">
                        <i class="bi bi-hash me-2"></i>
                        <div>
                            <strong>Talk ID</strong>
                            <p class="small text-muted">${talk.id}</p>
                        </div>
                    </div>
                </div>
            `;

            // Add to chat main
            const chatMain = this.element.querySelector('.chat-main');
            if (chatMain) {
                chatMain.appendChild(panel);
            }
        } catch (error) {
            console.error('Failed to load talk info:', error);
            this.showError('Failed to load talk information');
        }
    }

    /**
     * Toggle sidebar visibility on mobile
     */
    toggleSidebar() {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.classList.toggle('show');
        }
    }

    /**
     * Open file picker
     */
    attachFile() {
        if (this.hasFileInputTarget) {
            this.fileInputTarget.click();
        }
    }

    /**
     * Handle file selection (Phase 3 - Implemented)
     */
    handleFileSelect(event) {
        const files = Array.from(event.target.files);

        if (files.length === 0) return;

        // Validate files
        const maxSize = 10 * 1024 * 1024; // 10MB
        const invalidFiles = files.filter(f => f.size > maxSize);

        if (invalidFiles.length > 0) {
            this.showError(`Some files are too large (max 10MB): ${invalidFiles.map(f => f.name).join(', ')}`);
            return;
        }

        // Add to selected files
        this.selectedFiles = [...this.selectedFiles, ...files];

        // Show preview
        this.showFilePreview();

        // Clear file input for future selections
        event.target.value = '';
    }

    /**
     * Show file preview UI (Phase 3)
     */
    showFilePreview() {
        if (this.selectedFiles.length === 0) {
            this.hideFilePreview();
            return;
        }

        // Remove existing preview
        const existing = this.element.querySelector('.file-preview-container');
        if (existing) existing.remove();

        // Create preview container
        const container = document.createElement('div');
        container.className = 'file-preview-container';

        container.innerHTML = `
            <div class="file-preview-header">
                <span class="file-preview-title">
                    <i class="bi bi-paperclip me-2"></i>
                    ${this.selectedFiles.length} file(s) attached
                </span>
                <button type="button" class="btn btn-sm btn-link text-danger" data-action="click->chat#clearSelectedFiles">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="file-preview-list">
                ${this.selectedFiles.map((file, index) => `
                    <div class="file-preview-item" data-file-index="${index}">
                        ${this.isImageFile(file) ? `
                            <img src="${URL.createObjectURL(file)}" alt="${file.name}" class="file-preview-thumb">
                        ` : `
                            <div class="file-preview-icon">
                                <i class="${this.getFileIcon(file)}"></i>
                            </div>
                        `}
                        <div class="file-preview-info">
                            <div class="file-preview-name">${this.escapeHtml(file.name)}</div>
                            <div class="file-preview-size">${this.formatFileSize(file.size)}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger"
                                data-action="click->chat#removeFile"
                                data-index="${index}">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
        `;

        // Insert before composer
        if (this.hasComposerTarget) {
            this.composerTarget.insertAdjacentElement('beforebegin', container);
        }
    }

    /**
     * Hide file preview (Phase 3)
     */
    hideFilePreview() {
        const preview = this.element.querySelector('.file-preview-container');
        if (preview) preview.remove();
    }

    /**
     * Clear all selected files (Phase 3)
     */
    clearSelectedFiles() {
        this.selectedFiles = [];
        this.hideFilePreview();

        // Clear file input
        if (this.hasFileInputTarget) {
            this.fileInputTarget.value = '';
        }
    }

    /**
     * Remove single file from selection (Phase 3)
     */
    removeFile(event) {
        const index = parseInt(event.currentTarget.dataset.index);
        this.selectedFiles.splice(index, 1);
        this.showFilePreview();
    }

    /**
     * Setup drag-and-drop for file upload (Phase 3)
     */
    setupDragAndDrop() {
        if (!this.hasMessageThreadTarget) return;

        const dropZone = this.messageThreadTarget;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('drag-over');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('drag-over');
            }, false);
        });

        // Handle dropped files
        dropZone.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer.files);

            if (files.length > 0) {
                // Add files to selection
                this.selectedFiles = [...this.selectedFiles, ...files];
                this.showFilePreview();
                this.showToast(`${files.length} file(s) added`);

                // Focus on message input
                if (this.hasMessageInputTarget) {
                    this.messageInputTarget.focus();
                }
            }
        }, false);
    }

    /**
     * Check if file is an image (Phase 3)
     */
    isImageFile(file) {
        return file.type.startsWith('image/');
    }

    /**
     * Get file icon based on type (Phase 3)
     */
    getFileIcon(file) {
        const type = file.type;

        if (type.includes('pdf')) return 'bi bi-file-pdf-fill text-danger';
        if (type.includes('word') || type.includes('document')) return 'bi bi-file-word-fill text-primary';
        if (type.includes('excel') || type.includes('spreadsheet')) return 'bi bi-file-excel-fill text-success';
        if (type.includes('powerpoint') || type.includes('presentation')) return 'bi bi-file-ppt-fill text-warning';
        if (type.includes('zip') || type.includes('compressed')) return 'bi bi-file-zip-fill text-secondary';
        if (type.includes('text')) return 'bi bi-file-text-fill';
        if (type.includes('video')) return 'bi bi-file-play-fill text-info';
        if (type.includes('audio')) return 'bi bi-file-music-fill text-purple';

        return 'bi bi-file-earmark-fill';
    }

    /**
     * Format file size (Phase 3)
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';

        const units = ['B', 'KB', 'MB', 'GB'];
        const k = 1024;
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + units[i];
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showToast(message, 'danger');
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'primary') {
        if (!this.hasToastTarget || !this.hasToastBodyTarget) return;

        // Update message
        this.toastBodyTarget.textContent = message;

        // Update style
        this.toastTarget.className = `toast align-items-center text-white border-0 bg-${type}`;

        // Show toast
        const toast = new window.bootstrap.Toast(this.toastTarget);
        toast.show();
    }

    // =========================================================
    // PHASE 2: REAL-TIME ENHANCEMENTS
    // =========================================================

    /**
     * Setup visibility change detection
     * Pauses polling when tab is hidden, resumes when visible
     */
    setupVisibilityDetection() {
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;

            if (this.isVisible) {
                // Refresh talk list when tab becomes visible
                if (this.currentTalkId) {
                    this.loadTalks();
                }
                // Clear unread title notification
                this.updateBrowserTitle(0);
            }
        });
    }

    /**
     * Setup scroll detection for "new messages" banner
     */
    setupScrollDetection() {
        if (!this.hasMessageThreadTarget) return;

        this.messageThreadTarget.addEventListener('scroll', () => {
            const element = this.messageThreadTarget;
            const threshold = 100; // pixels from bottom

            this.isAtBottom = (element.scrollHeight - element.scrollTop - element.clientHeight) < threshold;

            // Hide banner if scrolled to bottom
            if (this.isAtBottom && this.hasNewMessages) {
                this.hideNewMessagesBanner();
            }
        });
    }

    /**
     * Setup connection status monitor
     */
    setupConnectionMonitor() {
        this.isOnline = navigator.onLine;

        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateConnectionStatus('online');
            // Refresh talk list when connection restored (Mercure will auto-reconnect)
            if (this.currentTalkId) {
                this.loadTalks();
            }
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateConnectionStatus('offline');
        });

        // Note: Connection quality monitoring is now handled by Mercure EventSource
        // The EventSource will automatically detect disconnections and attempt to reconnect
    }

    /**
     * Update connection status indicator
     */
    updateConnectionStatus(status) {
        // Remove existing indicator
        const existing = document.querySelector('.connection-status');
        if (existing) existing.remove();

        if (status === 'online') {
            // Don't show anything when online
            return;
        }

        // Create status indicator
        const indicator = document.createElement('div');
        indicator.className = `connection-status connection-${status}`;

        if (status === 'offline') {
            indicator.innerHTML = '<i class="bi bi-wifi-off me-2"></i>No connection';
        } else if (status === 'poor') {
            indicator.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Poor connection';
        }

        // Insert at top of chat
        const chatHeader = this.hasChatHeaderTarget ? this.chatHeaderTarget : this.element;
        chatHeader.insertAdjacentElement('beforebegin', indicator);
    }

    /**
     * Update browser title with unread count
     */
    updateBrowserTitle(unreadCount) {
        if (unreadCount > 0 && !this.isVisible) {
            document.title = `(${unreadCount}) ${this.originalTitle}`;
        } else {
            document.title = this.originalTitle;
        }
    }

    /**
     * Show new messages banner
     */
    showNewMessagesBanner(count) {
        if (this.isAtBottom) return; // Don't show if at bottom

        // Remove existing banner
        this.hideNewMessagesBanner();

        const banner = document.createElement('div');
        banner.className = 'new-messages-banner';
        banner.innerHTML = `
            <i class="bi bi-arrow-down-circle me-2"></i>
            ${count} new message${count > 1 ? 's' : ''}
        `;
        banner.addEventListener('click', () => {
            this.scrollToBottom();
            this.hideNewMessagesBanner();
        });

        if (this.hasMessageThreadTarget) {
            this.messageThreadTarget.appendChild(banner);
        }

        this.hasNewMessages = true;
    }

    /**
     * Hide new messages banner
     */
    hideNewMessagesBanner() {
        const banner = document.querySelector('.new-messages-banner');
        if (banner) {
            banner.remove();
        }
        this.hasNewMessages = false;
    }

    /**
     * Play notification sound
     */
    playNotificationSound() {
        if (!this.soundEnabled || this.isVisible) return;

        // Create simple notification beep using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (error) {
            console.warn('Failed to play notification sound:', error);
        }
    }

    /**
     * Show desktop notification (if permission granted)
     */
    showDesktopNotification(message) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        if (this.isVisible) return; // Don't show if tab is visible

        const notification = new Notification('New message', {
            body: message.body.substring(0, 100),
            icon: '/favicon.ico',
            tag: 'chat-message',
            requireInteraction: false
        });

        notification.onclick = () => {
            window.focus();
            this.scrollToBottom();
            notification.close();
        };

        // Auto-close after 5 seconds
        setTimeout(() => notification.close(), 5000);
    }

    /**
     * Request notification permission
     */
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('Notifications not supported');
            return false;
        }

        if (Notification.permission === 'granted') {
            return true;
        }

        if (Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            return permission === 'granted';
        }

        return false;
    }

    /**
     * Toggle desktop notifications
     */
    async toggleNotifications() {
        const granted = await this.requestNotificationPermission();

        if (granted) {
            this.showToast('Desktop notifications enabled', 'success');
            this.updateNotificationToggleButton(true);
        } else {
            this.showToast('Desktop notifications denied or not supported', 'warning');
            this.updateNotificationToggleButton(false);
        }
    }

    /**
     * Update notification toggle button icon
     */
    updateNotificationToggleButton(enabled) {
        if (!this.hasNotificationToggleTarget) return;

        const icon = this.notificationToggleTarget.querySelector('i');
        if (icon) {
            icon.className = enabled ? 'bi bi-bell-fill' : 'bi bi-bell';
        }

        this.notificationToggleTarget.title = enabled
            ? 'Desktop notifications enabled'
            : 'Enable desktop notifications';
    }

    /**
     * Check notification permission on connect
     */
    checkNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'granted') {
            this.updateNotificationToggleButton(true);
        }
    }

    // =========================================================
    // PHASE 4: ADVANCED FEATURES
    // =========================================================

    /**
     * Broadcast typing status (Phase 4)
     */
    broadcastTypingStatus(isTyping) {
        if (!this.currentTalkId) return;

        // Clear existing timeout
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        // Only send if status changed
        if (isTyping && !this.isTyping) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }

        // Auto-stop typing after 3 seconds of inactivity
        this.typingTimeout = setTimeout(() => {
            if (this.isTyping) {
                this.isTyping = false;
                this.sendTypingStatus(false);
            }
        }, 3000);
    }

    /**
     * Send typing status to server (Phase 4)
     */
    async sendTypingStatus(isTyping) {
        if (!this.currentTalkId) return;

        try {
            await fetch(`/chat/api/talk/${this.currentTalkId}/typing`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ isTyping })
            });
        } catch (error) {
            console.error('Failed to send typing status:', error);
        }
    }

    /**
     * Display typing indicator (Phase 4)
     */
    showTypingIndicator(users) {
        if (!this.hasMessageThreadTarget || !users || users.length === 0) {
            this.hideTypingIndicator();
            return;
        }

        // Remove existing indicator
        this.hideTypingIndicator();

        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.innerHTML = `
            <div class="message message-other">
                <div class="message-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="message-bubble">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;

        this.messageThreadTarget.appendChild(indicator);

        // Auto-scroll if at bottom
        if (this.isAtBottom) {
            this.scrollToBottom();
        }
    }

    /**
     * Hide typing indicator (Phase 4)
     */
    hideTypingIndicator() {
        const indicator = this.element.querySelector('.typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Open emoji picker (Phase 4 - Implemented)
     */
    openEmoji() {
        if (!this.hasMessageInputTarget) return;

        // Toggle: if popup exists, close it
        const existingPopup = this.element.querySelector('.emoji-popup');
        if (existingPopup) {
            this.closeEmojiPopup();
        } else {
            this.showEmojiPopup();
        }
    }

    /**
     * Show emoji popup (Phase 4)
     */
    showEmojiPopup() {
        // Common emojis
        const emojis = [
            '😀', '😂', '😍', '🥰', '😊', '😎', '🤔', '😮', '😢', '😡',
            '👍', '👎', '👏', '🙏', '💪', '✌️', '🤝', '❤️', '💔', '🔥',
            '⭐', '✨', '🎉', '🎊', '🎈', '🎁', '🏆', '💯', '✅', '❌',
            '📎', '📁', '📄', '📊', '📈', '💼', '📧', '📱', '💻', '⌨️'
        ];

        // Create popup
        const popup = document.createElement('div');
        popup.className = 'emoji-popup';
        popup.innerHTML = `
            <div class="emoji-popup-header">
                <span>Select Emoji</span>
                <button type="button" class="btn-close" data-action="click->chat#closeEmojiPopup"></button>
            </div>
            <div class="emoji-grid">
                ${emojis.map(emoji => `
                    <button type="button" class="emoji-item" data-emoji="${emoji}" data-action="click->chat#insertEmoji">
                        ${emoji}
                    </button>
                `).join('')}
            </div>
        `;

        // Remove existing popup
        this.closeEmojiPopup();

        // Add to DOM
        if (this.hasComposerTarget) {
            this.composerTarget.appendChild(popup);
        }
    }

    /**
     * Insert emoji at cursor (Phase 4)
     */
    insertEmoji(event) {
        const emoji = event.currentTarget.dataset.emoji;
        if (!emoji || !this.hasMessageInputTarget) return;

        const textarea = this.messageInputTarget;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        // Insert emoji at cursor position
        textarea.value = text.substring(0, start) + emoji + text.substring(end);

        // Move cursor after emoji
        textarea.selectionStart = textarea.selectionEnd = start + emoji.length;

        // Auto-resize and focus
        this.autoResizeTextarea();
        textarea.focus();

        // Close popup
        this.closeEmojiPopup();
    }

    /**
     * Close emoji popup (Phase 4)
     */
    closeEmojiPopup() {
        const popup = this.element.querySelector('.emoji-popup');
        if (popup) {
            popup.remove();
        }
    }

    /**
     * Toggle message search (Phase 4)
     */
    toggleMessageSearch() {
        const searchContainer = this.element.querySelector('.message-search-container');

        if (searchContainer) {
            searchContainer.remove();
        } else {
            this.showMessageSearch();
        }
    }

    /**
     * Show message search UI (Phase 4)
     */
    showMessageSearch() {
        // Create search container
        const container = document.createElement('div');
        container.className = 'message-search-container';
        container.innerHTML = `
            <div class="message-search-header">
                <input type="search"
                       class="form-control form-control-sm"
                       placeholder="Search messages..."
                       data-chat-target="searchInput"
                       data-action="input->chat#handleSearchInput">
                <button type="button" class="btn btn-sm btn-link" data-action="click->chat#toggleMessageSearch">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="message-search-results" data-chat-target="searchResults">
                <p class="text-muted text-center p-3">Type to search messages...</p>
            </div>
        `;

        // Insert at top of chat
        if (this.hasChatHeaderTarget) {
            this.chatHeaderTarget.insertAdjacentElement('afterend', container);
        }

        // Focus search input
        const searchInput = container.querySelector('input');
        if (searchInput) {
            searchInput.focus();
        }
    }

    /**
     * Handle search input (Phase 4)
     */
    async handleSearchInput(event) {
        const query = event.target.value.trim();

        if (query.length < 2) {
            const resultsContainer = this.element.querySelector('.message-search-results');
            if (resultsContainer) {
                resultsContainer.innerHTML = '<p class="text-muted text-center p-3">Type at least 2 characters...</p>';
            }
            return;
        }

        // Search in current messages (client-side)
        await this.searchMessages(query);
    }

    /**
     * Search messages (Phase 4)
     */
    async searchMessages(query) {
        if (!this.currentTalkId) return;

        const resultsContainer = this.element.querySelector('.message-search-results');
        if (!resultsContainer) return;

        try {
            // For now, search client-side in loaded messages
            const messages = Array.from(this.messageThreadTarget.querySelectorAll('.message'));
            const results = [];

            messages.forEach(messageEl => {
                const body = messageEl.querySelector('.message-body');
                if (body && body.textContent.toLowerCase().includes(query.toLowerCase())) {
                    const messageId = messageEl.dataset.messageId;
                    const text = body.textContent;
                    const time = messageEl.querySelector('.message-time')?.textContent || '';

                    results.push({ messageId, text, time, element: messageEl });
                }
            });

            if (results.length === 0) {
                resultsContainer.innerHTML = `
                    <p class="text-muted text-center p-3">
                        <i class="bi bi-search"></i><br>
                        No messages found for "${this.escapeHtml(query)}"
                    </p>
                `;
            } else {
                resultsContainer.innerHTML = `
                    <div class="search-result-count p-2 bg-dark">
                        Found ${results.length} message${results.length > 1 ? 's' : ''}
                    </div>
                    ${results.map(result => `
                        <div class="search-result-item" data-message-id="${result.messageId}" data-action="click->chat#scrollToMessage">
                            <div class="search-result-text">${this.highlightText(result.text, query)}</div>
                            <div class="search-result-time">${result.time}</div>
                        </div>
                    `).join('')}
                `;
            }
        } catch (error) {
            console.error('Search failed:', error);
            resultsContainer.innerHTML = '<p class="text-danger text-center p-3">Search failed</p>';
        }
    }

    /**
     * Highlight search query in text (Phase 4)
     */
    highlightText(text, query) {
        const regex = new RegExp(`(${this.escapeRegex(query)})`, 'gi');
        return this.escapeHtml(text).replace(regex, '<mark>$1</mark>');
    }

    /**
     * Escape regex special characters (Phase 4)
     */
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Scroll to specific message (Phase 4)
     */
    scrollToMessage(event) {
        const messageId = event.currentTarget.dataset.messageId;
        const messageEl = this.messageThreadTarget.querySelector(`[data-message-id="${messageId}"]`);

        if (messageEl) {
            messageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Highlight briefly
            messageEl.classList.add('highlighted');
            setTimeout(() => {
                messageEl.classList.remove('highlighted');
            }, 2000);

            // Close search
            this.toggleMessageSearch();
        }
    }

    /**
     * Handle modal success event - refresh talk list when Talk is created
     */
    handleModalSuccess(event) {
        const { type, entityId } = event.detail;

        // If a Talk was created, refresh the talk list
        if (type === 'talk' || type === 'entity') {
            this.loadTalks();

            // If we have an entityId, we could also select that talk
            if (entityId) {
                setTimeout(() => {
                    this.currentTalkId = entityId;
                    this.loadMessages(entityId);
                    this.subscribeToMercure(entityId);
                    this.showEmptyState(false);
                    this.showChatInterface(true);
                }, 500);
            }
        }
    }
}

@extends('layouts.app')

@section('title', 'AI Career Assistant')

@section('content')

<div class="container-fluid py-4">

    <div class="row justify-content-center">

        <div class="col-12 col-xl-10">

            <div class="card border-0 shadow-sm overflow-hidden">

                {{-- Header --}}
                <div class="card-header bg-white border-bottom py-3">

                    <div class="d-flex align-items-center justify-content-between">

                        <div class="d-flex align-items-center">

                            <div
                                class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3"
                                style="width:48px;height:48px;">
                                <i class="bi bi-stars fs-4"></i>
                            </div>

                            <div>
                                <h4 class="mb-0 fw-bold">
                                    AI Career Assistant
                                </h4>

                                <small class="text-muted">
                                    Jobs • Career • Resume • Interview • Learning • Industry
                                </small>
                            </div>

                        </div>

                        <div>

                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm"
                                id="newConversationBtn">
                                <i class="bi bi-plus-lg me-1"></i>
                                New Chat
                            </button>

                        </div>

                    </div>

                </div>


                <div class="row g-0">

                    {{-- Conversation Sidebar --}}
                    <div
                        class="col-lg-3 border-end"
                        id="conversationSidebar">

                        <div class="p-3 border-bottom">

                            <div class="small fw-semibold text-muted mb-2">
                                AI AGENTS
                            </div>

                            <div
                                class="d-flex flex-wrap gap-2"
                                id="agentList">

                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary agent-button"
                                    data-agent="career">
                                    Career
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="job">
                                    Jobs
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="resume">
                                    Resume
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="interview">
                                    Interview
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="learning">
                                    Learning
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="industrial">
                                    Industrial
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="company">
                                    Company
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="news">
                                    News
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary agent-button"
                                    data-agent="hr">
                                    HR
                                </button>

                            </div>

                        </div>


                        <div class="p-3">

                            <div class="small fw-semibold text-muted mb-2">
                                RECENT CONVERSATIONS
                            </div>

                            <div
                                id="conversationList"
                                class="small text-muted">
                                Login to see your conversations.
                            </div>

                        </div>

                    </div>


                    {{-- Chat Area --}}
                    <div class="col-lg-9">

                        <div
                            id="chatMessages"
                            class="p-3 p-lg-4"
                            style="
                                height:520px;
                                overflow-y:auto;
                                background:#f8f9fa;
                            ">

                            {{-- Welcome --}}
                            <div
                                id="welcomeMessage"
                                class="text-center py-5">

                                <div
                                    class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width:72px;height:72px;">
                                    <i class="bi bi-stars fs-1"></i>
                                </div>

                                <h3 class="fw-bold">
                                    How can I help your career?
                                </h3>

                                <p class="text-muted mb-4">
                                    Ask about jobs, skills, resumes,
                                    interviews, companies, industrial careers
                                    or career growth.
                                </p>


                                <div class="row g-2 justify-content-center">

                                    <div class="col-12 col-md-6 col-xl-4">

                                        <button
                                            type="button"
                                            class="btn btn-light border w-100 suggestion-button"
                                            data-message="Find Java Spring Boot jobs for me">
                                            <i class="bi bi-briefcase me-2"></i>
                                            Find Java jobs
                                        </button>

                                    </div>

                                    <div class="col-12 col-md-6 col-xl-4">

                                        <button
                                            type="button"
                                            class="btn btn-light border w-100 suggestion-button"
                                            data-message="What skills should I learn to become a Spring Boot developer?">
                                            <i class="bi bi-mortarboard me-2"></i>
                                            Skills to learn
                                        </button>

                                    </div>

                                    <div class="col-12 col-md-6 col-xl-4">

                                        <button
                                            type="button"
                                            class="btn btn-light border w-100 suggestion-button"
                                            data-message="Help me prepare for a Java interview">
                                            <i class="bi bi-person-workspace me-2"></i>
                                            Interview preparation
                                        </button>

                                    </div>

                                    <div class="col-12 col-md-6 col-xl-4">

                                        <button
                                            type="button"
                                            class="btn btn-light border w-100 suggestion-button"
                                            data-message="Find manufacturing and mechanical jobs in Punjab">
                                            <i class="bi bi-building me-2"></i>
                                            Industrial jobs
                                        </button>

                                    </div>

                                    <div class="col-12 col-md-6 col-xl-4">

                                        <button
                                            type="button"
                                            class="btn btn-light border w-100 suggestion-button"
                                            data-message="Review my resume and tell me what I should improve">
                                            <i class="bi bi-file-earmark-text me-2"></i>
                                            Resume review
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- Typing indicator --}}
                        <div
                            id="typingIndicator"
                            class="px-4 pb-2 d-none">

                            <div class="d-inline-flex align-items-center text-muted small">

                                <span class="spinner-grow spinner-grow-sm me-2"></span>

                                AI is thinking...

                            </div>

                        </div>


                        {{-- Input --}}
                        <div class="border-top bg-white p-3">

                            <form id="aiChatForm">

                                <div class="input-group">

                                    <textarea
                                        id="aiMessageInput"
                                        class="form-control"
                                        rows="2"
                                        maxlength="10000"
                                        placeholder="Ask me about jobs, career, skills, resume, interview preparation..."
                                        autocomplete="off"></textarea>

                                    <button
                                        type="submit"
                                        class="btn btn-primary px-4"
                                        id="sendMessageBtn">
                                        <i class="bi bi-send-fill me-1"></i>
                                        Send
                                    </button>

                                </div>

                                <div class="d-flex justify-content-between mt-2">

                                    <small class="text-muted">
                                        AI responses are based on available platform data.
                                    </small>

                                    <small
                                        class="text-muted"
                                        id="messageCounter">
                                        0 / 10000
                                    </small>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- Message template --}}
<template id="userMessageTemplate">

    <div class="d-flex justify-content-end mb-3">

        <div
            class="bg-primary text-white rounded-3 px-3 py-2"
            style="max-width:80%;">

            <div class="message-content"></div>

        </div>

    </div>

</template>


<template id="assistantMessageTemplate">

    <div class="d-flex justify-content-start mb-3">

        <div
            class="bg-white border rounded-3 px-3 py-3 shadow-sm"
            style="max-width:85%;">

            <div class="d-flex align-items-center mb-2">

                <div
                    class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2"
                    style="width:32px;height:32px;">
                    <i class="bi bi-stars"></i>
                </div>

                <strong class="small">
                    AI Assistant
                </strong>

            </div>

            <div class="message-content"></div>

        </div>

    </div>

</template>


<script>
    document.addEventListener('DOMContentLoaded', function() {

        const chatForm =
            document.getElementById('aiChatForm');

        const messageInput =
            document.getElementById('aiMessageInput');

        const sendButton =
            document.getElementById('sendMessageBtn');

        const messagesContainer =
            document.getElementById('chatMessages');

        const typingIndicator =
            document.getElementById('typingIndicator');

        const welcomeMessage =
            document.getElementById('welcomeMessage');

        const newConversationButton =
            document.getElementById('newConversationBtn');

        const conversationList =
            document.getElementById('conversationList');

        const messageCounter =
            document.getElementById('messageCounter');

        let conversationId = null;

        let selectedAgent = 'career';


        /*
         |--------------------------------------------------------------------------
         | CSRF
         |--------------------------------------------------------------------------
         */

        function csrfToken() {

            const meta =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );

            return meta ?
                meta.getAttribute('content') :
                '';

        }


        /*
         |--------------------------------------------------------------------------
         | API helper
         |--------------------------------------------------------------------------
         */

        async function apiRequest(
            url,
            options = {}
        ) {

            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(
                    options.headers || {}
                ),
            };

            const token =
                csrfToken();

            if (token) {
                headers['X-CSRF-TOKEN'] =
                    token;
            }

            const response =
                await fetch(
                    url, {
                        ...options,
                        headers,
                    }
                );

            let data = {};

            try {
                data =
                    await response.json();
            } catch (error) {
                data = {};
            }

            if (!response.ok) {

                const error =
                    new Error(
                        data.message ||
                        'Request failed.'
                    );

                error.status =
                    response.status;

                error.data =
                    data;

                throw error;
            }

            return data;

        }


        /*
         |--------------------------------------------------------------------------
         | Escape HTML
         |--------------------------------------------------------------------------
         */

        function escapeHtml(value) {

            const div =
                document.createElement('div');

            div.textContent =
                value ?? '';

            return div.innerHTML;

        }


        /*
         |--------------------------------------------------------------------------
         | Simple AI text formatting
         |--------------------------------------------------------------------------
         */

        function formatAssistantText(text) {

            let html =
                escapeHtml(text);

            html =
                html.replace(
                    /\*\*(.*?)\*\*/g,
                    '<strong>$1</strong>'
                );

            html =
                html.replace(
                    /\n/g,
                    '<br>'
                );

            return html;

        }


        /*
         |--------------------------------------------------------------------------
         | Add user message
         |--------------------------------------------------------------------------
         */

        function addUserMessage(
            message
        ) {

            const template =
                document.getElementById(
                    'userMessageTemplate'
                );

            const clone =
                template.content.cloneNode(
                    true
                );

            clone.querySelector(
                    '.message-content'
                ).textContent =
                message;

            messagesContainer.appendChild(
                clone
            );

            scrollToBottom();

        }


        /*
         |--------------------------------------------------------------------------
         | Add assistant message
         |--------------------------------------------------------------------------
         */

        function addAssistantMessage(
            message
        ) {

            const template =
                document.getElementById(
                    'assistantMessageTemplate'
                );

            const clone =
                template.content.cloneNode(
                    true
                );

            clone.querySelector(
                    '.message-content'
                ).innerHTML =
                formatAssistantText(
                    message
                );

            messagesContainer.appendChild(
                clone
            );

            scrollToBottom();

        }


        /*
         |--------------------------------------------------------------------------
         | Scroll chat
         |--------------------------------------------------------------------------
         */

        function scrollToBottom() {

            messagesContainer.scrollTop =
                messagesContainer.scrollHeight;

        }


        /*
         |--------------------------------------------------------------------------
         | Loading state
         |--------------------------------------------------------------------------
         */

        function setLoading(
            loading
        ) {

            if (loading) {

                typingIndicator.classList
                    .remove('d-none');

                sendButton.disabled =
                    true;

                messageInput.disabled =
                    true;

            } else {

                typingIndicator.classList
                    .add('d-none');

                sendButton.disabled =
                    false;

                messageInput.disabled =
                    false;

                messageInput.focus();

            }

        }


        /*
         |--------------------------------------------------------------------------
         | Select agent
         |--------------------------------------------------------------------------
         */

        function selectAgent(
            agent
        ) {

            selectedAgent =
                agent;

            document
                .querySelectorAll(
                    '.agent-button'
                )
                .forEach(
                    function(button) {

                        const active =
                            button.dataset.agent ===
                            agent;

                        button.classList.toggle(
                            'btn-primary',
                            active
                        );

                        button.classList.toggle(
                            'btn-outline-secondary',
                            !active
                        );

                    }
                );

        }


        /*
         |--------------------------------------------------------------------------
         | Create conversation
         |--------------------------------------------------------------------------
         */

        async function createConversation() {

            try {

                const data =
                    await apiRequest(
                        '/api/ai/conversations', {
                            method: 'POST',
                            body: JSON.stringify({
                                agent: selectedAgent,
                            }),
                        }
                    );

                conversationId =
                    data.conversation_id ??
                    null;

                return conversationId;

            } catch (error) {

                /*
                 * The chat endpoint can create a conversation
                 * automatically, so failure here does not prevent
                 * anonymous chat from working.
                 */

                conversationId =
                    null;

                return null;

            }

        }


        /*
         |--------------------------------------------------------------------------
         | Send message
         |--------------------------------------------------------------------------
         */

        async function sendMessage(
            message
        ) {

            message =
                String(message || '')
                .trim();

            if (!message) {
                return;
            }

            if (welcomeMessage) {
                welcomeMessage.remove();
            }

            addUserMessage(
                message
            );

            messageInput.value =
                '';

            updateCounter();

            setLoading(true);

            try {

                const payload = {
                    message: message,

                    agent: selectedAgent,

                    context: {},

                    metadata: {},
                };


                if (conversationId !== null) {

                    payload.conversation_id =
                        conversationId;

                }


                const data =
                    await apiRequest(
                        '/api/ai/chat', {
                            method: 'POST',
                            body: JSON.stringify(
                                payload
                            ),
                        }
                    );


                /*
                 * The router/controller returns the
                 * conversation ID after processing.
                 */

                if (
                    data.conversation_id
                ) {

                    conversationId =
                        Number(
                            data.conversation_id
                        );

                } else if (
                    data.metadata &&
                    data.metadata.conversation_id
                ) {

                    conversationId =
                        Number(
                            data.metadata
                            .conversation_id
                        );

                }


                if (
                    data.success &&
                    data.message
                ) {

                    addAssistantMessage(
                        data.message
                    );

                } else {

                    addAssistantMessage(
                        data.message ||
                        'The AI assistant could not generate a response.'
                    );

                }

            } catch (error) {

                let errorMessage =
                    'Unable to contact the AI assistant right now.';

                if (
                    error &&
                    error.data &&
                    error.data.message
                ) {

                    errorMessage =
                        error.data.message;

                }

                addAssistantMessage(
                    errorMessage
                );

            } finally {

                setLoading(false);

            }

        }


        /*
         |--------------------------------------------------------------------------
         | Load conversation
         |--------------------------------------------------------------------------
         */

        async function loadConversation(
            id
        ) {

            try {

                const data =
                    await apiRequest(
                        '/api/ai/conversations/' +
                        encodeURIComponent(id)
                    );

                const conversation =
                    data.conversation;

                if (!conversation) {
                    return;
                }

                conversationId =
                    Number(id);

                messagesContainer.innerHTML =
                    '';

                const messages =
                    Array.isArray(
                        conversation.messages
                    ) ?
                    conversation.messages :
                    [];

                if (
                    messages.length === 0
                ) {

                    showWelcome();

                    return;

                }

                messages.forEach(
                    function(message) {

                        const role =
                            message.role;

                        const content =
                            message.content ??
                            message.message ??
                            '';

                        if (!content) {
                            return;
                        }

                        if (
                            role === 'user'
                        ) {

                            addUserMessage(
                                content
                            );

                        } else if (
                            role === 'assistant'
                        ) {

                            addAssistantMessage(
                                content
                            );

                        }

                    }
                );

                scrollToBottom();

            } catch (error) {

                console.error(
                    'Conversation loading failed.',
                    error
                );

            }

        }


        /*
         |--------------------------------------------------------------------------
         | Load user's conversations
         |--------------------------------------------------------------------------
         */

        async function loadConversations() {

            try {

                const data =
                    await apiRequest(
                        '/api/ai/conversations?limit=20'
                    );

                const conversations =
                    Array.isArray(
                        data.conversations
                    ) ?
                    data.conversations :
                    [];

                if (
                    conversations.length === 0
                ) {

                    conversationList.innerHTML =
                        '<div class="text-muted">No conversations yet.</div>';

                    return;

                }

                conversationList.innerHTML =
                    '';

                conversations.forEach(
                    function(conversation) {

                        const button =
                            document.createElement(
                                'button'
                            );

                        button.type =
                            'button';

                        button.className =
                            'btn btn-light w-100 text-start mb-2 border';

                        const title =
                            conversation.title ||
                            (
                                conversation.agent ?
                                conversation.agent
                                .charAt(0)
                                .toUpperCase() +
                                conversation.agent
                                .slice(1) :
                                'AI Conversation'
                            );

                        button.innerHTML =
                            `
                        <div class="fw-semibold">
                            ${escapeHtml(title)}
                        </div>
                        <small class="text-muted">
                            ${escapeHtml(
                                conversation.status ||
                                'active'
                            )}
                        </small>
                        `;

                        button.addEventListener(
                            'click',
                            function() {

                                loadConversation(
                                    conversation.id
                                );

                            }
                        );

                        conversationList.appendChild(
                            button
                        );

                    }
                );

            } catch (error) {

                /*
                 * Anonymous users will receive 401 here.
                 * That is expected because the conversation list
                 * belongs to authenticated users.
                 */

                conversationList.innerHTML =
                    '<div class="text-muted">Login to see your conversations.</div>';

            }

        }


        /*
         |--------------------------------------------------------------------------
         | Welcome screen
         |--------------------------------------------------------------------------
         */

        function showWelcome() {

            messagesContainer.innerHTML =
                `
            <div
                id="welcomeMessage"
                class="text-center py-5"
            >
                <div
                    class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-3"
                    style="width:72px;height:72px;"
                >
                    <i class="bi bi-stars fs-1"></i>
                </div>

                <h3 class="fw-bold">
                    How can I help your career?
                </h3>

                <p class="text-muted">
                    Ask about jobs, skills, resumes,
                    interviews, companies, industrial careers
                    or career growth.
                </p>
            </div>
            `;

        }


        /*
         |--------------------------------------------------------------------------
         | New conversation
         |--------------------------------------------------------------------------
         */

        async function newConversation() {

            conversationId =
                null;

            messagesContainer.innerHTML =
                '';

            showWelcome();

            await createConversation();

            messageInput.focus();

        }


        /*
         |--------------------------------------------------------------------------
         | Counter
         |--------------------------------------------------------------------------
         */

        function updateCounter() {

            messageCounter.textContent =
                messageInput.value.length +
                ' / 10000';

        }


        /*
         |--------------------------------------------------------------------------
         | Events
         |--------------------------------------------------------------------------
         */

        chatForm.addEventListener(
            'submit',
            function(event) {

                event.preventDefault();

                sendMessage(
                    messageInput.value
                );

            }
        );


        messageInput.addEventListener(
            'input',
            updateCounter
        );


        messageInput.addEventListener(
            'keydown',
            function(event) {

                if (
                    event.key === 'Enter' &&
                    !event.shiftKey
                ) {

                    event.preventDefault();

                    chatForm.requestSubmit();

                }

            }
        );


        document
            .querySelectorAll(
                '.agent-button'
            )
            .forEach(
                function(button) {

                    button.addEventListener(
                        'click',
                        function() {

                            selectAgent(
                                button.dataset.agent
                            );

                        }
                    );

                }
            );


        document
            .querySelectorAll(
                '.suggestion-button'
            )
            .forEach(
                function(button) {

                    button.addEventListener(
                        'click',
                        function() {

                            sendMessage(
                                button.dataset.message
                            );

                        }
                    );

                }
            );


        newConversationButton
            .addEventListener(
                'click',
                newConversation
            );


        /*
         |--------------------------------------------------------------------------
         | Initialisation
         |--------------------------------------------------------------------------
         */

        selectAgent(
            'career'
        );

        updateCounter();

        loadConversations();

    });
</script>

@endsection
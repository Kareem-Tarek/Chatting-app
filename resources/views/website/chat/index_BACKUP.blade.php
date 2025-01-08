@extends('layouts.app')

@push('scripts')
<style>
    .user-item {
        cursor: pointer;
    }
    .user-item:hover {
        background-color: #f1f1f1;
    }
    .active-user {
        background-color: rgba(255, 137, 216, 0.196);
        font-weight: bold;
    }
    .unseen-count {
        font-size: 12px;
        padding: 3px 7px;
        border-radius: 12px;
    }
    .d-none {
        display: none;
    }
    #message-input:focus {
        outline: none;
        /*box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);*/  /* Light shadow */
        /*border-color: #80bdff;*/   /* Custom border color */
        box-shadow: 0 0 5px rgba(255, 0, 170, 0.25);
        border-color: #ff80bb;
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow">
                <div class="card-header fw-semibold h5">Users ({{ $users->count() }})</div>
                <ul class="list-group" id="user-list">
                    @foreach($users as $user)
                        <li class="list-group-item user-item"
                            data-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            id="user-{{ $user->id }}">
                                {{ $user->name }}
                                @php
                                    $countUnseenMsg = \App\Models\Message::where('sender_id', $user->id)
                                                    ->where('receiver_id', Auth::id())
                                                    ->where('seen', false)
                                                    ->count();
                                @endphp
                                @if($countUnseenMsg > 0)
                                    <span class="badge bg-secondary float-end unseen-count"
                                        id="unseen-{{ $user->id }}">
                                        {{ $countUnseenMsg }}
                                    </span>
                                @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-md-9 mt-5 mt-md-0">
            <div class="card shadow">
                <div class="card-header fw-semibold h5" id="chat-header">Select a User to Chat</div>
                <div class="card-body" id="messages" style="height: 400px; overflow-y: auto;">
                    <div class="text-center text-muted alert alert-info" role="alert">No messages yet.</div>
                </div>
                <div class="card-footer">
                    <div class="input-group">
                        <input type="text" id="message-input" class="form-control" placeholder="Type a message...">
                        <button class="btn btn-dark" style="background-color: rgb(154, 10, 106);" id="send" disabled><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<audio id="notification-sound" src="{{ asset('assets/sounds/popup-notification.wav') }}" preload="auto"></audio>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script>
let activeUser = null;
let activeUserName = '';
let seenMessages = {};  // Track seen messages to prevent looping checkmarks

$(document).ready(function () {
    let notificationSound = document.getElementById('notification-sound');

    // Enable audio on first interaction (important for autoplay policies)
    document.addEventListener('click', function () {
        notificationSound.play().then(() => {
            notificationSound.pause();
            notificationSound.currentTime = 0;
        }).catch(error => {
            console.log('Autoplay prevented. Waiting for user interaction.');
        });
    }, { once: true });

    // ----------------> Handle user selection
    $('#user-list').on('click', '.user-item', function () {
        $('.user-item').removeClass('active-user');
        $(this).addClass('active-user');

        activeUser = $(this).data('id');
        activeUserName = $(this).data('name');

        $("#unseen-" + activeUser).addClass('d-none');

        $('#chat-header').text(`Chatting with ${activeUserName}`);
        $('#send').prop('disabled', false);

        fetchMessages();
        markAsSeen();
    });

    // ----------------> Handle message sending
    $('#send').click(function () {
        let message = $('#message-input').val().trim();
        if (message === '' || activeUser === null) return;

        $.ajax({
            url: '/chat/send',
            method: 'POST',
            data: {
                message: message,
                receiver_id: activeUser,
                _token: '{{ csrf_token() }}'
            },
            success: function (data) {
                appendMessage(data, true, '<i class="fa-solid fa-check text-secondary"></i>');
                $('#message-input').val('');
            }
        });
    });

    // ----------------> Send message on Enter key press
    $('#message-input').keypress(function (e) {
        if (e.which == 13) {  // 13 is the Enter key
            $('#send').click();
        }
    });

    // ----------------> Fetch Messages
    function fetchMessages() {
        if (activeUser === null) return;

        $.get('/chat/messages', { receiver_id: activeUser }, function (data) {
            $('#messages').html('');
            let unseenCount = 0;

            data.forEach(msg => {
                let checkmark = msg.seen
                    ? '<i class="fa-solid fa-check-double text-primary"></i>'
                    : '<i class="fa-solid fa-check text-secondary"></i>';

                appendMessage(msg, msg.sender_id === parseInt(`{{ Auth::id() }}`), checkmark);

                if (!msg.seen && msg.sender_id !== parseInt(`{{ Auth::id() }}`)) {
                    unseenCount++;
                }
            });

            if (unseenCount > 0) {
                $("#unseen-" + activeUser)
                    .text(unseenCount)
                    .removeClass('d-none');
            } else {
                $("#unseen-" + activeUser).addClass('d-none');
            }
        });
    }

    // Poll for new messages every second
    setInterval(fetchMessages, 500);

    // ----------------> Append Message to Chat
    let messagesContainer = $('#messages');
    let isUserAtBottom = true;

    messagesContainer.on('scroll', function () {
        let scrollPosition = messagesContainer.scrollTop() + messagesContainer.innerHeight();
        let nearBottom = messagesContainer[0].scrollHeight - 50;
        isUserAtBottom = scrollPosition >= nearBottom;
    });

    function appendMessage(message, isSender, seenStatus) {
        let alignment = isSender ? 'text-end' : 'text-start';
        let bgColorSender = isSender ? 'bg-light' : '';
        let bgColorReceiver = isSender ? '' : 'background: linear-gradient(135deg, rgba(139, 242, 128, 0.475), rgba(223, 246, 220, 0.1));';
        let messageTime = new Date(message.created_at).toLocaleTimeString();
        let senderName = isSender ? 'You' : message.sender.name;

        messagesContainer.append(
            `<div class="mb-2 ${alignment}">
                <div><strong>${senderName}</strong></div>
                <div class="d-inline-block p-2 rounded border border-light shadow ${bgColorSender}" style="${bgColorReceiver}">
                    ${message.message}
                    <small class="d-block text-muted">${messageTime} ${seenStatus}</small>
                </div>
            </div>`
        );

        if (isUserAtBottom) {
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        }
    }

    // ----------------> Mark Messages as Seen
    function markAsSeen() {
        if (activeUser === null) return;

        $.post('/chat/mark-seen', {
            receiver_id: activeUser,
            _token: '{{ csrf_token() }}'
        }, function () {
            $('#messages .text-muted').each(function () {
                let text = $(this).html();
                if (text.includes('<i class="fa-solid fa-check text-secondary"></i>')) {
                    $(this).html(text.replace(
                        '<i class="fa-solid fa-check text-secondary"></i>',
                        '<i class="fa-solid fa-check-double text-primary"></i>'
                    ));
                }
            });

            $("#unseen-" + activeUser).addClass('d-none');
        });
    }

    // ----------------> Fetch Unseen Counts and Play Sound
    let notifiedUsers = {};  // Tracks users who have triggered notifications
    function fetchUnseenCounts() {
        $.get('/chat/unseen-counts', function (data) {
            data.forEach(user => {
                let unseenBadge = $("#unseen-" + user.id);
                let previousCount = parseInt(unseenBadge.text()) || 0;

                if (user.unseen > 0) {
                    unseenBadge
                        .text(user.unseen)
                        .removeClass('d-none');

                    // Check if the user has not triggered a notification yet
                    if (user.unseen > previousCount || activeUser === null) {
                        if (!notifiedUsers[user.id]) {
                            playNotificationSound();
                            notifiedUsers[user.id] = true;  // Mark user as notified
                        }
                    }
                } else {
                    unseenBadge.addClass('d-none');
                    delete notifiedUsers[user.id];  // Reset if messages are seen
                }
            });
        });
    }
    // Play notification sound
    function playNotificationSound() {
        notificationSound.play().catch(err => console.log('Audio blocked:', err));
    }

    fetchUnseenCounts();
    setInterval(fetchUnseenCounts, 2000);
});
</script>
@endpush

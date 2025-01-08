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
                                    $countUnseenMsg = \App\Models\Message::where('sender_id', $user->id)->where('seen', false)->count();
                                @endphp
                                <span class="{{ $countUnseenMsg > 0 ? 'badge bg-secondary' : ''  }} float-end unseen-count"
                                    id="unseen-{{ $user->id }}">
                                    {{ $countUnseenMsg == 0 ? '' : $countUnseenMsg  }}
                                </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-md-9">
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
@endsection

@push('scripts')
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script>
let activeUser = null;
let activeUserName = '';
let seenMessages = {};  // Track seen messages to prevent looping checkmarks

$(document).ready(function () {
    // console.log("Page loaded. Waiting for user selection...");

    //--------------------> Handle user selection
    $('#user-list').on('click', '.user-item', function () {
        $('.user-item').removeClass('active-user');
        $(this).addClass('active-user');

        activeUser = $(this).data('id');
        activeUserName = $(this).data('name');

        // Reset unseen count to 0 for selected user
        $("#unseen-" + activeUser).addClass('d-none');

        $('#chat-header').text(`Chatting with ${activeUserName}`);
        $('#send').prop('disabled', false);

        // Fetch messages immediately when clicking a user
        fetchMessages();

        // Mark messages as seen once chat is open
        markAsSeen();
    });

    //--------------------> Handle message sending
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
                // Append message immediately with single checkmark
                appendMessage(data, true, '<i class="fa-solid fa-check text-secondary"></i>');
                $('#message-input').val('');
            }
        });
    });

    //--------------------> Handle sending message on Enter key press
    $('#message-input').keypress(function (e) {
        if (e.which == 13) {  // 13 is the Enter key
            $('#send').click();
        }
    });

    //--------------------> Fetch and display messages
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
    // Poll for new messages every 5 seconds
    setInterval(fetchMessages, 1000);

    //--------------------> Append message with correct checkmark
    let messagesContainer = $('#messages');
    // Track if the user manually scrolled
    let isUserAtBottom = true;
    // Detect manual scroll and track if at the bottom
    messagesContainer.on('scroll', function () {
        let scrollPosition = messagesContainer.scrollTop() + messagesContainer.innerHeight();
        let nearBottom = messagesContainer[0].scrollHeight - 50;  // Margin of 50px
        isUserAtBottom = scrollPosition >= nearBottom;
    });
    function appendMessage(message, isSender, seenStatus) {
        let alignment = isSender ? 'text-end' : 'text-start';
        let bgColorSender = isSender ? 'bg-light' : '';
        // let bgColorReceiver = isSender ? '' : 'background-color: rgba(139, 242, 128, 0.283);';
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

        // Scroll to bottom only if user is at bottom before appending
        if (isUserAtBottom) {
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        }
    }

    //--------------------> Mark messages as seen
    function markAsSeen() {
        if (activeUser === null) return;

        $.post('/chat/mark-seen', {
            receiver_id: activeUser,
            _token: '{{ csrf_token() }}'
        }, function () {
            $('#messages .text-muted').each(function () {
                let text = $(this).html();
                if (text.includes('<i class="fa-solid fa-check text-secondary"></i>') &&
                    !text.includes('<i class="fa-solid fa-check-double text-primary"></i>')) {
                    $(this).html(text.replace(
                        '<i class="fa-solid fa-check text-secondary"></i>',
                        '<i class="fa-solid fa-check-double text-primary"></i>'
                    ));
                }
            });

            $("#unseen-" + activeUser).addClass('d-none');
        });
    }

    //--------------------> Real-time unseen message count polling for all users
    function fetchUnseenCounts() {
        $.get('/chat/unseen-counts', function (data) {
            data.forEach(user => {
                let unseenBadge = $("#unseen-" + user.id);

                if (user.unseen > 0) {
                    unseenBadge
                        .text(user.unseen)
                        .removeClass('d-none');
                } else {
                    unseenBadge.addClass('d-none');
                }
            });
        });
    }
    // Initial fetch for unseen counts on page load
    fetchUnseenCounts();

    // Poll unseen counts for all users every 5 seconds
    setInterval(fetchUnseenCounts, 2000);
});
</script>
@endpush

<?php

namespace App\Http\Controllers;
use App\Events\Message as MessageEvent;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Traits\FileUploadTrait;

class MessengerController extends Controller
{

    use FileUploadTrait;
    public function index(): View
    {
        return view('messenger.index');
    }

    public function search(Request $request)
    {   $getRecords = null;
        $input = $request['query'];
        $records = User::where('id', '!=', Auth::user()->id)
            ->where(function ($query) use ($input) {
                $query->where('name', 'like', "%{$input}%")
                    ->orWhere('user_name', 'like', "%{$input}%");
                })
            ->get();

        foreach($records as $record) {
            $getRecords .= view('messenger.components.search-item', compact('record'))->render();
        }
        return response()->json([
            'records' => $getRecords
        ]);
    }

     // fetch user by id
     function fetchIdInfo(Request $request)
     {
         $fetch = User::where('id', $request['id'])->first();
        
        
         $sharedPhotos = Message::where('from_id', Auth::user()->id)->where('to_id', $request->id)->whereNotNull('attachment')
         ->orWhere('from_id', $request->id)->where('to_id', Auth::user()->id)->whereNotNull('attachment')
         ->latest()->get();

     $content = '';

     foreach($sharedPhotos as $photo) {
         $content .= view('messenger.components.gallery-item', compact('photo'))->render();
     }

     return response()->json([
         'fetch' => $fetch,
         
         'shared_photos' => $content
     ]);
     }


     function makeSeen(Request $request) {
        Message::where('from_id', $request->id)
            ->where('to_id', Auth::user()->id)
            ->where('seen', 0)->update(['seen' => 1]);

        return true;
    }
    function sendMessage(Request $request)
    {   
        $request->validate([
            // 'message' => ['required'],
            'id' => ['required', 'integer'],
            'temporaryMsgId' => ['required'],
            'attachment' => ['nullable', 'max:1024', 'image']
        ]);

        // store the message in DB
        $attachmentPath = $this->uploadFile($request, 'attachment');
        
        $message = new Message();
        $message->from_id = Auth::user()->id;
        $message->to_id = $request->id;
        $message->body = $request->message;
        if ($attachmentPath) $message->attachment = json_encode($attachmentPath);
        $message->save();

        // broadcast event
        MessageEvent::dispatch($message);

        return response()->json([
            'message' => $message->attachment ? $this->messageCard($message, true) : $this->messageCard($message),
            'tempID' => $request->temporaryMsgId
        ]);
    }
 
     function messageCard($message, $attachment = false)
     {
         return view('messenger.components.message-card', compact('message', 'attachment'))->render();
     }
 
     // fetch messages from database
     function fetchMessages(Request $request)
     {
         $messages = Message::where('from_id', Auth::user()->id)->where('to_id', $request->id)
             ->orWhere('from_id', $request->id)->where('to_id', Auth::user()->id)
             ->latest()->paginate(20);
 
         $response = [
             'last_page' => $messages->lastPage(),
             'last_message' => $messages->last(),
             'messages' => ''
         ];
 
         if (count($messages) < 1) {
             $response['messages'] = "<div class='d-flex justify-content-center no_messages align-items-center h-100'><p>Say 'hi' and start messaging.</p></div>";
             return response()->json($response);
         }
 
         $allMessages = '';
         foreach ($messages->reverse() as $message) {
 
             $allMessages .= $this->messageCard($message, $message->attachment ? true : false);
         }
 
         $response['messages'] = $allMessages;
 
         return response()->json($response);
     }
// fetch contacts from database
function fetchContacts(Request $request)
{
    $users = Message::join('users', function($join) {
       $join->on('messages.from_id', '=', 'users.id')
        ->orOn('messages.to_id', '=', 'users.id');
    })
    ->where(function($q) {
        $q->where('messages.from_id', Auth::user()->id)
        ->orWhere('messages.to_id', Auth::user()->id);
    })
    ->where('users.id', '!=', Auth::user()->id)
    ->select('users.id', DB::raw('MAX(messages.created_at) max_created_at'))
    ->orderBy('max_created_at', 'desc')
    ->groupBy('users.id')
    ->paginate(10);

    if(count($users) > 0) {
        $contacts = '';
        foreach($users as $user) {
            $contacts .= $this->getContactItem($user);
        }

    }else {
        $contacts = "<p class='text-center no_contact'>Your contact list in empty!</p>";
    }

    return response()->json([
        'contacts' => $contacts,
        'last_page' => $users->lastPage()
    ]);

}
function getContactItem($user) {
    $lastMessage = Message::where('from_id', Auth::user()->id)->where('to_id', $user->id)
    ->orWhere('from_id', $user->id)->where('to_id', Auth::user()->id)
    ->latest()->first();
    $unseenCounter = Message::where('from_id', $user->id)->where('to_id', Auth::user()->id)->where('seen', 0)->count();

    return view('messenger.components.contact-list-item', compact('lastMessage', 'unseenCounter', 'user'))->render();

}
}